<?php

namespace App\Http\Controllers\Manutentore;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\Intervention;
use App\Models\MaintenanceRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $deptIds = $user->departments()->pluck('departments.id');
        $roleIds = $user->maintenanceRoles()->pluck('maintenance_roles.id');

        // Miei interventi + interventi disponibili compatibili (non ancora chiusi).
        $interventions = Intervention::with([
            'equipment.department.area',
            'area',
            'department',
            'maintenanceRole',
            'assignedUser',
            'collaborations' => fn ($q) => $q
                ->where('user_id', $user->id)
                ->where('status', \App\Models\InterventionCollaboration::STATUS_ACCEPTED),
            'reports' => fn ($q) => $q->where('user_id', $user->id),
            'activeReschedules.user:id,name',
        ])
            ->whereIn('status', ['open', 'planned', 'in_progress'])
            ->where(function ($q) {
                // Nasconde ticket sospesi/rinviati finché non arriva la data.
                $q->whereNull('suspended_until')
                    ->orWhere('suspended_until', '<=', today());
            })
            ->where(function ($q) use ($user, $deptIds, $roleIds) {
                // Miei assegnati
                $q->where('assigned_user_id', $user->id);

                // Collaborazioni accettate
                $q->orWhereHas('collaborations', fn ($c) => $c
                    ->where('user_id', $user->id)
                    ->where('status', \App\Models\InterventionCollaboration::STATUS_ACCEPTED));

                // Disponibili compatibili per zona/specializzazione
                $q->orWhere(function ($q2) use ($deptIds, $roleIds) {
                    $q2->whereNull('assigned_user_id');
                    if ($roleIds->isNotEmpty()) {
                        $q2->whereIn('maintenance_role_id', $roleIds);
                    }
                    if ($deptIds->isNotEmpty()) {
                        $q2->where(function ($q3) use ($deptIds) {
                            $q3->whereIn('department_id', $deptIds)
                                ->orWhereHas('equipment', fn ($eq) => $eq->whereIn('department_id', $deptIds));
                        });
                    }
                });
            })
            ->get();

        $today = today();
        $weekAhead = today()->copy()->addDays(7);
        $weekEnd = $weekAhead->copy()->endOfDay();

        $active = $interventions
            ->reject(fn ($i) => in_array($i->status, ['completed', 'cancelled'], true))
            ->reject(function ($i) use ($today) {
                // L'utente ha già concluso definitivamente il suo lavoro sul ticket
                if ($i->reports->contains('is_final', true)) {
                    return true;
                }
                // L'ultimo rapportino dell'utente rinvia il ticket a una data futura
                $last = $i->reports->sortByDesc('created_at')->first();
                if ($last && $last->next_work_date && $last->next_work_date->gt($today)) {
                    return true;
                }

                return false;
            });

        // 1. Scaduti: pianificati con data passata + ordinari in ritardo (is_overdue).
        $scaduti = $active->filter(function ($i) use ($today) {
            $overduePianificato = $i->tipo === 'pianificazione'
                && $i->scheduled_date
                && $i->scheduled_date->lt($today);
            $overdueLibero = $i->tipo !== 'pianificazione' && $i->is_overdue;

            return $overduePianificato || $overdueLibero;
        })->sortBy(fn ($i) => $i->deadline?->timestamp ?? $i->scheduled_date?->timestamp ?? 0)->values();

        $scadutiIds = $scaduti->pluck('id')->all();

        // 2. Alta priorità nei prossimi 7 giorni (escluso chi è già in scaduti).
        $altaPriorita = $active->filter(function ($i) use ($scadutiIds, $weekEnd) {
            if (in_array($i->id, $scadutiIds, true)) return false;
            if ($i->priority !== 'high') return false;

            return $i->deadline && $i->deadline->lte($weekEnd);
        })->sortBy(fn ($i) => $i->deadline?->timestamp ?? 0)->values();

        $altaIds = $altaPriorita->pluck('id')->all();

        // 3. Pianificati nei prossimi 7 giorni (tipo=pianificazione oppure priorità=fixed_date).
        $pianificati = $active->filter(function ($i) use ($scadutiIds, $altaIds, $today, $weekAhead) {
            if (in_array($i->id, $scadutiIds, true) || in_array($i->id, $altaIds, true)) return false;
            $isPianificato = $i->tipo === 'pianificazione' || $i->priority === 'fixed_date';
            if (! $isPianificato || ! $i->scheduled_date) return false;

            return $i->scheduled_date->gte($today) && $i->scheduled_date->lte($weekAhead);
        })->sortBy(fn ($i) => $i->scheduled_date->timestamp)->values();

        $pianificatiIds = $pianificati->pluck('id')->all();

        // 4. Bassa priorità nei prossimi 7 giorni.
        $bassaPriorita = $active->filter(function ($i) use ($scadutiIds, $altaIds, $pianificatiIds, $weekEnd) {
            if (in_array($i->id, $scadutiIds, true)
                || in_array($i->id, $altaIds, true)
                || in_array($i->id, $pianificatiIds, true)) {
                return false;
            }
            if ($i->priority !== 'low') return false;

            return $i->deadline && $i->deadline->lte($weekEnd);
        })->sortBy(fn ($i) => $i->deadline?->timestamp ?? 0)->values();

        [$quickAreas, $quickDepartments, $quickEquipments, $quickMaintenanceRoles] = self::quickOpenScope($user);

        return view('manutentore.home', compact(
            'user',
            'scaduti',
            'altaPriorita',
            'pianificati',
            'bassaPriorita',
            'quickAreas',
            'quickDepartments',
            'quickEquipments',
            'quickMaintenanceRoles'
        ));
    }

    /**
     * Aree / zone / impianti / specializzazioni limitati al perimetro dell'utente,
     * usati dal form mobile "Nuovo ticket".
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection, 2: \Illuminate\Support\Collection, 3: \Illuminate\Support\Collection}
     */
    public static function quickOpenScope(\App\Models\User $user): array
    {
        $user->loadMissing(['departments', 'areas']);
        $userDeptIds = $user->departments->pluck('id')->all();
        $userAreaIds = $user->assignedAreaIds()->all();

        $areas = Area::whereIn('id', $userAreaIds)->where('active', true)
            ->orderBy('name')->get(['id', 'name']);
        $departments = Department::whereIn('id', $userDeptIds)->where('active', true)
            ->orderBy('name')->get(['id', 'name', 'area_id']);
        $equipments = empty($userDeptIds)
            ? collect()
            : Equipment::whereIn('department_id', $userDeptIds)
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'department_id']);
        $maintenanceRoles = MaintenanceRole::orderBy('name')->get(['id', 'name']);

        return [$areas, $departments, $equipments, $maintenanceRoles];
    }

    public function profile(): View
    {
        $user = Auth::user()->load(['maintenanceRoles', 'departments.area']);

        $departmentsByArea = $user->departments
            ->sortBy('name')
            ->groupBy(fn ($d) => $d->area?->id ?? 0)
            ->sortBy(fn ($group) => $group->first()?->area?->name ?? '');

        return view('manutentore.profile', compact('user', 'departmentsByArea'));
    }
}
