<?php

namespace App\Http\Controllers\Manutentore;

use App\Http\Controllers\Controller;
use App\Models\UserOneSignalSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OneSignalSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'player_id' => ['required', 'string', 'max:255'],
            'device_label' => ['nullable', 'string', 'max:255'],
        ]);

        $userAgent = substr((string) $request->userAgent(), 0, 500);

        $subscription = UserOneSignalSubscription::updateOrCreate(
            ['player_id' => $data['player_id']],
            [
                'user_id' => Auth::id(),
                'device_label' => $data['device_label'] ?? null,
                'user_agent' => $userAgent,
                'last_seen_at' => now(),
            ],
        );

        return response()->json([
            'ok' => true,
            'id' => $subscription->id,
        ]);
    }

    public function destroy(string $playerId): JsonResponse
    {
        UserOneSignalSubscription::where('user_id', Auth::id())
            ->where('player_id', $playerId)
            ->delete();

        return response()->json(['ok' => true]);
    }
}
