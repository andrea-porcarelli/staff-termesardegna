<?php

namespace App\Http\Controllers\Manutentore;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\Intervention;
use App\Models\InterventionCollaboration;
use App\Models\Media;
use App\Models\Report;
use App\Models\User;
use App\Services\InterventionActivityLogger;
use App\Support\InterventionLogActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
            'duration' => ['required', 'date_format:H:i'],
            'activities' => ['required', 'string', 'max:4000'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'is_final' => ['required', 'boolean'],
            'next_work_date' => ['nullable', 'date', 'after_or_equal:today', 'required_if:is_final,false'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,gif,zip'],
        ], [
            'duration.required' => 'Indica il tempo impiegato.',
            'duration.date_format' => 'Il tempo deve essere nel formato HH:MM.',
            'is_final.required' => 'Indica se hai terminato il lavoro.',
            'next_work_date.required_if' => 'Indica quando tornerai a lavorare sul ticket.',
            'next_work_date.after_or_equal' => 'La data deve essere oggi o successiva.',
            'files.*.mimes' => 'Sono accettati solo PDF, immagini (JPG/PNG/GIF) e ZIP.',
            'files.*.max' => 'Il file non può superare 10 MB.',
        ]);

        $totalMinutes = self::durationToMinutes($data['duration']);
        if ($totalMinutes <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Il tempo impiegato deve essere maggiore di zero.',
                'errors' => ['duration' => ['Il tempo impiegato deve essere maggiore di zero.']],
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

    /**
     * Rapportino standalone: non legato a un ticket.
     * Solo per manutentori.
     */
    public function storeStandalone(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->role !== 'manutentore') {
            return response()->json([
                'ok' => false,
                'message' => 'Solo i manutentori possono inserire un rapportino libero.',
            ], 403);
        }

        $userDeptIds = $user->departments()->pluck('departments.id')->all();
        $userAreaIds = $user->assignedAreaIds()->all();

        $data = $request->validate([
            'report_date' => ['required', 'date', 'before_or_equal:today'],
            'duration' => ['required', 'date_format:H:i'],
            'activities' => ['required', 'string', 'max:4000'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'area_id' => ['nullable', Rule::in($userAreaIds)],
            'department_id' => ['nullable', Rule::in($userDeptIds)],
            'equipment_id' => [
                'nullable',
                Rule::exists('equipments', 'id')
                    ->where('active', true)
                    ->whereIn('department_id', $userDeptIds ?: [-1]),
            ],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,gif,zip'],
        ], [
            'report_date.required' => 'Indica la data del rapportino.',
            'report_date.before_or_equal' => 'La data non può essere nel futuro.',
            'duration.required' => 'Indica il tempo impiegato.',
            'duration.date_format' => 'Il tempo deve essere nel formato HH:MM.',
            'activities.required' => 'Descrivi le attività svolte.',
            'area_id.in' => 'Area non disponibile.',
            'department_id.in' => 'Zona non disponibile.',
            'equipment_id.exists' => 'Impianto non disponibile.',
            'files.*.mimes' => 'Sono accettati solo PDF, immagini (JPG/PNG/GIF) e ZIP.',
            'files.*.max' => 'Il file non può superare 10 MB.',
        ]);

        $totalMinutes = self::durationToMinutes($data['duration']);
        if ($totalMinutes <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Il tempo impiegato deve essere maggiore di zero.',
                'errors' => ['duration' => ['Il tempo impiegato deve essere maggiore di zero.']],
            ], 422);
        }

        // Se è fornito un impianto, deriva area/zona dall'impianto per coerenza.
        $areaId = $data['area_id'] ?? null;
        $deptId = $data['department_id'] ?? null;
        $equipmentId = $data['equipment_id'] ?? null;
        if ($equipmentId) {
            $equipment = Equipment::with('department')->find($equipmentId);
            if ($equipment && $equipment->department) {
                $deptId = $equipment->department_id;
                $areaId = $equipment->department->area_id;
            }
        }

        $report = Report::create([
            'intervention_id' => null,
            'user_id' => $user->id,
            'report_date' => $data['report_date'],
            'start_time' => null,
            'end_time' => null,
            'duration_minutes' => $totalMinutes,
            'activities' => $data['activities'],
            'notes' => $data['notes'] ?? null,
            'status' => 'completed',
            'is_final' => true,
            'next_work_date' => null,
        ]);

        // Area/zona/impianto non hanno colonne dedicate su reports: li teniamo
        // nelle note di testa per preservarne la tracciabilità visiva in admin.
        $locationLine = collect([
            $areaId ? (Area::find($areaId)?->name) : null,
            $deptId ? (Department::find($deptId)?->name) : null,
            $equipmentId ? (Equipment::find($equipmentId)?->name) : null,
        ])->filter()->implode(' / ');
        if ($locationLine !== '') {
            $report->notes = trim($locationLine.($report->notes ? "\n\n".$report->notes : ''));
            $report->save();
        }

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

        return response()->json([
            'ok' => true,
            'message' => 'Rapportino libero salvato.',
            'report_id' => $report->id,
        ]);
    }

    private static function durationToMinutes(string $hhmm): int
    {
        [$h, $m] = array_pad(explode(':', $hhmm), 2, 0);

        return ((int) $h) * 60 + ((int) $m);
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
