<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\InterventionController;
use App\Http\Controllers\InterventionLogController;
use App\Http\Controllers\MaintenanceRoleController;
use App\Http\Controllers\PasswordSetController;
use App\Http\Controllers\Manutentore\CalendarController as MCalendarController;
use App\Http\Controllers\Manutentore\CollaborationController as MCollaborationController;
use App\Http\Controllers\Manutentore\HomeController as MHomeController;
use App\Http\Controllers\Manutentore\InterventionController as MInterventionController;
use App\Http\Controllers\Manutentore\NotificationController as MNotificationController;
use App\Http\Controllers\Manutentore\OneSignalSubscriptionController as MOneSignalSubscriptionController;
use App\Http\Controllers\Manutentore\ReportController as MReportController;
use App\Http\Controllers\Manutentore\ScheduleController as MScheduleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Imposta/resetta password tramite link inviato dall'admin
Route::get('/password/set/{token}', [PasswordSetController::class, 'show'])->name('password.set');
Route::post('/password/set/{token}', [PasswordSetController::class, 'store'])->name('password.set.store');

Route::impersonate();

// ─── Area manutentore (layout mobile dedicato) ──────────────────────────────
Route::prefix('m')->middleware(['auth', 'mobile'])->name('m.')->group(function () {
    Route::get('/', [MHomeController::class, 'index'])->name('home');
    Route::get('/profilo', [MHomeController::class, 'profile'])->name('profile');

    Route::get('/calendario', [MCalendarController::class, 'index'])->name('calendar');

    Route::get('/piano', [MScheduleController::class, 'index'])->name('schedule');

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
    Route::post('/interventions/{intervention}/suspend', [MInterventionController::class, 'suspend'])
        ->name('interventions.suspend');
    Route::post('/interventions/{intervention}/defer', [MInterventionController::class, 'defer'])
        ->name('interventions.defer');

    // Risposta a richiesta di collaborazione
    Route::post('/collaborations/{collaboration}/respond', [MCollaborationController::class, 'respond'])
        ->name('collaborations.respond');

    // Notifiche in-app
    Route::get('/notifications/json', [MNotificationController::class, 'indexJson'])->name('notifications.json');
    Route::post('/notifications/read-all', [MNotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [MNotificationController::class, 'markRead'])->name('notifications.read');

    // OneSignal push subscriptions (multi-device)
    Route::post('/onesignal/subscribe', [MOneSignalSubscriptionController::class, 'store'])->name('onesignal.subscribe');
    Route::delete('/onesignal/subscribe/{playerId}', [MOneSignalSubscriptionController::class, 'destroy'])->name('onesignal.unsubscribe');

    Route::post('/interventions/quick-open', [MInterventionController::class, 'quickStore'])
        ->name('interventions.quick-open');

    // Rapportino inline (mobile)
    Route::get('/rapportini', [MReportController::class, 'index'])->name('reports.index');
    Route::post('/interventions/{intervention}/reports', [MReportController::class, 'store'])
        ->name('reports.store');
    Route::post('/reports', [MReportController::class, 'storeStandalone'])
        ->name('reports.store-standalone');
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
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::post('users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
        Route::post('users/{user}/send-password-link', [UserController::class, 'sendPasswordLink'])->name('users.send-password-link');
        Route::post('users/stop-impersonating', [UserController::class, 'stopImpersonating'])->name('users.stopImpersonating');
        Route::resource('areas', AreaController::class);
        Route::resource('departments', DepartmentController::class);
        Route::resource('equipments', EquipmentController::class);
        Route::resource('maintenance_roles', MaintenanceRoleController::class);
        Route::resource('teams', TeamController::class);

        Route::get('/intervention-logs', [InterventionLogController::class, 'index'])->name('intervention_logs.index');
    });
});
