<?php

namespace App\Http\Controllers;

use App\Http\Requests\InterventionRequest;
use App\Models\Area;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentComponent;
use App\Models\Intervention;
use App\Models\MaintenanceRole;
use App\Models\Media;
use App\Models\User;
use App\Observers\InterventionObserver;
use App\Services\InterventionActivityLogger;
use App\Support\InterventionLogActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InterventionController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        abort_if($user->role === 'manutentore', 403);

        $query = Intervention::with(['equipment.department.area', 'area', 'department', 'assignedUser', 'creator:id,name']);

        // Operatore: vede solo i ticket che ricadono nelle sue aree/zone (e quelli che ha aperto lui).
        if ($user->role === 'operator') {
            $deptIds = $user->departments()->pluck('departments.id')->all();
            $areaIds = $user->assignedAreaIds()->all();

            $query->where(function ($q) use ($user, $deptIds, $areaIds) {
                // Sempre i ticket aperti da lui (rete di sicurezza per casi fuori perimetro).
                $q->where('created_by', $user->id);

                if (! empty($deptIds)) {
                    $q->orWhereIn('department_id', $deptIds);
                    $q->orWhereHas('equipment', fn ($eq) => $eq->whereIn('department_id', $deptIds));
                }

                if (! empty($areaIds)) {
                    $q->orWhereIn('area_id', $areaIds);
                }
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($user->role === 'admin') {
            if ($request->filled('title')) {
                $query->where('title', 'like', '%'.$request->input('title').'%');
            }

            if ($request->filled('id')) {
                $query->where('id', (int) $request->input('id'));
            }

            if ($request->filled('created_by_id')) {
                $query->where('created_by', $request->input('created_by_id'));
            }

            if ($request->filled('assigned_to_id')) {
                $query->where('assigned_user_id', $request->input('assigned_to_id'));
            }

            if ($request->filled('area_id')) {
                $areaId = (int) $request->input('area_id');
                $query->where(function ($q) use ($areaId) {
                    $q->where('area_id', $areaId)
                        ->orWhereHas('equipment.department', fn ($d) => $d->where('area_id', $areaId));
                });
            }

            if ($request->filled('department_id')) {
                $deptId = (int) $request->input('department_id');
                $query->where(function ($q) use ($deptId) {
                    $q->where('department_id', $deptId)
                        ->orWhereHas('equipment', fn ($e) => $e->where('department_id', $deptId));
                });
            }
        }

        $interventions = $query->orderBy('id', 'DESC')->get();

        $openableUsers = collect();
        $assignableUsers = collect();
        $areas = collect();
        $departments = collect();
        if ($user->role === 'admin') {
            $openableUsers = User::whereIn('role', ['admin', 'operator', 'manutentore'])
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'role'])
                ->groupBy('role');
            $assignableUsers = User::where('role', 'manutentore')
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
            $areas = Area::where('active', true)->orderBy('name')->get(['id', 'name']);
            $departments = Department::where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'area_id']);
        }

        return view('interventions.index', compact('interventions', 'openableUsers', 'assignableUsers', 'areas', 'departments'));
    }

    public function create(): View
    {
        $user = Auth::user();
        $equipments = Equipment::where('active', true)->orderBy('name')->get();
        $operators = User::whereIn('role', ['operator', 'manutentore'])->where('active', true)->orderBy('name')->get();
        $areas = Area::where('active', true)->orderBy('name')->get();
        $departments = Department::where('active', true)->orderBy('name')->get();
        $maintenanceRoles = MaintenanceRole::orderBy('name')->get();
        $components = EquipmentComponent::all(['id', 'equipment_id', 'name']);

        $userRole = $user->role;
        $userDepartmentIds = $user->departments->pluck('id')->toArray();
        $userAreaId = $user->departments->first()?->area_id;

        $tempMediaId = 'temp_'.uniqid().'_'.Auth::id();
        session(['intervention_temp_media_id' => $tempMediaId]);

        return view('interventions.create', compact(
            'equipments', 'operators', 'areas', 'departments', 'maintenanceRoles', 'components',
            'userRole', 'userDepartmentIds', 'userAreaId', 'tempMediaId'
        ));
    }

    public function store(InterventionRequest $request): RedirectResponse
    {
        $isPianificazione = $request->tipo === 'pianificazione';
        $priority = $isPianificazione ? 'low' : ($request->priority ?? 'low');
        $isFixedDate = ! $isPianificazione && $priority === 'fixed_date';
        $assignType = $request->input('assignment_type', 'specializzazione');
        $isDirectAssign = $assignType === 'diretto';

        $equipmentId = $isPianificazione ? $request->equipment_id : null;
        $areaId = ! $isPianificazione ? ($request->area_id ?: null) : null;
        $deptId = ! $isPianificazione ? ($request->department_id ?: null) : null;

        $title = $request->filled('title')
            ? $request->title
            : Intervention::generateFallbackTitle([
                'equipment' => $equipmentId ? Equipment::find($equipmentId) : null,
                'area' => $areaId ? Area::find($areaId) : null,
                'department' => $deptId ? Department::find($deptId) : null,
                'description' => $request->description,
            ]);

        $maintenanceRoleId = (! $isDirectAssign && $request->filled('maintenance_role_id'))
            ? $request->maintenance_role_id
            : null;

        $assignedUserId = null;
        if ($isPianificazione && $isDirectAssign) {
            $assignedUserId = $request->assigned_user_id ?: null;
        } elseif (! $isPianificazione && $isDirectAssign && Auth::user()->role === 'admin') {
            $assignedUserId = $request->assigned_user_id ?: null;
        }

        $data = [
            'tipo' => $request->tipo,
            'equipment_id' => $equipmentId,
            'component_id' => $isPianificazione ? ($request->component_id ?: null) : null,
            'maintenance_role_id' => $maintenanceRoleId,
            'area_id' => $areaId,
            'department_id' => $deptId,
            'assigned_user_id' => $assignedUserId,
            'created_by' => Auth::id(),
            'title' => $title,
            'description' => $request->description,
            'scheduled_date' => ($isPianificazione || $isFixedDate) ? ($request->scheduled_date ?: null) : null,
            'scheduled_start_time' => ($isPianificazione || $isFixedDate) ? ($request->scheduled_start_time ?: null) : null,
            'estimated_duration_minutes' => $isPianificazione ? ($request->estimated_duration_minutes ?: null) : null,
            'status' => in_array(Auth::user()->role, ['operator', 'manutentore']) ? 'open' : ($request->status ?? 'planned'),
            'priority' => $priority,
            'notes' => $request->notes,
        ];

        $intervention = Intervention::create($data);

        $tempMediaId = session('intervention_temp_media_id');
        if ($tempMediaId) {
            Media::where('mediable_type', 'TempMedia')
                ->where('mediable_id', $tempMediaId)
                ->update([
                    'mediable_type' => 'App\\Models\\Intervention',
                    'mediable_id' => $intervention->id,
                ]);
            session()->forget('intervention_temp_media_id');
        }

        $result = InterventionObserver::$lastAssignmentResult;
        $redirect = redirect()->route('interventions.index');

        return match ($result['status'] ?? 'skipped') {
            'assigned' => $redirect
                ->with('success', "Ticket creato e assegnato a {$result['user']->name}."),

            'next_shift' => $redirect
                ->with('success', "Ticket creato e assegnato a {$result['user']->name}.")
                ->with('info', "Il manutentore è disponibile dal turno del {$result['shift_start']->translatedFormat('l d/m/Y \a\l\l\e H:i')}."),

            'no_match' => $redirect
                ->with('success', 'Ticket creato.')
                ->with('warning', 'Nessun manutentore disponibile e compatibile trovato. Il ticket rimane non assegnato.'),

            default => $redirect->with('success', 'Ticket creato con successo!'),
        };
    }

    public function show(Intervention $intervention): View
    {
        $intervention->load(['equipment.department.area', 'assignedUser', 'maintenanceRole', 'reports.user', 'creator:id,name']);

        return view('interventions.show', compact('intervention'));
    }

    public function takeCharge(Request $request, Intervention $intervention): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        abort_if($user->role !== 'manutentore', 403);

        if ($intervention->preso_in_carico_at !== null) {
            $message = 'Questo ticket è già stato preso in carico.';

            return $request->wantsJson()
                ? response()->json(['ok' => false, 'message' => $message], 422)
                : back()->with('error', $message);
        }

        if (in_array($intervention->status, ['completed', 'cancelled'])) {
            $message = 'Questo ticket non può essere preso in carico.';

            return $request->wantsJson()
                ? response()->json(['ok' => false, 'message' => $message], 422)
                : back()->with('error', $message);
        }

        $intervention->update([
            'assigned_user_id' => $user->id,
            'status' => 'in_progress',
            'preso_in_carico_at' => now(),
        ]);

        InterventionActivityLogger::log($intervention, InterventionLogActions::TAKEN_IN_CHARGE);

        if ($request->wantsJson()) {
            $intervention->refresh();

            return response()->json([
                'ok' => true,
                'message' => 'Ticket preso in carico con successo!',
                'intervention' => [
                    'id' => $intervention->id,
                    'status' => $intervention->status,
                    'status_label' => 'In corso',
                    'preso_in_carico_at' => $intervention->preso_in_carico_at->format('d/m/Y H:i'),
                ],
                'report_create_url' => route('interventions.reports.create', $intervention),
            ]);
        }

        return redirect()->route('interventions.show', $intervention)
            ->with('success', 'Ticket preso in carico con successo!');
    }

    public function edit(Intervention $intervention): View
    {
        abort_unless($intervention->canBeEditedBy(Auth::user()), 403);
        $equipments = Equipment::where('active', true)->orderBy('name')->get();
        $operators = User::whereIn('role', ['operator', 'manutentore'])->where('active', true)->orderBy('name')->get();
        $areas = Area::where('active', true)->orderBy('name')->get();
        $departments = Department::where('active', true)->orderBy('name')->get();
        $maintenanceRoles = MaintenanceRole::orderBy('name')->get();
        $components = EquipmentComponent::all(['id', 'equipment_id', 'name']);

        return view('interventions.edit', compact('intervention', 'equipments', 'operators', 'areas', 'departments', 'maintenanceRoles', 'components'));
    }

    public function update(InterventionRequest $request, Intervention $intervention): RedirectResponse
    {
        abort_unless($intervention->canBeEditedBy(Auth::user()), 403);

        $isPianificazione = $request->tipo === 'pianificazione';
        $priority = $isPianificazione ? 'low' : ($request->priority ?? 'low');
        $isFixedDate = ! $isPianificazione && $priority === 'fixed_date';
        $assignType = $request->input('assignment_type', 'specializzazione');
        $isDirectAssign = $assignType === 'diretto';

        $previousAssignedUserId = $intervention->assigned_user_id;

        $equipmentId = $isPianificazione ? $request->equipment_id : null;
        $areaId = ! $isPianificazione ? ($request->area_id ?: null) : null;
        $deptId = ! $isPianificazione ? ($request->department_id ?: null) : null;

        $title = $request->filled('title')
            ? $request->title
            : Intervention::generateFallbackTitle([
                'equipment' => $equipmentId ? Equipment::find($equipmentId) : null,
                'area' => $areaId ? Area::find($areaId) : null,
                'department' => $deptId ? Department::find($deptId) : null,
                'description' => $request->description,
            ]);

        $maintenanceRoleId = (! $isDirectAssign && $request->filled('maintenance_role_id'))
            ? $request->maintenance_role_id
            : null;

        $assignedUserId = null;
        if ($isPianificazione && $isDirectAssign) {
            $assignedUserId = $request->assigned_user_id ?: null;
        } elseif (! $isPianificazione && $isDirectAssign && Auth::user()->role === 'admin') {
            $assignedUserId = $request->assigned_user_id ?: null;
        }

        $data = [
            'tipo' => $request->tipo,
            'equipment_id' => $equipmentId,
            'component_id' => $isPianificazione ? ($request->component_id ?: null) : null,
            'maintenance_role_id' => $maintenanceRoleId,
            'area_id' => $areaId,
            'department_id' => $deptId,
            'assigned_user_id' => $assignedUserId,
            'title' => $title,
            'description' => $request->description,
            'scheduled_date' => ($isPianificazione || $isFixedDate) ? ($request->scheduled_date ?: null) : null,
            'scheduled_start_time' => ($isPianificazione || $isFixedDate) ? ($request->scheduled_start_time ?: null) : null,
            'estimated_duration_minutes' => $isPianificazione ? ($request->estimated_duration_minutes ?: null) : null,
            'status' => $request->status ?? 'planned',
            'priority' => $priority,
            'notes' => $request->notes,
        ];

        $intervention->fill($data);
        $changed = array_keys($intervention->getDirty());
        $intervention->save();

        if (in_array('assigned_user_id', $changed, true)
            && $previousAssignedUserId !== $intervention->assigned_user_id) {
            InterventionActivityLogger::log($intervention, InterventionLogActions::MANUALLY_ASSIGNED, [
                'from_user_id' => $previousAssignedUserId,
                'to_user_id' => $intervention->assigned_user_id,
            ]);
            $changed = array_values(array_diff($changed, ['assigned_user_id']));
        }

        if (! empty($changed)) {
            InterventionActivityLogger::log($intervention, InterventionLogActions::UPDATED, [
                'changed_fields' => array_values($changed),
            ]);
        }

        $listRoute = Auth::user()->role === 'manutentore' ? 'm.tickets.index' : 'interventions.index';

        return redirect()->route($listRoute)
            ->with('success', 'Ticket aggiornato con successo!');
    }

    public function destroy(Intervention $intervention): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($intervention->canBeDeletedBy($user), 403);

        InterventionActivityLogger::log($intervention, InterventionLogActions::CANCELLED, [
            'reason' => $user->role === 'admin' ? 'deleted_by_admin' : 'deleted_by_creator',
        ]);

        $intervention->delete();

        $listRoute = $user->role === 'manutentore' ? 'm.tickets.index' : 'interventions.index';

        return redirect()->route($listRoute)
            ->with('success', 'Ticket eliminato con successo!');
    }

    public function quickStore(Request $request): RedirectResponse
    {
        abort_if(! in_array(Auth::user()->role, ['operator', 'manutentore']), 403);

        $request->validate([
            'area_id' => 'required|exists:areas,id',
            'department_id' => 'required|exists:departments,id',
            'priority' => 'required|in:low,medium,high',
            'description' => 'nullable|string',
        ], [
            'area_id.required' => 'Seleziona un\'area.',
            'department_id.required' => 'Seleziona una zona.',
            'priority.required' => 'Seleziona la priorità.',
            'priority.in' => 'La priorità selezionata non è valida.',
        ]);

        $area = \App\Models\Area::find($request->area_id);
        $department = \App\Models\Department::find($request->department_id);

        Intervention::create([
            'tipo' => 'ordinario',
            'area_id' => $request->area_id,
            'department_id' => $request->department_id,
            'assigned_user_id' => Auth::id(),
            'created_by' => Auth::id(),
            'title' => Intervention::generateFallbackTitle([
                'area' => $area,
                'department' => $department,
                'description' => $request->description,
            ]),
            'description' => $request->description,
            'status' => 'open',
            'priority' => $request->priority,
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Ticket ordinario aperto con successo!');
    }

    public function equipmentPlanning(Equipment $equipment): JsonResponse
    {
        return response()->json([
            'next_maintenance_date' => $equipment->next_maintenance_date?->format('Y-m-d'),
            'maintenance_frequency_days' => $equipment->maintenance_frequency_days,
        ]);
    }

    public function calendar(): View
    {
        $user = Auth::user();

        // Recupera interventi per i prossimi 20 giorni
        $query = Intervention::with(['equipment', 'assignedUser'])
            ->whereBetween('scheduled_date', [now(), now()->addDays(20)])
            ->orderBy('scheduled_date', 'asc')
            ->orderBy('scheduled_start_time', 'asc');

        // Operatori e manutentori vedono solo i loro interventi
        if (in_array($user->role, ['operator', 'manutentore'])) {
            $query->where('assigned_user_id', $user->id);
        }

        $interventions = $query->get();

        // Organizza per settimane
        $weeklyInterventions = [];
        foreach ($interventions as $intervention) {
            $weekNumber = $intervention->scheduled_date->week;
            $year = $intervention->scheduled_date->year;
            $weekKey = $year.'-W'.$weekNumber;

            if (! isset($weeklyInterventions[$weekKey])) {
                $weeklyInterventions[$weekKey] = [
                    'start' => $intervention->scheduled_date->startOfWeek(),
                    'end' => $intervention->scheduled_date->endOfWeek(),
                    'days' => [],
                ];
            }

            $dayKey = $intervention->scheduled_date->format('Y-m-d');
            if (! isset($weeklyInterventions[$weekKey]['days'][$dayKey])) {
                $weeklyInterventions[$weekKey]['days'][$dayKey] = [
                    'date' => $intervention->scheduled_date,
                    'interventions' => [],
                ];
            }

            $weeklyInterventions[$weekKey]['days'][$dayKey]['interventions'][] = $intervention;
        }

        return view('interventions.calendar', compact('weeklyInterventions'));
    }

    public function calendarData(): JsonResponse
    {
        $user = Auth::user();

        // Operatori e manutentori vedono solo i loro interventi
        // Admin vede tutti gli interventi
        $query = Intervention::with(['equipment', 'assignedUser']);

        if (in_array($user->role, ['operator', 'manutentore'])) {
            $query->where('assigned_user_id', $user->id);
        }

        $interventions = $query->whereNotNull('scheduled_date')->get();

        $events = $interventions->map(function ($intervention) {
            // Definizione colori per priorità
            $priorityColors = [
                'low' => '#6c757d',  // grigio
                'medium' => '#0dcaf0',  // info
                'high' => '#ffc107',  // warning
                'fixed_date' => '#6f42c1',  // viola
            ];

            // Definizione colori per stato
            $statusColors = [
                'planned' => '#0dcaf0',      // info
                'in_progress' => '#ffc107',  // warning
                'completed' => '#198754',    // success
                'cancelled' => '#6c757d',     // secondary
            ];

            // Calcola ora fine se disponibile
            $startTime = $intervention->scheduled_start_time ?? '09:00:00';
            $start = $intervention->scheduled_date->format('Y-m-d').'T'.substr($startTime, 0, 5);

            $end = null;
            if ($intervention->scheduled_start_time && $intervention->estimated_duration_minutes) {
                $startDateTime = \Carbon\Carbon::parse($intervention->scheduled_date->format('Y-m-d').' '.$startTime);
                $endDateTime = $startDateTime->addMinutes($intervention->estimated_duration_minutes);
                $end = $endDateTime->format('Y-m-d\TH:i');
            }

            return [
                'id' => $intervention->id,
                'title' => $intervention->title,
                'start' => $start,
                'end' => $end,
                'backgroundColor' => $statusColors[$intervention->status] ?? '#0dcaf0',
                'borderColor' => $priorityColors[$intervention->priority] ?? '#6c757d',
                'extendedProps' => [
                    'equipment' => $intervention->equipment
                        ? $intervention->equipment->name
                        : (($intervention->area->name ?? '').' / '.($intervention->department->name ?? '')),
                    'operator' => $intervention->assignedUser->name,
                    'status' => $intervention->status,
                    'priority' => $intervention->priority,
                    'description' => $intervention->description,
                    'notes' => $intervention->notes,
                    'duration' => $intervention->estimated_duration_minutes,
                    'showUrl' => route('interventions.show', $intervention),
                    'reportUrl' => route('interventions.reports.create', $intervention),
                ],
            ];
        });

        return response()->json($events);
    }
}
