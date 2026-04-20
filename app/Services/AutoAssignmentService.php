<?php

namespace App\Services;

use App\Models\Intervention;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Servizio di auto-assegnazione interventi.
 *
 * Logica:
 * - Cerca manutentori con la specializzazione richiesta + zona/area compatibile
 * - Esclude chi ha interventi in_progress (sempre) o planned (se priorità > low)
 * - Preferisce chi è in turno nella finestra [ora → deadline priorità]
 * - Se nessuno è in turno, prende il prossimo turno disponibile
 * - A parità di disponibilità, ordina per level nel pivot (1 = massimo), poi random
 * - Risultato: array ['status', 'user', 'shift_start']
 *   status: 'assigned' | 'next_shift' | 'no_match' | 'skipped'
 */
class AutoAssignmentService
{
    /**
     * Attiva/disattiva il filtro sui turni lavorativi.
     * Quando è false ignora i WorkScheduleSlot e assegna direttamente
     * al miglior candidato compatibile (pivot level + random).
     * TODO: riattivare quando il piano orario sarà consolidato.
     */
    private const SHIFT_FILTERING_ENABLED = false;

    /**
     * Finestre di priorità in ore (da created_at al limite).
     * urgent = 0 → finestra [ora, ora] = "chi è in turno adesso"
     */
    private const WINDOW_HOURS = [
        'urgent'     => 0,
        'high'       => 24,
        'medium'     => 72,
        'low'        => 168,
        'fixed_date' => null,
    ];

    /**
     * Esegue l'auto-assegnazione su un intervento appena creato.
     *
     * @return array{status: string, user: ?User, shift_start: ?Carbon}
     */
    public function assign(Intervention $intervention): array
    {
        // Non auto-assegnare se manca la specializzazione o è già assegnato
        if (!$intervention->maintenance_role_id || $intervention->assigned_user_id) {
            return ['status' => 'skipped', 'user' => null, 'shift_start' => null];
        }

        $isLow = $intervention->priority === 'low';

        // Candidati compatibili e disponibili
        $candidates = $this->loadCandidates($intervention, $isLow);

        if ($candidates->isEmpty()) {
            return ['status' => 'no_match', 'user' => null, 'shift_start' => null];
        }

        if (self::SHIFT_FILTERING_ENABLED) {
            [$windowStart, $windowEnd] = $this->buildWindow($intervention);

            // 1. Cerca qualcuno in turno nella finestra
            $inShift = $candidates->filter(
                fn(User $u) => $this->hasShiftInWindow($u, $windowStart, $windowEnd)
            );

            if ($inShift->isNotEmpty()) {
                $user = $this->pickBest($inShift, $intervention->maintenance_role_id);
                $intervention->update(['assigned_user_id' => $user->id]);
                return ['status' => 'assigned', 'user' => $user, 'shift_start' => null];
            }

            // 2. Nessuno in turno: cerca il primo turno successivo alla finestra
            $next = $this->findNextAvailable($candidates, $windowEnd, $intervention->maintenance_role_id);

            if ($next) {
                $intervention->update(['assigned_user_id' => $next['user']->id]);
                return ['status' => 'next_shift', 'user' => $next['user'], 'shift_start' => $next['shift_start']];
            }

            return ['status' => 'no_match', 'user' => null, 'shift_start' => null];
        }

        // ── Filtro turni DISATTIVATO: assegna direttamente al miglior candidato ──
        $user = $this->pickBest($candidates, $intervention->maintenance_role_id);
        $intervention->update(['assigned_user_id' => $user->id]);
        return ['status' => 'assigned', 'user' => $user, 'shift_start' => null];
    }

    // ─── Finestra temporale ───────────────────────────────────────────────────

    private function buildWindow(Intervention $intervention): array
    {
        $now = Carbon::now();

        if ($intervention->priority === 'fixed_date') {
            $end = $intervention->scheduled_date
                ? Carbon::parse(
                    $intervention->scheduled_date->toDateString()
                    . ' ' . ($intervention->scheduled_start_time ?? '08:00:00')
                )
                : $now->copy()->addDays(7);
            return [$now, $end];
        }

        $hours = self::WINDOW_HOURS[$intervention->priority] ?? 24;

        // urgent → finestra puntuale su "ora"
        return [$now, $hours === 0 ? $now->copy() : $now->copy()->addHours($hours)];
    }

    // ─── Candidati ───────────────────────────────────────────────────────────

    private function loadCandidates(Intervention $intervention, bool $isLow): Collection
    {
        $deptId = $intervention->department_id
            ?? $intervention->equipment?->department_id;

        $query = User::where('role', 'manutentore')
            ->whereHas('maintenanceRoles', fn($q) =>
                $q->where('maintenance_roles.id', $intervention->maintenance_role_id)
            );

        // Zona compatibile
        if ($deptId) {
            $query->whereHas('departments', fn($q) =>
                $q->where('departments.id', $deptId)
            );
        }

        // Escludi sempre chi è in_progress
        $query->whereDoesntHave('interventions', fn($q) =>
            $q->where('status', 'in_progress')
        );

        // Per priorità > low, escludi anche chi ha planned
        if (!$isLow) {
            $query->whereDoesntHave('interventions', fn($q) =>
                $q->where('status', 'planned')
            );
        }

        return $query->with(['workScheduleSlots', 'maintenanceRoles'])->get();
    }

