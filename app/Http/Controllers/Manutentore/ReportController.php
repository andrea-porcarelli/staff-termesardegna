<?php

namespace App\Http\Controllers\Manutentore;

use App\Http\Controllers\Controller;
use App\Models\Intervention;
use App\Models\InterventionCollaboration;
use App\Models\Media;
use App\Models\Report;
use App\Models\User;
use App\Notifications\CollaboratorReportSubmittedNotification;
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

        $alreadyWritten = Report::where('intervention_id', $intervention->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyWritten) {
            return response()->json([
                'ok' => false,
                'message' => 'Hai già scritto un rapportino per questo ticket.',
            ], 422);
        }

        $data = $request->validate([
            'hours' => ['required', 'integer', 'min:0', 'max:24'],
            'minutes' => ['required', 'integer', 'min:0', 'max:59'],
            'activities' => ['required', 'string', 'max:4000'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'status' => ['nullable', 'in:draft,completed'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,gif,zip'],
        ], [
            'hours.required' => 'Indica le ore impiegate.',
            'minutes.required' => 'Indica i minuti impiegati.',
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

        $report = Report::create([
            'intervention_id' => $intervention->id,
            'user_id' => $user->id,
            'report_date' => now()->toDateString(),
            'start_time' => null,
            'end_time' => null,
            'duration_minutes' => $totalMinutes,
            'activities' => $data['activities'],
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'draft',
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
        ]);

        $isAssignee = $intervention->assigned_user_id === $user->id;

        if (! $isAssignee && $intervention->assignedUser) {
            $intervention->assignedUser->notify(
                new CollaboratorReportSubmittedNotification($intervention, $report, $user)
            );
        }

        $intervention->load(['collaborations', 'reports']);
        $canClose = $this->allRequiredReportsCompleted($intervention);

        return response()->json([
            'ok' => true,
            'message' => 'Rapportino salvato.',
            'report_id' => $report->id,
            'report_status' => $report->status,
            'can_close_ticket' => $canClose,
            'should_prompt_close' => $isAssignee,
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

    /**
     * True se assegnatario + collaboratori accettati hanno tutti
     * scritto un rapportino con status=completed.
     */
    private function allRequiredReportsCompleted(Intervention $intervention): bool
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

        $completedIds = $intervention->reports
            ->where('status', 'completed')
            ->pluck('user_id')
            ->unique();

        return $requiredIds->diff($completedIds)->isEmpty();
    }
}
