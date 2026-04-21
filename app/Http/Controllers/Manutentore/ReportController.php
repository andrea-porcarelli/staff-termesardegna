<?php

namespace App\Http\Controllers\Manutentore;

use App\Http\Controllers\Controller;
use App\Models\Intervention;
use App\Models\InterventionCollaboration;
use App\Models\Media;
use App\Models\Report;
use App\Models\User;
use App\Services\InterventionActivityLogger;
use App\Support\InterventionLogActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function store(Request $request, Intervention $intervention): JsonResponse
    {
        $user = Auth::user();

        if (! $this->canReportOn($intervention, $user)) {
            return response()->json([
                'ok' => false,
                'message' => 'Non puoi scrivere un rapportino per questo ticket.',
            ], 403);
        }

        if ($this->userHasFinalReport($intervention, $user)) {
            return response()->json([
                'ok' => false,
                'message' => 'Hai già terminato il tuo lavoro su questo ticket.',
            ], 422);
        }

        $data = $request->validate([
            'hours' => ['required', 'integer', 'min:0', 'max:24'],
            'minutes' => ['required', 'integer', 'min:0', 'max:59'],
            'activities' => ['required', 'string', 'max:4000'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'is_final' => ['required', 'boolean'],
            'next_work_date' => ['nullable', 'date', 'after_or_equal:today', 'required_if:is_final,false'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,gif,zip'],
        ], [
            'hours.required' => 'Indica le ore impiegate.',
            'minutes.required' => 'Indica i minuti impiegati.',
            'is_final.required' => 'Indica se hai terminato il lavoro.',
            'next_work_date.required_if' => 'Indica quando tornerai a lavorare sul ticket.',
            'next_work_date.after_or_equal' => 'La data deve essere oggi o successiva.',
            'files.*.mimes' => 'Sono accettati solo PDF, immagini (JPG/PNG/GIF) e ZIP.',
            'files.*.max' => 'Il file non può superare 10 MB.',
        ]);

        $totalMinutes = ((int) $data['hours']) * 60 + (int) $data['minutes'];
        if ($totalMinutes <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Il tempo impiegato deve essere maggiore di zero.',
                'errors' => ['hours' => ['Il tempo impiegato deve essere maggiore di zero.']],
            ], 422);
        }

        $isFinal = (bool) $data['is_final'];

        $report = Report::create([
            'intervention_id' => $intervention->id,
            'user_id' => $user->id,
            'report_date' => now()->toDateString(),
            'start_time' => null,
            'end_time' => null,
            'duration_minutes' => $totalMinutes,
            'activities' => $data['activities'],
            'notes' => $data['notes'] ?? null,
            'status' => $isFinal ? 'completed' : 'draft',
            'is_final' => $isFinal,
            'next_work_date' => $isFinal ? null : ($data['next_work_date'] ?? null),
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('media', 'public');
                Media::create([
                    'mediable_type' => Report::class,
                    'mediable_id' => $report->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        InterventionActivityLogger::log($intervention, InterventionLogActions::REPORT_CREATED, [
            'report_id' => $report->id,
            'report_status' => $report->status,
            'is_final' => $isFinal,
            'next_work_date' => $report->next_work_date?->toDateString(),
        ]);

        $autoClosed = false;
        if ($isFinal) {
            $intervention->load(['collaborations', 'reports']);
            if ($this->allRequiredUsersFinal($intervention) && ! in_array($intervention->status, ['completed', 'cancelled'], true)) {
                $intervention->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'suspended_until' => null,
                ]);
                InterventionActivityLogger::log($intervention, InterventionLogActions::COMPLETED, [
                    'auto_closed' => true,
                    'closed_by_report_id' => $report->id,
                ]);
                $autoClosed = true;
            }
        }

        return response()->json([
            'ok' => true,
            'message' => $autoClosed
                ? 'Rapportino salvato. Ticket chiuso.'
                : ($isFinal ? 'Rapportino salvato. Lavoro terminato.' : 'Rapportino salvato.'),
            'report_id' => $report->id,
            'is_final' => $isFinal,
            'auto_closed' => $autoClosed,
        ]);
    }

    private function canReportOn(Intervention $intervention, User $user): bool
    {
        if ($user->role === 'admin') {
            return true;
        }
        if ($intervention->assigned_user_id === $user->id) {
            return true;
        }

        return $intervention->collaborations()
            ->where('user_id', $user->id)
            ->where('status', InterventionCollaboration::STATUS_ACCEPTED)
            ->exists();
    }

    private function userHasFinalReport(Intervention $intervention, User $user): bool
    {
        return Report::where('intervention_id', $intervention->id)
            ->where('user_id', $user->id)
            ->where('is_final', true)
            ->exists();
    }

    /**
     * True se assegnatario + collaboratori accettati hanno tutti almeno
     * un rapportino con is_final=true.
     */
    private function allRequiredUsersFinal(Intervention $intervention): bool
    {
        $requiredIds = collect([$intervention->assigned_user_id])
            ->merge(
                $intervention->collaborations
                    ->where('status', InterventionCollaboration::STATUS_ACCEPTED)
                    ->pluck('user_id')
            )
            ->filter()
            ->unique()
            ->values();

        if ($requiredIds->isEmpty()) {
            return false;
        }

        $finalIds = $intervention->reports
            ->where('is_final', true)
            ->pluck('user_id')
            ->unique();

        return $requiredIds->diff($finalIds)->isEmpty();
    }
}
