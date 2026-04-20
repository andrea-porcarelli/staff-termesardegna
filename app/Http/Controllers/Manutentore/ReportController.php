<?php

namespace App\Http\Controllers\Manutentore;

use App\Http\Controllers\Controller;
use App\Models\Intervention;
use App\Models\InterventionCollaboration;
use App\Models\Media;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function store(Request $request, Intervention $intervention): JsonResponse
    {
        $user = Auth::user();

        if (!$this->canReportOn($intervention, $user)) {
            return response()->json([
                'ok'      => false,
                'message' => 'Non puoi scrivere un rapportino per questo ticket.',
            ], 403);
        }

        $alreadyWritten = Report::where('intervention_id', $intervention->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyWritten) {
            return response()->json([
                'ok'      => false,
                'message' => 'Hai già scritto un rapportino per questo ticket.',
            ], 422);
        }

        $data = $request->validate([
            'report_date' => ['required', 'date'],
            'start_time'  => ['required', 'date_format:H:i'],
            'end_time'    => ['required', 'date_format:H:i', 'after:start_time'],
            'activities'  => ['required', 'string', 'max:4000'],
            'notes'       => ['nullable', 'string', 'max:4000'],
            'status'      => ['nullable', 'in:draft,completed'],
            'files'       => ['nullable', 'array', 'max:10'],
            'files.*'     => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,gif,zip'],
        ], [
            'end_time.after'      => 'L\'ora di fine deve essere successiva a quella di inizio.',
            'files.*.mimes'       => 'Sono accettati solo PDF, immagini (JPG/PNG/GIF) e ZIP.',
            'files.*.max'         => 'Il file non può superare 10 MB.',
        ]);

        $report = Report::create([
            'intervention_id' => $intervention->id,
            'user_id'         => $user->id,
            'report_date'     => $data['report_date'],
            'start_time'      => $data['start_time'],
            'end_time'        => $data['end_time'],
            'activities'      => $data['activities'],
            'notes'           => $data['notes'] ?? null,
            'status'          => $data['status'] ?? 'draft',
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('media', 'public');
                Media::create([
                    'mediable_type' => Report::class,
                    'mediable_id'   => $report->id,
                    'file_name'     => $file->getClientOriginalName(),
                    'file_path'     => $path,
                    'file_type'     => $file->getMimeType(),
                    'file_size'     => $file->getSize(),
                ]);
            }
        }

        return response()->json([
            'ok'        => true,
            'message'   => 'Rapportino salvato.',
            'report_id' => $report->id,
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
}
