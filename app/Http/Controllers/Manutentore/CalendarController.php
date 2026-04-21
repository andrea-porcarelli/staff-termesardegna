<?php

namespace App\Http\Controllers\Manutentore;

use App\Http\Controllers\Controller;
use App\Models\Intervention;
use App\Models\InterventionCollaboration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $deptIds = $user->departments()->pluck('departments.id')->all();

        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);

        // Normalizza mese/anno
        if ($month < 1 || $month > 12) {
            $month = now()->month;
            $year = now()->year;
        }

        $current = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $prev = $current->copy()->subMonth();
        $next = $current->copy()->addMonth();

        // Range della griglia (parte da lunedì per inquadrare il mese)
        $gridStart = $current->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $current->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $query = Intervention::with(['equipment', 'area', 'department', 'assignedUser'])
            ->where(function ($q) use ($user, $deptIds) {
                $q->where('assigned_user_id', $user->id);
                $q->orWhereHas('collaborations', fn ($c) => $c
                    ->where('user_id', $user->id)
                    ->where('status', InterventionCollaboration::STATUS_ACCEPTED));
                if (! empty($deptIds)) {
                    $q->orWhere(function ($q2) use ($deptIds) {
                        $q2->whereIn('department_id', $deptIds)
                            ->orWhereHas('equipment', fn ($eq) => $eq->whereIn('department_id', $deptIds));
                    });
                }
            });

        $interventions = $query->get();

        // Mappa: yyyy-mm-dd → collection di interventi
        $byDay = $interventions
            ->map(function ($i) {
                $i->__display_date = $i->scheduled_date ?? $i->deadline?->copy()->startOfDay() ?? $i->created_at?->copy()->startOfDay();

                return $i;
            })
            ->filter(fn ($i) => $i->__display_date
                && $i->__display_date->between($gridStart, $gridEnd))
            ->groupBy(fn ($i) => $i->__display_date->toDateString())
            ->map(fn ($items) => $items->sortBy(function ($i) {
                $time = $i->scheduled_start_time ?: '00:00:00';

                return $i->__display_date->toDateString().' '.$time;
            })->values());

        // Costruisce le settimane della griglia
        $weeks = [];
        $cursor = $gridStart->copy();
        while ($cursor->lte($gridEnd)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = $cursor->copy();
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        return view('manutentore.calendar.index', [
            'current' => $current,
            'prev' => $prev,
            'next' => $next,
            'weeks' => $weeks,
            'byDay' => $byDay,
        ]);
    }
}
