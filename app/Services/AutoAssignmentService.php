<?php

namespace App\Services;

use App\Models\Intervention;
use App\Models\User;
use App\Support\InterventionLogActions;
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
 * - A parità di disponibilità, ordina per level nel pivot (5 = massimo), poi random
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
     */
    private const WINDOW_HOURS = [
        'high' => 24,
        'low' => 168,
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
        if (! $intervention->maintenance_role_id || $intervention->assigned_user_id) {
            return ['status' => 'skipped', 'user' => null, 'shift_start' => null];
        }

        $intervention->loadMissing(['maintenanceRole', 'area', 'department', 'equipment.department.area']);

        $isLow = $intervention->priority === 'low';
        $scopeContext = $this->scopeContext($intervention);

        // Tutti i manutentori compatibili per specializzazione + zona (senza esclusione impegno)
        $compatible = $this->loadCompatible($intervention);

        if ($compatible->isEmpty()) {
            $this->logFailure($intervention, $scopeContext, $this->reasonNoCompatible($scopeContext));

            return ['status' => 'no_match', 'user' => null, 'shift_start' => null];
        }

        // Filtra chi è impegnato (sempre in_progress, e planned se priority > low)
        $busyStatuses = $isLow ? ['in_progress'] : ['in_progress', 'planned'];
        [$eligible, $excludedBusy] = $this->splitByAvailability($compatible, $busyStatuses);

        $baseContext = $scopeContext + [
            'compatible_count' => $compatible->count(),
            'eligible_count' => $eligible->count(),
            'excluded_busy' => $excludedBusy,
            'busy_statuses' => $busyStatuses,
        ];

        if ($eligible->isEmpty()) {
            $this->logFailure($intervention, $baseContext, $this->reasonAllBusy($baseContext));

            return ['status' => 'no_match', 'user' => null, 'shift_start' => null];
        }

        if (self::SHIFT_FILTERING_ENABLED) {
            [$windowStart, $windowEnd] = $this->buildWindow($intervention);

            $inShift = $eligible->filter(
                fn (User $u) => $this->hasShiftInWindow($u, $windowStart, $windowEnd)
            );

            if ($inShift->isNotEmpty()) {
                [$user, $pickContext] = $this->pickBestWithContext($inShift, $intervention->maintenance_role_id);
                $intervention->update(['assigned_user_id' => $user->id]);
                $this->logAutoAssigned($intervention, $user, 'assigned',
                    $baseContext + $pickContext + ['selection_path' => 'in_shift'],
                    $this->reasonAssigned($baseContext, $pickContext, 'in_shift', $windowStart, $windowEnd));

                return ['status' => 'assigned', 'user' => $user, 'shift_start' => null];
            }

            $next = $this->findNextAvailable($eligible, $windowEnd, $intervention->maintenance_role_id);

            if ($next) {
                [$user, $pickContext] = $this->pickBestWithContext(collect([$next['user']]), $intervention->maintenance_role_id);
                $intervention->update(['assigned_user_id' => $user->id]);
                $this->logAutoAssigned($intervention, $user, 'next_shift',
                    $baseContext + $pickContext + ['selection_path' => 'next_shift'],
                    $this->reasonNextShift($baseContext, $pickContext, $next['shift_start']),
                    $next['shift_start']);

                return ['status' => 'next_shift', 'user' => $user, 'shift_start' => $next['shift_start']];
            }

            $this->logFailure($intervention, $baseContext, 'Filtro turni attivo: nessun candidato eligibile ha turni nella finestra né turni futuri.');

            return ['status' => 'no_match', 'user' => null, 'shift_start' => null];
        }

        // ── Filtro turni DISATTIVATO: assegna direttamente al miglior candidato ──
        [$user, $pickContext] = $this->pickBestWithContext($eligible, $intervention->maintenance_role_id);
        $intervention->update(['assigned_user_id' => $user->id]);
        $this->logAutoAssigned($intervention, $user, 'assigned',
            $baseContext + $pickContext + ['selection_path' => 'best_level'],
            $this->reasonAssigned($baseContext, $pickContext, 'best_level'));

        return ['status' => 'assigned', 'user' => $user, 'shift_start' => null];
    }

    /**
     * Logga l'esito positivo dell'auto-assegnazione.
     */
    private function logAutoAssigned(Intervention $intervention, User $user, string $status, array $context, string $reason, ?Carbon $shiftStart = null): void
    {
        InterventionActivityLogger::system($intervention, InterventionLogActions::AUTO_ASSIGNED, array_filter([
            'reason' => $reason,
            'assigned_user_id' => $user->id,
            'assigned_user_name' => $user->name,
            'assignment_status' => $status,
            'shift_start' => $shiftStart?->toIso8601String(),
        ] + $context, fn ($v) => $v !== null && $v !== []));
    }

    /**
     * Logga il fallimento dell'auto-assegnazione (nessun candidato).
     */
    private function logFailure(Intervention $intervention, array $context, string $reason): void
    {
        InterventionActivityLogger::system($intervention, InterventionLogActions::AUTO_ASSIGNMENT_FAILED, array_filter([
            'reason' => $reason,
        ] + $context, fn ($v) => $v !== null && $v !== []));
    }

    /**
     * Estrae i dati di scope (specializzazione, zona, area, priorità) per il log.
     */
    private function scopeContext(Intervention $intervention): array
    {
        $area = $intervention->area ?? $intervention->equipment?->department?->area;
        $department = $intervention->department ?? $intervention->equipment?->department;

        return array_filter([
            'priority' => $intervention->priority,
            'maintenance_role_id' => $intervention->maintenance_role_id,
            'maintenance_role_name' => $intervention->maintenanceRole?->name,
            'area_id' => $area?->id,
            'area_name' => $area?->name,
            'department_id' => $department?->id,
            'department_name' => $department?->name,
        ], fn ($v) => $v !== null);
    }

    /**
     * Divide la collezione in [eligible, excludedBusy].
     * `excludedBusy` è una lista di {id, name, busy_status} per il log.
     *
     * @param  array<string>  $busyStatuses
     * @return array{0: Collection, 1: array<int, array{id:int, name:string, busy_with:string}>}
     */
    private function splitByAvailability(Collection $candidates, array $busyStatuses): array
    {
        $eligible = collect();
        $excluded = [];

        foreach ($candidates as $user) {
            $busy = $user->interventions
                ->whereIn('status', $busyStatuses)
                ->first();

            if ($busy) {
                $excluded[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'busy_with' => '#'.$busy->id.' ('.$busy->status.')',
                ];
                continue;
            }

            $eligible->push($user);
        }

        return [$eligible, $excluded];
    }

    private function reasonNoCompatible(array $scope): string
    {
        $parts = ["nessun manutentore con la specializzazione «{$scope['maintenance_role_name']}»"];
        if (! empty($scope['department_name'])) {
            $parts[] = "assegnato alla zona «{$scope['department_name']}»";
        } elseif (! empty($scope['area_name'])) {
            $parts[] = "presente nell'area «{$scope['area_name']}»";
        }

        return 'Auto-assegnazione fallita: '.implode(' ', $parts).'.';
    }

    private function reasonAllBusy(array $context): string
    {
        $n = (int) ($context['compatible_count'] ?? 0);
        $statuses = implode('/', $context['busy_statuses'] ?? []);

        return "Auto-assegnazione fallita: tutti i {$n} manutentori compatibili erano già impegnati ({$statuses}).";
    }

    private function reasonAssigned(array $context, array $pick, string $path, ?Carbon $windowStart = null, ?Carbon $windowEnd = null): string
    {
        $eligible = (int) ($context['eligible_count'] ?? 0);
        $compatible = (int) ($context['compatible_count'] ?? 0);
        $excluded = $compatible - $eligible;
        $level = $pick['selected_level'] ?? 0;
        $tied = (int) ($pick['tied_with_count'] ?? 1);
        $name = $pick['assigned_user_name'] ?? '?';

        $base = "Scelto «{$name}» (livello {$level}/5 sulla specializzazione «{$context['maintenance_role_name']}»)";

        $detail = " su {$eligible} candidat".($eligible === 1 ? 'o' : 'i')." disponibil".($eligible === 1 ? 'e' : 'i');
        if ($excluded > 0) {
            $detail .= " ({$excluded} esclus".($excluded === 1 ? 'o' : 'i')." perché impegnat".($excluded === 1 ? 'o' : 'i').")";
        }

        $tieBreak = $tied > 1
            ? ". {$tied} candidati a pari livello: scelta casuale fra di loro."
            : '.';

        $shift = ($path === 'in_shift' && $windowStart && $windowEnd)
            ? " In turno nella finestra {$windowStart->format('d/m H:i')}–{$windowEnd->format('d/m H:i')}."
            : '';

        return $base.$detail.$tieBreak.$shift;
    }

    private function reasonNextShift(array $context, array $pick, Carbon $shiftStart): string
    {
        $name = $pick['assigned_user_name'] ?? '?';
        $level = $pick['selected_level'] ?? 0;
        $when = $shiftStart->locale('it')->isoFormat('dddd D MMM [alle] HH:mm');

        return "Nessun candidato disponibile in turno entro la deadline. "
            ."Scelto «{$name}» (livello {$level}/5) come primo a rientrare in servizio: {$when}.";
    }

    // ─── Finestra temporale ───────────────────────────────────────────────────

    private function buildWindow(Intervention $intervention): array
    {
        $now = Carbon::now();

        if ($intervention->priority === 'fixed_date') {
            $end = $intervention->scheduled_date
                ? Carbon::parse(
                    $intervention->scheduled_date->toDateString()
                    .' '.($intervention->scheduled_start_time ?? '08:00:00')
                )
                : $now->copy()->addDays(7);

            return [$now, $end];
        }

        $hours = self::WINDOW_HOURS[$intervention->priority] ?? 24;

        return [$now, $now->copy()->addHours($hours)];
    }

    // ─── Candidati ───────────────────────────────────────────────────────────

    /**
     * Carica i manutentori compatibili per specializzazione + zona/area.
     * NON applica l'esclusione per impegno: quella è fatta in PHP da
     * splitByAvailability() per poter loggare gli esclusi.
     */
    private function loadCompatible(Intervention $intervention): Collection
    {
        $deptId = $intervention->department_id
            ?? $intervention->equipment?->department_id;
        $areaId = $intervention->area_id
            ?? $intervention->equipment?->department?->area_id;

        $query = User::where('role', 'manutentore')
            ->where('active', true)
            ->whereHas('maintenanceRoles', fn ($q) => $q->where('maintenance_roles.id', $intervention->maintenance_role_id)
            );

        // Zona compatibile: se è specificato un department, filtra per zona,
        // altrimenti se c'è solo l'area filtra per qualsiasi zona dell'area
        // (oppure assegnazione diretta dell'utente all'area).
        if ($deptId) {
            $query->whereHas('departments', fn ($q) => $q->where('departments.id', $deptId)
            );
        } elseif ($areaId) {
            $query->where(function ($q) use ($areaId) {
                $q->whereHas('departments', fn ($qq) => $qq->where('departments.area_id', $areaId))
                    ->orWhereHas('areas', fn ($qq) => $qq->where('areas.id', $areaId));
            });
        }

        return $query->with([
            'workScheduleSlots',
            'maintenanceRoles',
            'interventions:id,assigned_user_id,status',
        ])->get();
    }

    // ─── Controllo turni ─────────────────────────────────────────────────────

    /**
     * Verifica se l'utente ha almeno uno slot lavorativo che si sovrappone alla finestra.
     */
    private function hasShiftInWindow(User $user, Carbon $from, Carbon $to): bool
    {
        $offDays = $this->offDays($user);

        foreach ($user->workScheduleSlots as $slot) {
            if ($slot->type !== 'lavorativo') {
                continue;
            }

            if ($slot->is_recurring) {
                // Espandi le ricorrenze settimanali nella finestra
                $cursor = $from->copy()->startOfDay();
                $limit = $to->copy()->endOfDay();

                while ($cursor->lte($limit)) {
                    if ($cursor->dayOfWeek === $slot->day_of_week) {
                        $dateStr = $cursor->toDateString();
                        if (! isset($offDays[$dateStr])) {
                            $slotStart = $cursor->copy()->setTimeFromTimeString($slot->start_time);
                            $slotEnd = $cursor->copy()->setTimeFromTimeString($slot->end_time);
                            if ($slotStart->lte($to) && $slotEnd->gte($from)) {
                                return true;
                            }
                        }
                    }
                    $cursor->addDay();
                }
            } else {
                if (! $slot->date || ! $slot->start_time || ! $slot->end_time) {
                    continue;
                }
                $dateStr = $slot->date->toDateString();
                $slotStart = Carbon::parse($dateStr.' '.$slot->start_time);
                $slotEnd = Carbon::parse($dateStr.' '.$slot->end_time);

                if (! isset($offDays[$dateStr]) && $slotStart->lte($to) && $slotEnd->gte($from)) {
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
        $offDays = $this->offDays($user);
        $earliest = null;

        foreach ($user->workScheduleSlots as $slot) {
            if ($slot->type !== 'lavorativo' || ! $slot->start_time) {
                continue;
            }

            if ($slot->is_recurring) {
                // Cerca la prossima occorrenza del giorno (entro 14 giorni)
                $cursor = $after->copy()->addDay()->startOfDay();
                for ($i = 0; $i < 14; $i++, $cursor->addDay()) {
                    if ($cursor->dayOfWeek === $slot->day_of_week) {
                        $dateStr = $cursor->toDateString();
                        $slotStart = $cursor->copy()->setTimeFromTimeString($slot->start_time);
                        if (! isset($offDays[$dateStr]) && $slotStart->gt($after)) {
                            if (! $earliest || $slotStart->lt($earliest)) {
                                $earliest = $slotStart;
                            }
                        }
                        break;
                    }
                }
            } else {
                if (! $slot->date) {
                    continue;
                }
                $dateStr = $slot->date->toDateString();
                $slotStart = Carbon::parse($dateStr.' '.$slot->start_time);
                if (! isset($offDays[$dateStr]) && $slotStart->gt($after)) {
                    if (! $earliest || $slotStart->lt($earliest)) {
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
     * Ordina per level nel pivot (5 = massimo) e sceglie random tra i pari.
     */
    private function pickBest(Collection $candidates, int $roleId): User
    {
        return $this->pickBestWithContext($candidates, $roleId)[0];
    }

    /**
     * Come pickBest, ma restituisce anche i metadati della scelta per il log:
     * livello del candidato selezionato, numero di candidati a pari livello.
     *
     * @return array{0: User, 1: array{selected_level:int, tied_with_count:int, assigned_user_name:string}}
     */
    private function pickBestWithContext(Collection $candidates, int $roleId): array
    {
        $ranked = $candidates
            ->map(fn (User $u) => [
                'user' => $u,
                'level' => $u->maintenanceRoles->firstWhere('id', $roleId)?->pivot->level ?? 0,
            ])
            ->sortByDesc('level');

        $best = $ranked->first()['level'];
        $group = $ranked->filter(fn ($r) => $r['level'] === $best)->map(fn ($r) => $r['user']);
        $picked = $group->random();

        return [$picked, [
            'selected_level' => (int) $best,
            'tied_with_count' => $group->count(),
            'assigned_user_name' => $picked->name,
        ]];
    }

    /**
     * Tra tutti i candidati, trova chi ha il prossimo turno più vicino dopo $after.
     */
    private function findNextAvailable(Collection $candidates, Carbon $after, int $roleId): ?array
    {
        $withShift = $candidates
            ->map(fn (User $u) => ['user' => $u, 'shift_start' => $this->nextShiftStart($u, $after)])
            ->filter(fn ($r) => $r['shift_start'] !== null)
            ->sortBy(fn ($r) => $r['shift_start']->timestamp);

        if ($withShift->isEmpty()) {
            return null;
        }

        $earliest = $withShift->first()['shift_start'];
        $group = $withShift
            ->filter(fn ($r) => $r['shift_start']->equalTo($earliest))
            ->map(fn ($r) => $r['user']);

        return [
            'user' => $this->pickBest($group, $roleId),
            'shift_start' => $earliest,
        ];
    }
}
