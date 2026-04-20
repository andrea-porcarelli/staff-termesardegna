<?php

namespace App\Http\Controllers;

use App\Http\Requests\InterventionRequest;
use App\Models\Area;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentComponent;
use App\Models\Intervention;
use App\Models\MaintenanceRole;
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
        abort_if(Auth::user()->role === 'manutentore', 403);
        $query = Intervention::with(['equipment.department.area', 'area', 'department', 'assignedUser'])
            ->orderBy('scheduled_date', 'desc');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $interventions = $query->get();

        return view('interventions.index', compact('interventions'));
    }

    public function create(): View
    {
        $user = Auth::user();
        $equipments = Equipment::where('active', true)->orderBy('name')->get();
        $operators = User::whereIn('role', ['operator', 'manutentore'])->orderBy('name')->get();
        $areas = Area::where('active', true)->orderBy('name')->get();
        $departments = Department::where('active', true)->orderBy('name')->get();
        $maintenanceRoles = MaintenanceRole::orderBy('name')->get();
        $components = EquipmentComponent::all(['id', 'equipment_id', 'name']);

        $userRole = $user->role;
        $userDepartmentIds = $user->departments->pluck('id')->toArray();
        $userAreaId = $user->departments->first()?->area_id;

        return view('interventions.create', compact(
            'equipments', 'operators', 'areas', 'departments', 'maintenanceRoles', 'components',
            'userRole', 'userDepartmentIds', 'userAreaId'
        ));
    }

    public function store(InterventionRequest $request): RedirectResponse
    {
        $isPianificazione = $request->tipo === 'pianificazione';
        $priority = $isPianificazione ? 'low' : ($request->priority ?? 'low');
        $isFixedDate = ! $isPianificazione && $priority === 'fixed_date';

        $data = [
            'tipo' => $request->tipo,
            'equipment_id' => $isPianificazione ? $request->equipment_id : null,
            'component_id' => $isPianificazione ? ($request->component_id ?: null) : null,
            'maintenance_role_id' => $request->filled('maintenance_role_id') ? $request->maintenance_role_id : null,
            'area_id' => ! $isPianificazione ? $request->area_id : null,
            'department_id' => ! $isPianificazione ? $request->department_id : null,
            'assigned_user_id' => $isPianificazione ? ($request->assigned_user_id ?: null) : null,
            'title' => $request->title,
            'description' => $request->description,
            'scheduled_date' => ($isPianificazione || $isFixedDate) ? ($request->scheduled_date ?: null) : null,
            'scheduled_start_time' => ($isPianificazione || $isFixedDate) ? ($request->scheduled_start_time ?: null) : null,
            'estimated_duration_minutes' => $isPianificazione ? ($request->estimated_duration_minutes ?: null) : null,
            'status' => in_array(Auth::user()->role, ['operator', 'manutentore']) ? 'open' : ($request->status ?? 'planned'),
            'priority' => $priority,
            'notes' => $request->notes,
        ];

        Intervention::create($data);

        $result = InterventionObserver::$lastAssignmentResult;
        $redirect = redirect()->route('interventions.index');

        return match ($result['status'] ?? 'skipped') {
            'assigned' => $redirect
                ->with('success', "Intervento creato e assegnato a {$result['user']->name}."),

            'next_shift' => $redirect
                ->with('success', "Intervento creato e assegnato a {$result['user']->name}.")
                ->with('info', "Il manutentore è disponibile dal turno del {$result['shift_start']->translatedFormat('l d/m/Y \a\l\l\e H:i')}."),

            'no_match' => $redirect
                ->with('success', 'Intervento creato.')
                ->with('warning', 'Nessun manutentore disponibile e compatibile trovato. L\'intervento rimane non assegnato.'),

            default => $redirect->with('success', 'Intervento creato con successo!'),
        };
    }

    public function show(Intervention $intervention): View
    {
        $intervention->load(['equipment.department.area', 'assignedUser', 'maintenanceRole', 'reports.user']);

        return view('interventions.show', compact('intervention'));
    }

    public function takeCharge(Request $request, Intervention $intervention): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        abort_if($user->role !== 'manutentore', 403);

        if ($intervention->preso_in_carico_at !== null) {
            $message = 'Questo intervento è già stato preso in carico.';

            return $request->wantsJson()
                ? response()->json(['ok' => false, 'message' => $message], 422)
                : back()->with('error', $message);
        }

        if (in_array($intervention->status, ['completed', 'cancelled'])) {
            $message = 'Questo intervento non può essere preso in carico.';

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
                'message' => 'Intervento preso in carico con successo!',
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
            ->with('success', 'Intervento preso in carico con successo!');
    }

    public function edit(Intervention $intervention): View
    {
        abort_if(Auth::user()->role === 'manutentore', 403);
        $equipments = Equipment::where('active', true)->orderBy('name')->get();
        $operators = User::whereIn('role', ['operator', 'manutentore'])->orderBy('name')->get();
        $areas = Area::where('active', true)->orderBy('name')->get();
        $departments = Department::where('active', true)->orderBy('name')->get();
        $maintenanceRoles = MaintenanceRole::orderBy('name')->get();
        $components = EquipmentComponent::all(['id', 'equipment_id', 'name']);

        return view('interventions.edit', compact('intervention', 'equipments', 'operators', 'areas', 'departments', 'maintenanceRoles', 'components'));
    }

    public function update(InterventionRequest $request, Intervention $intervention): RedirectResponse
    {
        $isPianificazione = $request->tipo === 'pianificazione';
        $priority = $isPianificazione ? 'low' : ($request->priority ?? 'low');
        $isFixedDate = ! $isPianificazione && $priority === 'fixed_date';

        $previousAssignedUserId = $intervention->assigned_user_id;

        $data = [
            'tipo' => $request->tipo,
            'equipment_id' => $isPianificazione ? $request->equipment_id : null,
            'component_id' => $isPianificazione ? ($request->component_id ?: null) : null,
            'maintenance_role_id' => $request->filled('maintenance_role_id') ? $request->maintenance_role_id : null,
            'area_id' => ! $isPianificazione ? $request->area_id : null,
            'department_id' => ! $isPianificazione ? $request->department_id : null,
            'assigned_user_id' => $isPianificazione ? ($request->assigned_user_id ?: null) : null,
            'title' => $request->title,
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

        return redirect()->route('interventions.index')
            ->with('success', 'Intervento aggiornato con successo!');
    }

    public function destroy(Intervention $intervention): RedirectResponse
    {
        InterventionActivityLogger::log($intervention, InterventionLogActions::CANCELLED, [
            'reason' => 'deleted_by_admin',
        ]);

        $intervention->delete();

        return redirect()->route('interventions.index')
            ->with('success', 'Intervento eliminato con successo!');
    }

    public function quickStore(Request $request): RedirectResponse
    {
        abort_if(! in_array(Auth::user()->role, ['operator', 'manutentore']), 403);

        $request->validate([
            'area_id' => 'required|exists:areas,id',
            'department_id' => 'required|exists:departments,id',
            'description' => 'nullable|string',
        ], [
            'area_id.required' => 'Seleziona un\'area.',
            'department_id.required' => 'Seleziona una zona.',
        ]);

        $area = \App\Models\Area::find($request->area_id);
        $department = \App\Models\Department::find($request->department_id);

        Intervention::create([
            'tipo' => 'ordinario',
            'area_id' => $request->area_id,
            'department_id' => $request->department_id,
            'assigned_user_id' => Auth::id(),
            'title' => 'Intervento - '.$area->name.' / '.$department->name,
            'description' => $request->description,
            'scheduled_date' => today(),
            'status' => 'open',
            'priority' => 'low',
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Intervento ordinario aperto con successo!');
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
