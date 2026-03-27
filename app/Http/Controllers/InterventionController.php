<?php

namespace App\Http\Controllers;

use App\Http\Requests\InterventionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Intervention;
use App\Models\Area;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

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
        abort_if(Auth::user()->role === 'manutentore', 403);
        $equipments = Equipment::where('active', true)->orderBy('name')->get();
        $operators = User::whereIn('role', ['operator', 'manutentore'])->orderBy('name')->get();
        $areas = Area::where('active', true)->orderBy('name')->get();
        $departments = Department::where('active', true)->orderBy('name')->get();
        return view('interventions.create', compact('equipments', 'operators', 'areas', 'departments'));
    }

    public function store(InterventionRequest $request): RedirectResponse
    {
        $isPianificazione = $request->tipo === 'pianificazione';

        $data = [
            'tipo'                       => $request->tipo,
            'equipment_id'               => $isPianificazione ? $request->equipment_id : null,
            'area_id'                    => !$isPianificazione ? $request->area_id : null,
            'department_id'              => !$isPianificazione ? $request->department_id : null,
            'assigned_user_id'           => $request->assigned_user_id,
            'title'                      => $request->title,
            'description'                => $request->description,
            'scheduled_date'             => $request->scheduled_date ?: null,
            'scheduled_start_time'       => $isPianificazione ? $request->scheduled_start_time : null,
            'estimated_duration_minutes' => $isPianificazione ? $request->estimated_duration_minutes : null,
            'status'                     => $request->status ?? 'planned',
            'priority'                   => $request->priority ?? 'medium',
            'notes'                      => $request->notes,
        ];

        Intervention::create($data);

        return redirect()->route('interventions.index')
            ->with('success', 'Intervento creato con successo!');
    }

    public function show(Intervention $intervention): View
    {
        $intervention->load(['equipment.department.area', 'assignedUser', 'reports.user']);
        return view('interventions.show', compact('intervention'));
    }

    public function edit(Intervention $intervention): View
    {
        abort_if(Auth::user()->role === 'manutentore', 403);
        $equipments = Equipment::where('active', true)->orderBy('name')->get();
        $operators = User::whereIn('role', ['operator', 'manutentore'])->orderBy('name')->get();
        $areas = Area::where('active', true)->orderBy('name')->get();
        $departments = Department::where('active', true)->orderBy('name')->get();
        return view('interventions.edit', compact('intervention', 'equipments', 'operators', 'areas', 'departments'));
    }

    public function update(InterventionRequest $request, Intervention $intervention): RedirectResponse
    {
        $isPianificazione = $request->tipo === 'pianificazione';

        $data = [
            'tipo'                       => $request->tipo,
            'equipment_id'               => $isPianificazione ? $request->equipment_id : null,
            'area_id'                    => !$isPianificazione ? $request->area_id : null,
            'department_id'              => !$isPianificazione ? $request->department_id : null,
            'assigned_user_id'           => $request->assigned_user_id,
            'title'                      => $request->title,
            'description'                => $request->description,
            'scheduled_date'             => $request->scheduled_date ?: null,
            'scheduled_start_time'       => $isPianificazione ? $request->scheduled_start_time : null,
            'estimated_duration_minutes' => $isPianificazione ? $request->estimated_duration_minutes : null,
            'status'                     => $request->status ?? 'planned',
            'priority'                   => $request->priority ?? 'medium',
            'notes'                      => $request->notes,
        ];

        $intervention->update($data);

        return redirect()->route('interventions.index')
            ->with('success', 'Intervento aggiornato con successo!');
    }

    public function destroy(Intervention $intervention): RedirectResponse
    {
        $intervention->delete();

        return redirect()->route('interventions.index')
            ->with('success', 'Intervento eliminato con successo!');
    }

    public function quickStore(Request $request): RedirectResponse
    {
        abort_if(!in_array(Auth::user()->role, ['operator', 'manutentore']), 403);

        $request->validate([
            'area_id'       => 'required|exists:areas,id',
            'department_id' => 'required|exists:departments,id',
            'description'   => 'nullable|string',
        ], [
            'area_id.required'       => 'Seleziona un\'area.',
            'department_id.required' => 'Seleziona una zona.',
        ]);

        $area       = \App\Models\Area::find($request->area_id);
        $department = \App\Models\Department::find($request->department_id);

        Intervention::create([
            'tipo'             => 'ordinario',
            'area_id'          => $request->area_id,
            'department_id'    => $request->department_id,
            'assigned_user_id' => Auth::id(),
            'title'            => 'Intervento - ' . $area->name . ' / ' . $department->name,
            'description'      => $request->description,
            'scheduled_date'   => today(),
            'status'           => 'in_progress',
            'priority'         => 'medium',
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Intervento ordinario aperto con successo!');
    }

    public function equipmentPlanning(Equipment $equipment): JsonResponse
    {
        return response()->json([
            'next_maintenance_date'       => $equipment->next_maintenance_date?->format('Y-m-d'),
            'maintenance_frequency_days'  => $equipment->maintenance_frequency_days,
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
            $weekKey = $year . '-W' . $weekNumber;

            if (!isset($weeklyInterventions[$weekKey])) {
                $weeklyInterventions[$weekKey] = [
                    'start' => $intervention->scheduled_date->startOfWeek(),
                    'end' => $intervention->scheduled_date->endOfWeek(),
                    'days' => []
                ];
            }

            $dayKey = $intervention->scheduled_date->format('Y-m-d');
            if (!isset($weeklyInterventions[$weekKey]['days'][$dayKey])) {
                $weeklyInterventions[$weekKey]['days'][$dayKey] = [
                    'date' => $intervention->scheduled_date,
                    'interventions' => []
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
                'low' => '#6c757d',      // grigio
                'medium' => '#0dcaf0',   // info
                'high' => '#ffc107',     // warning
                'critical' => '#dc3545'  // danger
            ];

            // Definizione colori per stato
            $statusColors = [
                'planned' => '#0dcaf0',      // info
                'in_progress' => '#ffc107',  // warning
                'completed' => '#198754',    // success
                'cancelled' => '#6c757d'     // secondary
            ];

            // Calcola ora fine se disponibile
            $startTime = $intervention->scheduled_start_time ?? '09:00:00';
            $start = $intervention->scheduled_date->format('Y-m-d') . 'T' . substr($startTime, 0, 5);

            $end = null;
            if ($intervention->scheduled_start_time && $intervention->estimated_duration_minutes) {
                $startDateTime = \Carbon\Carbon::parse($intervention->scheduled_date->format('Y-m-d') . ' ' . $startTime);
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
                    'equipment'   => $intervention->equipment
                        ? $intervention->equipment->name
                        : (($intervention->area->name ?? '') . ' / ' . ($intervention->department->name ?? '')),
                    'operator'    => $intervention->assignedUser->name,
                    'status'      => $intervention->status,
                    'priority'    => $intervention->priority,
                    'description' => $intervention->description,
                    'notes'       => $intervention->notes,
                    'duration'    => $intervention->estimated_duration_minutes,
                    'showUrl'     => route('interventions.show', $intervention),
                    'reportUrl'   => route('interventions.reports.create', $intervention),
                ]
            ];
        });

        return response()->json($events);
    }
}
