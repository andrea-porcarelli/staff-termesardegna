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
use App\Http\Controllers\TeamController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

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

    // API endpoint per dettagli rapportino (per modale)
    Route::get('/api/reports/{report}', [ReportController::class, 'show'])->name('api.reports.show');

    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('areas', AreaController::class);
        Route::resource('departments', DepartmentController::class);
        Route::resource('equipments', EquipmentController::class);
        Route::resource('maintenance_roles', MaintenanceRoleController::class);
        Route::resource('teams', TeamController::class);
    });
});
