<?php

namespace App\Http\Controllers\Manutentore;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $slots = $user->workScheduleSlots()->get();

        // Slot ricorrenti raggruppati per day_of_week (0=Dom, 1=Lun, ...).
        $recurringByDay = $slots->where('is_recurring', true)
            ->groupBy('day_of_week')
            ->map(fn ($daySlots) => $daySlots->sortBy('start_time')->values())
            ->sortKeys();

        // Slot one-off (data concreta) → solo i prossimi 30 giorni.
        $today = today();
        $end = $today->copy()->addDays(30);
        $oneOff = $slots->where('is_recurring', false)
            ->filter(function ($s) use ($today, $end) {
                $date = $s->date;
                if (! $date) return false;

                return $date->between($today, $end);
            })
            ->sortBy(fn ($s) => $s->date->timestamp.' '.($s->start_time ?? '00:00'))
            ->values();

        return view('manutentore.schedule.index', [
            'user' => $user,
            'recurringByDay' => $recurringByDay,
            'oneOff' => $oneOff,
        ]);
    }
}