    // ─── Controllo turni ─────────────────────────────────────────────────────

    /**
     * Verifica se l'utente ha almeno uno slot lavorativo che si sovrappone alla finestra.
     */
    private function hasShiftInWindow(User $user, Carbon $from, Carbon $to): bool
    {
        $offDays = $this->offDays($user);

        foreach ($user->workScheduleSlots as $slot) {
            if ($slot->type !== 'lavorativo') continue;

            if ($slot->is_recurring) {
                // Espandi le ricorrenze settimanali nella finestra
                $cursor = $from->copy()->startOfDay();
                $limit  = $to->copy()->endOfDay();

                while ($cursor->lte($limit)) {
                    if ($cursor->dayOfWeek === $slot->day_of_week) {
                        $dateStr = $cursor->toDateString();
                        if (!isset($offDays[$dateStr])) {
                            $slotStart = $cursor->copy()->setTimeFromTimeString($slot->start_time);
                            $slotEnd   = $cursor->copy()->setTimeFromTimeString($slot->end_time);
                            if ($slotStart->lte($to) && $slotEnd->gte($from)) {
                                return true;
                            }
                        }
                    }
                    $cursor->addDay();
                }
            } else {
                if (!$slot->date || !$slot->start_time || !$slot->end_time) continue;
                $dateStr   = $slot->date->toDateString();
                $slotStart = Carbon::parse($dateStr . ' ' . $slot->start_time);
                $slotEnd   = Carbon::parse($dateStr . ' ' . $slot->end_time);

                if (!isset($offDays[$dateStr]) && $slotStart->lte($to) && $slotEnd->gte($from)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Ritorna il primo inizio-turno dell'utente successivo a $after.
     */
    private function nextShiftStart(User $user, Carbon $after): ?Carbon
    {
        $offDays  = $this->offDays($user);
        $earliest = null;

        foreach ($user->workScheduleSlots as $slot) {
            if ($slot->type !== 'lavorativo' || !$slot->start_time) continue;

            if ($slot->is_recurring) {
                // Cerca la prossima occorrenza del giorno (entro 14 giorni)
                $cursor = $after->copy()->addDay()->startOfDay();
                for ($i = 0; $i < 14; $i++, $cursor->addDay()) {
                    if ($cursor->dayOfWeek === $slot->day_of_week) {
                        $dateStr   = $cursor->toDateString();
                        $slotStart = $cursor->copy()->setTimeFromTimeString($slot->start_time);
                        if (!isset($offDays[$dateStr]) && $slotStart->gt($after)) {
                            if (!$earliest || $slotStart->lt($earliest)) {
                                $earliest = $slotStart;
                            }
                        }
                        break;
                    }
                }
            } else {
                if (!$slot->date) continue;
                $dateStr   = $slot->date->toDateString();
                $slotStart = Carbon::parse($dateStr . ' ' . $slot->start_time);
                if (!isset($offDays[$dateStr]) && $slotStart->gt($after)) {
                    if (!$earliest || $slotStart->lt($earliest)) {
                        $earliest = $slotStart;
                    }
                }
            }
        }

        return $earliest;
    }

    /**
     * Restituisce un array indicizzato per data di tutti i giorni off (ferie/riposi).
     */
    private function offDays(User $user): array
    {
        $off = [];
        foreach ($user->workScheduleSlots as $slot) {
            if (in_array($slot->type, ['ferie', 'riposi']) && $slot->date) {
                $off[$slot->date->toDateString()] = true;
            }
        }
        return $off;
    }

    // ─── Selezione del candidato migliore ────────────────────────────────────

    /**
     * Ordina per level nel pivot (1 = massimo) e sceglie random tra i pari.
     */
    private function pickBest(Collection $candidates, int $roleId): User
    {
        $ranked = $candidates
            ->map(fn(User $u) => [
                'user'  => $u,
                'level' => $u->maintenanceRoles->firstWhere('id', $roleId)?->pivot->level ?? 999,
            ])
            ->sortBy('level');

        $best  = $ranked->first()['level'];
        $group = $ranked->filter(fn($r) => $r['level'] === $best)->map(fn($r) => $r['user']);

        return $group->random();
    }

    /**
     * Tra tutti i candidati, trova chi ha il prossimo turno più vicino dopo $after.
     */
    private function findNextAvailable(Collection $candidates, Carbon $after, int $roleId): ?array
    {
        $withShift = $candidates
            ->map(fn(User $u) => ['user' => $u, 'shift_start' => $this->nextShiftStart($u, $after)])
            ->filter(fn($r) => $r['shift_start'] !== null)
            ->sortBy(fn($r) => $r['shift_start']->timestamp);

        if ($withShift->isEmpty()) return null;

        $earliest = $withShift->first()['shift_start'];
        $group    = $withShift
            ->filter(fn($r) => $r['shift_start']->equalTo($earliest))
            ->map(fn($r) => $r['user']);

        return [
            'user'        => $this->pickBest($group, $roleId),
            'shift_start' => $earliest,
        ];
    }
}
