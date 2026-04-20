<?php

namespace App\Http\Controllers\Manutentore;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function indexJson(): JsonResponse
    {
        $user = Auth::user();

        $items = $user->notifications()
            ->take(50)
            ->get()
            ->map(fn ($n) => [
                'id'         => $n->id,
                'type'       => $n->data['type'] ?? 'info',
                'headline'   => $n->data['headline'] ?? null,
                'subline'    => $n->data['subline'] ?? null,
                'intervention_id'  => $n->data['intervention_id'] ?? null,
                'collaboration_id' => $n->data['collaboration_id'] ?? null,
                'read_at'    => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->diffForHumans(),
            ]);

        return response()->json([
            'items'        => $items,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(string $notification): JsonResponse
    {
        $user = Auth::user();
        $n = $user->notifications()->where('id', $notification)->firstOrFail();
        $n->markAsRead();

        return response()->json([
            'ok'           => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAllRead(): JsonResponse
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'ok'           => true,
            'unread_count' => 0,
        ]);
    }
}
