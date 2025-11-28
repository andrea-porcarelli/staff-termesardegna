<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Intervention;
use App\Models\Report;
use App\Models\User;
use App\Models\Equipment;
use App\Models\Area;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $role = $user->role;

        $data = ['user' => $user];

        if ($role === 'admin') {
            // Admin: statistiche globali
            $data['totalInterventions'] = Intervention::count();
            $data['totalReports'] = Report::count();
            $data['totalOperators'] = User::where('role', 'operator')->count();
            $data['totalEquipment'] = Equipment::where('active', true)->count();
            $data['totalAreas'] = Area::count();

            $data['interventionsPlanned'] = Intervention::where('status', 'planned')->count();
            $data['interventionsInProgress'] = Intervention::where('status', 'in_progress')->count();
            $data['interventionsCompleted'] = Intervention::where('status', 'completed')->count();

            $data['reportsCompleted'] = Report::where('status', 'completed')->count();
            $data['reportsDraft'] = Report::where('status', 'draft')->count();

            // Interventi in scadenza (prossimi 7 giorni)
            $data['upcomingInterventions'] = Intervention::with(['equipment', 'assignedUser'])
                ->whereBetween('scheduled_date', [now(), now()->addDays(7)])
                ->orderBy('scheduled_date', 'asc')
                ->limit(5)
                ->get();

            // Rapportini recenti
            $data['recentReports'] = Report::with(['intervention.equipment', 'user'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

        } elseif ($role === 'supervisor') {
            // Supervisor: statistiche filtrate per reparti associati
            // Ottiene gli IDs dei reparti associati al supervisor
            $departmentIds = $user->departments()->pluck('departments.id');

            // Se il supervisor non ha reparti associati, mostra dati vuoti
            if ($departmentIds->isEmpty()) {
                $data['totalInterventions'] = 0;
                $data['totalReports'] = 0;
                $data['totalOperators'] = 0;
                $data['interventionsPlanned'] = 0;
                $data['interventionsInProgress'] = 0;
                $data['interventionsCompleted'] = 0;
                $data['reportsCompleted'] = 0;
                $data['reportsDraft'] = 0;
                $data['upcomingInterventions'] = collect();
                $data['pendingReports'] = collect();
            } else {
                // Filtra interventi per equipments nei reparti associati
                $data['totalInterventions'] = Intervention::whereHas('equipment', function($query) use ($departmentIds) {
                    $query->whereIn('department_id', $departmentIds);
                })->count();

                $data['totalReports'] = Report::whereHas('intervention.equipment', function($query) use ($departmentIds) {
                    $query->whereIn('department_id', $departmentIds);
                })->count();

                // Conta solo operatori che hanno interventi nei reparti associati
                $data['totalOperators'] = User::where('role', 'operator')
                    ->whereHas('interventions.equipment', function($query) use ($departmentIds) {
                        $query->whereIn('department_id', $departmentIds);
                    })
                    ->distinct()
                    ->count();

                $data['interventionsPlanned'] = Intervention::whereHas('equipment', function($query) use ($departmentIds) {
                    $query->whereIn('department_id', $departmentIds);
                })->where('status', 'planned')->count();

                $data['interventionsInProgress'] = Intervention::whereHas('equipment', function($query) use ($departmentIds) {
                    $query->whereIn('department_id', $departmentIds);
                })->where('status', 'in_progress')->count();

                $data['interventionsCompleted'] = Intervention::whereHas('equipment', function($query) use ($departmentIds) {
                    $query->whereIn('department_id', $departmentIds);
                })->where('status', 'completed')->count();

                $data['reportsCompleted'] = Report::whereHas('intervention.equipment', function($query) use ($departmentIds) {
                    $query->whereIn('department_id', $departmentIds);
                })->where('status', 'completed')->count();

                $data['reportsDraft'] = Report::whereHas('intervention.equipment', function($query) use ($departmentIds) {
                    $query->whereIn('department_id', $departmentIds);
                })->where('status', 'draft')->count();

                // Interventi in scadenza (prossimi 7 giorni) nei reparti associati
                $data['upcomingInterventions'] = Intervention::with(['equipment', 'assignedUser'])
                    ->whereHas('equipment', function($query) use ($departmentIds) {
                        $query->whereIn('department_id', $departmentIds);
                    })
                    ->whereBetween('scheduled_date', [now(), now()->addDays(7)])
                    ->orderBy('scheduled_date', 'asc')
                    ->limit(5)
                    ->get();

                // Rapportini in bozza da supervisionare nei reparti associati
                $data['pendingReports'] = Report::with(['intervention.equipment', 'user'])
                    ->whereHas('intervention.equipment', function($query) use ($departmentIds) {
                        $query->whereIn('department_id', $departmentIds);
                    })
                    ->where('status', 'draft')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            }

        } else {
            // Operator: solo le sue statistiche
            $data['myInterventions'] = Intervention::where('assigned_user_id', $user->id)->count();
            $data['myReports'] = Report::where('user_id', $user->id)->count();

            $data['myInterventionsPlanned'] = Intervention::where('assigned_user_id', $user->id)
                ->where('status', 'planned')
                ->count();
            $data['myInterventionsInProgress'] = Intervention::where('assigned_user_id', $user->id)
                ->where('status', 'in_progress')
                ->count();
            $data['myInterventionsCompleted'] = Intervention::where('assigned_user_id', $user->id)
                ->where('status', 'completed')
                ->count();

            $data['myReportsCompleted'] = Report::where('user_id', $user->id)
                ->where('status', 'completed')
                ->count();
            $data['myReportsDraft'] = Report::where('user_id', $user->id)
                ->where('status', 'draft')
                ->count();

            // Prossimi interventi dell'operatore (prossimi 7 giorni)
            $data['myUpcomingInterventions'] = Intervention::with(['equipment'])
                ->where('assigned_user_id', $user->id)
                ->whereBetween('scheduled_date', [now(), now()->addDays(7)])
                ->orderBy('scheduled_date', 'asc')
                ->limit(5)
                ->get();

            // Interventi di oggi
            $data['todayInterventions'] = Intervention::with(['equipment'])
                ->where('assigned_user_id', $user->id)
                ->whereDate('scheduled_date', today())
                ->orderBy('scheduled_start_time', 'asc')
                ->get();
        }

        return view('dashboard.index', $data);
    }
}
