<?php

namespace App\Http\Controllers\Manutentore;

use App\Http\Controllers\Controller;
use App\Models\InterventionCollaboration;
use App\Notifications\CollaborationRespondedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CollaborationController extends Controller
{
    public function respond(Request $request, InterventionCollaboration $collaboration): JsonResponse
    {
        if ($collaboration->user_id !== Auth::id()) {
            abort(403);
        }

        if ($collaboration->status !== InterventionCollaboration::STATUS_PENDING) {
            return response()->json([
                'ok' => false,
                'message' => 'Richiesta già elaborata.',
            ], 422);
        }

        $data = $request->validate([
            'decision' => ['required', 'in:accept,decline'],
        ]);

        $collaboration->update([
            'status' => $data['decision'] === 'accept'
                ? InterventionCollaboration::STATUS_ACCEPTED
                : InterventionCollaboration::STATUS_DECLINED,
            'responded_at' => now(),
        ]);

        $collaboration->requestedBy?->notify(
            new CollaborationRespondedNotification($collaboration->fresh())
        );

        return response()->json([
            'ok'      => true,
            'message' => $data['decision'] === 'accept'
                ? 'Collaborazione accettata.'
                : 'Richiesta rifiutata.',
        ]);
    }
}
