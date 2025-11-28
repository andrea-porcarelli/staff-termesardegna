<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Http\Requests\UpdateReportRequest;
use Illuminate\Http\RedirectResponse;
use App\Models\Report;
use App\Models\Media;
use App\Models\Intervention;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function create(Intervention $intervention): View
    {
        // Verifica che l'utente sia autorizzato
        if (Auth::user()->role !== 'admin' && $intervention->assigned_user_id !== Auth::id()) {
            abort(403);
        }

        // Genera un ID temporaneo unico per questa sessione di upload
        $tempMediaId = 'temp_' . uniqid() . '_' . Auth::id();
        session(['temp_media_id' => $tempMediaId]);

        return view('reports.create', compact('intervention', 'tempMediaId'));
    }

    public function store(ReportRequest $request, Intervention $intervention): RedirectResponse
    {
        // Verifica che l'utente sia autorizzato
        if (Auth::user()->role !== 'admin' && $intervention->assigned_user_id !== Auth::id()) {
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

        $report->update($data);

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

        $report->delete();

        return redirect()->route('interventions.show', $intervention)
            ->with('success', 'Rapportino eliminato con successo!');
    }

    public function show(Report $report)
    {
        // Carica le relazioni necessarie
        $report->load(['user', 'media']);

        // Formatta i dati per la risposta JSON
        $data = [
            'id' => $report->id,
            'report_date' => $report->report_date->format('d/m/Y'),
            'time_range' => null,
            'user_name' => $report->user->name,
            'status' => $report->status,
            'status_label' => $report->status === 'completed' ? 'Completato' : 'Bozza',
            'activities' => $report->activities,
            'notes' => $report->notes,
            'media' => []
        ];

        // Formatta orario
        if ($report->start_time && $report->end_time) {
            $data['time_range'] = substr($report->start_time, 0, 5) . ' - ' . substr($report->end_time, 0, 5);
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
                'description' => $media->description
            ];
        }

        return response()->json($data);
    }
}
