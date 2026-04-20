<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\InterventionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MaintenanceRoleController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\Manutentore\HomeController as MHomeController;
use App\Http\Controllers\Manutentore\InterventionController as MInterventionController;
use App\Http\Controllers\Manutentore\CollaborationController as MCollaborationController;
use App\Http\Controllers\Manutentore\NotificationController as MNotificationController;
use App\Http\Controllers\Manutentore\ReportController as MReportController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::impersonate();

// ─── Area manutentore (layout mobile dedicato) ──────────────────────────────
Route::prefix('m')->middleware(['auth', 'mobile'])->name('m.')->group(function () {
    Route::get('/', [MHomeController::class, 'index'])->name('home');
    Route::get('/profilo', [MHomeController::class, 'profile'])->name('profile');

    Route::view('/calendario', 'manutentore.placeholder', [
        'title' => 'Calendario',
        'message' => 'La vista calendario arriva con la prossima release.',
    ])->name('calendar');

    Route::view('/piano', 'manutentore.placeholder', [
        'title' => 'Piano orario',
        'message' => 'Il piano orario mobile arriva con la prossima release.',
    ])->name('schedule');

    Route::get('/tickets', [MInterventionController::class, 'index'])
        ->name('tickets.index');

    Route::get('/tickets/{intervention}/json', [MInterventionController::class, 'showJson'])
        ->name('tickets.json');

    // Azioni sul ticket
    Route::get('/interventions/{intervention}/candidates', [MInterventionController::class, 'candidatesJson'])
        ->name('interventions.candidates');
    Route::post('/interventions/{intervention}/transfer', [MInterventionController::class, 'transfer'])
        ->name('interventions.transfer');
    Route::post('/interventions/{intervention}/collaboration', [MInterventionController::class, 'requestCollaboration'])
        ->name('interventions.collaboration');
    Route::post('/interventions/{intervention}/close', [MInterventionController::class, 'close'])
        ->name('interventions.close');
    Route::post('/interventions/{intervention}/suspend', [MInterventionController::class, 'suspend'])
        ->name('interventions.suspend');
    Route::post('/interventions/{intervention}/defer', [MInterventionController::class, 'defer'])
        ->name('interventions.defer');

    // Risposta a richiesta di collaborazione
    Route::post('/collaborations/{collaboration}/respond', [MCollaborationController::class, 'respond'])
        ->name('collaborations.respond');

    // Notifiche in-app
    Route::get('/notifications/json',  [MNotificationController::class, 'indexJson'])->name('notifications.json');
    Route::post('/notifications/read-all', [MNotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [MNotificationController::class, 'markRead'])->name('notifications.read');

    Route::post('/interventions/quick-open', [MInterventionController::class, 'quickStore'])
        ->name('interventions.quick-open');

    // Rapportino inline (mobile)
    Route::post('/interventions/{intervention}/reports', [MReportController::class, 'store'])
        ->name('reports.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');


    // Calendario interventi
    Route::get('/interventions/calendar/view', [InterventionController::class, 'calendar'])->name('interventions.calendar');
    Route::get('/interventions/calendar/data', [InterventionController::class, 'calendarData'])->name('interventions.calendar.data');

    Route::resource('interventions', InterventionController::class);

    // Rapportini (flusso: create mostra form con media temporanei -> store crea rapportino e associa media)
    Route::get('/interventions/{intervention}/reports/create', [ReportController::class, 'create'])->name('interventions.reports.create');
    Route::post('/interventions/{intervention}/reports', [ReportController::class, 'store'])->name('interventions.reports.store');
    Route::get('/interventions/{intervention}/reports/{report}/edit', [ReportController::class, 'edit'])->name('interventions.reports.edit');
    Route::put('/interventions/{intervention}/reports/{report}', [ReportController::class, 'update'])->name('interventions.reports.update');
    Route::delete('/interventions/{intervention}/reports/{report}', [ReportController::class, 'destroy'])->name('interventions.reports.destroy');

    // Lista rapportini (admin/operator)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Chiusura rapportino (admin/operator)
    Route::patch('/interventions/{intervention}/reports/{report}/close', [ReportController::class, 'close'])->name('interventions.reports.close');

    // API endpoint per dettagli rapportino (per modale)
    Route::get('/api/reports/{report}', [ReportController::class, 'show'])->name('api.reports.show');

    // Apertura rapida intervento ordinario (operator/manutentore)
    Route::post('/interventions/quick-open', [InterventionController::class, 'quickStore'])->name('interventions.quick-open');

    // API endpoint per dati pianificazione impianto (per form intervento)
    Route::get('/api/equipments/{equipment}/planning', [InterventionController::class, 'equipmentPlanning'])->name('api.equipments.planning');

    // Presa in carico intervento (manutentore)
    Route::post('/interventions/{intervention}/take-charge', [InterventionController::class, 'takeCharge'])->name('interventions.take-charge');

    // Piano orario manutentore (self-service)
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::put('/schedule', [ScheduleController::class, 'update'])->name('schedule.update');

    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class);
        Route::put('users/{user}/schedule', [UserController::class, 'updateSchedule'])->name('users.schedule.update');
        Route::post('users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
        Route::post('users/stop-impersonating', [UserController::class, 'stopImpersonating'])->name('users.stopImpersonating');
        Route::resource('areas', AreaController::class);
        Route::resource('departments', DepartmentController::class);
        Route::resource('equipments', EquipmentController::class);
        Route::resource('maintenance_roles', MaintenanceRoleController::class);
        Route::resource('teams', TeamController::class);
    });
});
