<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        abort_if($user->role !== 'manutentore', 403);

        $existingSlots = $user->workScheduleSlots->map(fn ($s) => [
            'date' => $s->date?->format('Y-m-d') ?? '',
            'date_from' => $s->date_from?->format('Y-m-d') ?? '',
            'date_to' => $s->date_to?->format('Y-m-d') ?? '',
            'day_of_week' => $s->day_of_week !== null ? (string) $s->day_of_week : '',
            'start_time' => $s->start_time ? substr($s->start_time, 0, 5) : '',
            'end_time' => $s->end_time ? substr($s->end_time, 0, 5) : '',
            'type' => $s->type,
            'is_recurring' => $s->is_recurring ? '1' : '0',
            'group_id' => $s->group_id ?? '',
        ])->toArray();

        return view('schedule.index', compact('existingSlots'));
    }

    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();
        abort_if($user->role !== 'manutentore', 403);

        $request->validate([
            'slots' => 'nullable|array',
            'slots.*.start_time' => 'nullable|date_format:H:i',
            'slots.*.end_time' => 'nullable|date_format:H:i',
            'slots.*.type' => 'nullable|in:lavorativo,ferie,riposi,pausa_pranzo',
            'slots.*.is_recurring' => 'nullable|in:0,1',
            'slots.*.day_of_week' => 'nullable|integer|between:0,6',
            'slots.*.date' => 'nullable|date',
            'slots.*.date_from' => 'nullable|date',
            'slots.*.date_to' => 'nullable|date',
            'slots.*.group_id' => 'nullable|string',
        ]);

        // Elimina slot con data concreta più vecchi di un anno
        $oneYearAgo = now()->subYear();
        $user->workScheduleSlots()
            ->whereNotNull('date')
            ->where('date', '<', $oneYearAgo)
            ->delete();

        // Elimina tutti gli altri slot (non ricorrenti infiniti)
        $user->workScheduleSlots()
            ->where('is_recurring', false)
            ->delete();

        foreach ($request->get('slots', []) as $slot) {
            $type = in_array($slot['type'] ?? '', ['lavorativo', 'ferie', 'riposi', 'pausa_pranzo'])
                ? $slot['type']
                : 'lavorativo';
            $isAllDay = in_array($type, ['ferie', 'riposi']) && empty($slot['start_time']);
            if (! $isAllDay && (empty($slot['start_time']) || empty($slot['end_time']))) {
                continue;
            }
            $isRecurring = (bool) (int) ($slot['is_recurring'] ?? 0);
            $user->workScheduleSlots()->create([
                'date' => ! $isRecurring ? ($slot['date'] ?: null) : null,
                'date_from' => $slot['date_from'] ?: null,
                'date_to' => $slot['date_to'] ?: null,
                'day_of_week' => $isRecurring ? (int) ($slot['day_of_week'] ?? 0) : null,
                'start_time' => $slot['start_time'] ?: null,
                'end_time' => $slot['end_time'] ?: null,
                'type' => $type,
                'is_recurring' => $isRecurring,
                'group_id' => $slot['group_id'] ?: null,
            ]);
        }

        return response()->json(['message' => 'Piano orario salvato con successo.']);
    }
}
