<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Models\Intervention;
use App\Models\Media;
use App\Models\Report;
use App\Models\User;
use App\Services\InterventionActivityLogger;
use App\Support\InterventionLogActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_if(! in_array(Auth::user()->role, ['admin', 'operator']), 403);

        $query = Report::with(['user', 'intervention.equipment', 'intervention.area', 'intervention.department'])
            ->orderBy('report_date', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('date_from')) {
            $query->where('report_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('report_date', '<=', $request->date_to);
        }
        if ($request->filled('q')) {
            $like = '%'.$request->q.'%';
            $query->where(function ($qq) use ($like) {
                $qq->where('activities', 'like', $like)
                    ->orWhere('notes', 'like', $like)
                    ->orWhere('report_date', 'like', $like)
                    ->orWhereHas('intervention', function ($i) use ($like) {
                        $i->where('title', 'like', $like)
                            ->orWhere('description', 'like', $like);
                    });
            });
        }

        $reports = $query->paginate(25)->withQueryString();
        $users = User::whereIn('role', ['operator', 'manutentore'])->orderBy('name')->get();

        return view('reports.index', compact('reports', 'users'));
    }

    public function create(Intervention $intervention): View
    {
        // Verifica che l'utente sia autorizzato
        if (! $this->canReportOn($intervention)) {
            abort(403);
        }

        // Genera un ID temporaneo unico per questa sessione di upload
        $tempMediaId = 'temp_'.uniqid().'_'.Auth::id();
        session(['temp_media_id' => $tempMediaId]);

        $intervention->load(['equipment.components', 'equipment.department.area', 'area', 'department', 'assignedUser']);

        return view('reports.create', compact('intervention', 'tempMediaId'));
    }

    public function store(ReportRequest $request, Intervention $intervention): RedirectResponse
    {
        // Verifica che l'utente sia autorizzato
        if (! $this->canReportOn($intervention)) {
            abort(403);
        }

        // Crea il rapportino
        $report = Report::create([
            'intervention_id' => $intervention->id,
            'user_id' => Auth::id(),
            'report_date' => $request->report_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'activities' => $request->activities,
            'notes' => $request->notes,
            'status' => $request->status ?? 'draft',
        ]);

        // Trasferisci i media temporanei al rapportino appena creato
        $tempMediaId = session('temp_media_id');
        if ($tempMediaId) {
            Media::where('mediable_type', 'TempMedia')
                ->where('mediable_id', $tempMediaId)
                ->update([
                    'mediable_type' => 'App\\Models\\Report',
                    'mediable_id' => $report->id,
                ]);

            // Rimuovi l'ID temporaneo dalla sessione
            session()->forget('temp_media_id');
        }

        InterventionActivityLogger::log($intervention, InterventionLogActions::REPORT_CREATED, [
            'report_id' => $report->id,
            'report_status' => $report->status,
        ]);

        return redirect()->route('interventions.show', $intervention)
            ->with('success', 'Rapportino creato con successo!');
    }

    public function edit(Intervention $intervention, Report $report): View
    {
        // Verifica che il report appartenga all'intervento
        if ($report->intervention_id !== $intervention->id) {
            abort(404);
        }

        // Verifica che l'utente sia autorizzato (operatore del report o admin)
        if (Auth::user()->role !== 'admin' && $report->user_id !== Auth::id()) {
            abort(403);
        }

        return view('reports.edit', compact('intervention', 'report'));
    }

    public function update(UpdateReportRequest $request, Intervention $intervention, Report $report): RedirectResponse
    {
        // Verifica che il report appartenga all'intervento
        if ($report->intervention_id !== $intervention->id) {
            abort(404);
        }

        // Verifica che l'utente sia autorizzato
        if (Auth::user()->role !== 'admin' && $report->user_id !== Auth::id()) {
            abort(403);
        }

        $data = [
            'report_date' => $request->report_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'activities' => $request->activities,
            'notes' => $request->notes,
            'status' => $request->status ?? 'draft',
        ];

        // Solo admin/operator possono impostare "chiuso"
        if (($data['status'] ?? '') === 'chiuso' && ! in_array(Auth::user()->role, ['admin', 'operator'])) {
            $data['status'] = 'completed';
        }

        $report->update($data);

        InterventionActivityLogger::log($intervention, InterventionLogActions::REPORT_UPDATED, [
            'report_id' => $report->id,
            'report_status' => $report->status,
        ]);

        if (($data['status'] ?? '') === 'chiuso') {
            InterventionActivityLogger::log($intervention, InterventionLogActions::REPORT_CLOSED, [
                'report_id' => $report->id,
            ]);

            $intervention->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            InterventionActivityLogger::log($intervention, InterventionLogActions::COMPLETED);
        }

        return redirect()->route('interventions.show', $intervention)
            ->with('success', 'Rapportino aggiornato con successo!');
    }

    public function destroy(Intervention $intervention, Report $report): RedirectResponse
    {
        // Verifica che il report appartenga all'intervento
        if ($report->intervention_id !== $intervention->id) {
            abort(404);
        }

        // Verifica che l'utente sia autorizzato
        if (Auth::user()->role !== 'admin' && $report->user_id !== Auth::id()) {
            abort(403);
        }

        $reportId = $report->id;
        $report->delete();

        InterventionActivityLogger::log($intervention, InterventionLogActions::REPORT_DELETED, [
            'report_id' => $reportId,
        ]);

        return redirect()->route('interventions.show', $intervention)
            ->with('success', 'Rapportino eliminato con successo!');
    }

    private function canReportOn(Intervention $intervention): bool
    {
        $user = Auth::user();
        if ($user->role === 'admin') {
            return true;
        }
        if ($intervention->assigned_user_id === $user->id) {
            return true;
        }

        return $intervention->collaborations()
            ->where('user_id', $user->id)
            ->where('status', \App\Models\InterventionCollaboration::STATUS_ACCEPTED)
            ->exists();
    }

    public function show(Report $report)
    {
        // Carica le relazioni necessarie
        $report->load(['user', 'media', 'intervention.equipment', 'intervention.area', 'intervention.department', 'intervention.assignedUser']);

        $intervention = $report->intervention;

        $interventionPayload = null;
        if ($intervention) {
            if ($intervention->equipment) {
                $destination = $intervention->equipment->name.' ('.$intervention->equipment->code.')';
                $location = ($intervention->equipment->department->area->name ?? '').' / '.($intervention->equipment->department->name ?? '');
            } else {
                $destination = ($intervention->area->name ?? '-').' / '.($intervention->department->name ?? '-');
                $location = null;
            }

            $interventionPayload = [
                'title' => $intervention->title,
                'description' => $intervention->description,
                'notes' => $intervention->notes,
                'destination' => $destination,
                'location' => $location,
                'scheduled_date' => $intervention->scheduled_date ? $intervention->scheduled_date->format('d/m/Y') : null,
                'scheduled_time' => $intervention->scheduled_start_time ? substr($intervention->scheduled_start_time, 0, 5) : null,
                'priority' => $intervention->priority,
                'priority_label' => ['low' => 'Bassa', 'medium' => 'Media', 'high' => 'Alta', 'fixed_date' => 'Data fissa'][$intervention->priority] ?? $intervention->priority,
            ];
        }

        // Formatta i dati per la risposta JSON
        $data = [
            'id' => $report->id,
            'report_date' => $report->report_date ? $report->report_date->format('d/m/Y') : null,
            'time_range' => null,
            'user_name' => $report->user->name,
            'status' => $report->status,
            'status_label' => ['draft' => 'Bozza', 'completed' => 'Completato', 'chiuso' => 'Chiuso'][$report->status] ?? $report->status,
            'activities' => $report->activities,
            'notes' => $report->notes,
            'media' => [],
            'intervention' => $interventionPayload,
        ];

        // Formatta orario
        if ($report->start_time && $report->end_time) {
            $data['time_range'] = substr($report->start_time, 0, 5).' - '.substr($report->end_time, 0, 5);
        }

        // Formatta media
        foreach ($report->media as $media) {
            $data['media'][] = [
                'id' => $media->id,
                'file_name' => $media->file_name,
                'url' => Storage::url($media->file_path),
                'is_image' => str_contains($media->file_type, 'image'),
                'file_type' => $media->file_type,
                'file_size' => $media->file_size_formatted,
                'created_at' => $media->created_at->format('d/m/Y H:i'),
                'description' => $media->description,
            ];
        }

        return response()->json($data);
    }
}
