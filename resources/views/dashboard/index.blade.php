@extends('layouts.app')

@section('title', 'Dashboard - Rapportini')

@section('page-title', 'Dashboard')

@section('content')

{{-- Welcome Section --}}
<div class="alert alert-light border-0 mb-4">
    <h4 class="mb-1">Benvenuto, {{ explode(' ', $user->name)[0] }}!</h4>
    <p class="mb-0 text-muted">
        @if($user->role === 'admin')
            Gestisci l'intero sistema di rapportini e tickets.
        @elseif($user->role === 'operatore')
            Monitora i tickets e supervisiona i rapportini degli operatori.
        @else
            Visualizza i tuoi tickets e crea rapportini operativi.
        @endif
    </p>
</div>

@if($user->role === 'admin')
    {{-- ADMIN DASHBOARD --}}

    {{-- Statistiche Principali --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded p-3">
                                <i class="bi bi-calendar-check text-primary" style="font-size: 24px;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0">{{ $totalInterventions }}</h3>
                            <small class="text-muted">Tickets Totali</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded p-3">
                                <i class="bi bi-file-earmark-text text-success" style="font-size: 24px;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0">{{ $totalReports }}</h3>
                            <small class="text-muted">Rapportini Totali</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded p-3">
                                <i class="bi bi-people text-info" style="font-size: 24px;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0">{{ $totalOperators }}</h3>
                            <small class="text-muted">Operatori Attivi</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded p-3">
                                <i class="bi bi-gear text-warning" style="font-size: 24px;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0">{{ $totalEquipment }}</h3>
                            <small class="text-muted">Attrezzature Attive</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistiche Interventi --}}
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Stato Tickets</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="p-3">
                                <h2 class="mb-1 text-info">{{ $interventionsPlanned }}</h2>
                                <small class="text-muted">Pianificati</small>
                            </div>
                        </div>
                        <div class="col-4 border-start border-end">
                            <div class="p-3">
                                <h2 class="mb-1 text-warning">{{ $interventionsInProgress }}</h2>
                                <small class="text-muted">In Corso</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3">
                                <h2 class="mb-1 text-success">{{ $interventionsCompleted }}</h2>
                                <small class="text-muted">Completati</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-file-text me-2"></i>Rapportini</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Completati</span>
                        <span class="badge bg-success">{{ $reportsCompleted }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Bozze</span>
                        <span class="badge bg-secondary">{{ $reportsDraft }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Prossimi Interventi e Rapportini Recenti --}}
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Prossimi Tickets (7 gg)</h5>
                    <a href="{{ route('interventions.calendar') }}" class="btn btn-sm btn-light">Vedi Tutti</a>
                </div>
                <div class="card-body p-0">
                    @if($upcomingInterventions->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-x" style="font-size: 32px;"></i>
                            <p class="mt-2 mb-0">Nessun ticket programmato</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($upcomingInterventions as $intervention)
                                <a href="{{ route('interventions.show', $intervention) }}" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $intervention->title }}</h6>
                                            <small class="text-muted">
                                                <i class="bi bi-gear me-1"></i>{{ $intervention->equipment?->name ?? ($intervention->area?->name . ' / ' . $intervention->department?->name) }}
                                                <i class="bi bi-person ms-2 me-1"></i>{{ $intervention->assignedUser->name }}
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <small class="d-block">{{ $intervention->scheduled_date->format('d/m/Y') }}</small>
                                            @if($intervention->scheduled_start_time)
                                                <small class="text-muted">{{ substr($intervention->scheduled_start_time, 0, 5) }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-check me-2"></i>Rapportini Recenti</h5>
                </div>
                <div class="card-body p-0">
                    @if($recentReports->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 32px;"></i>
                            <p class="mt-2 mb-0">Nessun rapportino disponibile</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($recentReports as $report)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $report->intervention->title }}</h6>
                                            <small class="text-muted">
                                                <i class="bi bi-person me-1"></i>{{ $report->user->name }}
                                                <i class="bi bi-calendar ms-2 me-1"></i>{{ $report->report_date->format('d/m/Y') }}
                                            </small>
                                        </div>
                                        <span class="badge {{ $report->status === 'completed' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $report->status === 'completed' ? 'Completato' : 'Bozza' }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Azioni Rapide Admin --}}
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Azioni Rapide</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <a href="{{ route('interventions.create') }}" class="btn btn-light w-100 py-3">
                        <i class="bi bi-plus-circle d-block mb-2" style="font-size: 24px;"></i>
                        <small>Nuovo Ticket</small>
                    </a>
                </div>
                <div class="col-md-3 col-6">
                    <a href="{{ route('users.index') }}" class="btn btn-light w-100 py-3">
                        <i class="bi bi-people d-block mb-2" style="font-size: 24px;"></i>
                        <small>Gestisci Utenti</small>
                    </a>
                </div>
                <div class="col-md-3 col-6">
                    <a href="{{ route('equipments.index') }}" class="btn btn-light w-100 py-3">
                        <i class="bi bi-gear d-block mb-2" style="font-size: 24px;"></i>
                        <small>Gestisci Attrezzature</small>
                    </a>
                </div>
                <div class="col-md-3 col-6">
                    <a href="{{ route('interventions.calendar') }}" class="btn btn-light w-100 py-3">
                        <i class="bi bi-calendar3 d-block mb-2" style="font-size: 24px;"></i>
                        <small>Calendario</small>
                    </a>
                </div>
            </div>
        </div>
    </div>

@elseif($user->role === 'operator')
    {{-- SUPERVISOR DASHBOARD --}}

    {{-- Statistiche Principali --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded p-3">
                                <i class="bi bi-calendar-check text-primary" style="font-size: 24px;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0">{{ $totalInterventions }}</h3>
                            <small class="text-muted">Tickets Totali</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded p-3">
                                <i class="bi bi-file-earmark-text text-success" style="font-size: 24px;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0">{{ $totalReports }}</h3>
                            <small class="text-muted">Rapportini Totali</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded p-3">
                                <i class="bi bi-people text-info" style="font-size: 24px;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0">{{ $totalOperators }}</h3>
                            <small class="text-muted">Operatori Attivi</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistiche Interventi --}}
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Stato Tickets</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="p-3">
                                <h2 class="mb-1 text-info">{{ $interventionsPlanned }}</h2>
                                <small class="text-muted">Pianificati</small>
                            </div>
                        </div>
                        <div class="col-4 border-start border-end">
                            <div class="p-3">
                                <h2 class="mb-1 text-warning">{{ $interventionsInProgress }}</h2>
                                <small class="text-muted">In Corso</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3">
                                <h2 class="mb-1 text-success">{{ $interventionsCompleted }}</h2>
                                <small class="text-muted">Completati</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-file-text me-2"></i>Rapportini</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Completati</span>
                        <span class="badge bg-success">{{ $reportsCompleted }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Bozze da Revisionare</span>
                        <span class="badge bg-warning">{{ $reportsDraft }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Prossimi Interventi e Rapportini in Bozza --}}
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Prossimi Tickets (7 gg)</h5>
                    <a href="{{ route('interventions.calendar') }}" class="btn btn-sm btn-light">Vedi Tutti</a>
                </div>
                <div class="card-body p-0">
                    @if($upcomingInterventions->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-x" style="font-size: 32px;"></i>
                            <p class="mt-2 mb-0">Nessun ticket programmato</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($upcomingInterventions as $intervention)
                                <a href="{{ route('interventions.show', $intervention) }}" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $intervention->title }}</h6>
                                            <small class="text-muted">
                                                <i class="bi bi-gear me-1"></i>{{ $intervention->equipment?->name ?? ($intervention->area?->name . ' / ' . $intervention->department?->name) }}
                                                <i class="bi bi-person ms-2 me-1"></i>{{ $intervention->assignedUser->name }}
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <small class="d-block">{{ $intervention->scheduled_date->format('d/m/Y') }}</small>
                                            @if($intervention->scheduled_start_time)
                                                <small class="text-muted">{{ substr($intervention->scheduled_start_time, 0, 5) }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-exclamation-circle me-2"></i>Rapportini in Bozza</h5>
                </div>
                <div class="card-body p-0">
                    @if($pendingReports->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-check-circle" style="font-size: 32px;"></i>
                            <p class="mt-2 mb-0">Nessuna bozza da revisionare</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($pendingReports as $report)
                                <a href="{{ route('interventions.show', $report->intervention) }}" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $report->intervention->title }}</h6>
                                            <small class="text-muted">
                                                <i class="bi bi-person me-1"></i>{{ $report->user->name }}
                                                <i class="bi bi-calendar ms-2 me-1"></i>{{ $report->report_date->format('d/m/Y') }}
                                            </small>
                                        </div>
                                        <span class="badge bg-warning">Bozza</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Azioni Rapide Supervisor --}}
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Azioni Rapide</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4 col-6">
                    <a href="{{ route('interventions.create') }}" class="btn btn-light w-100 py-3">
                        <i class="bi bi-plus-circle d-block mb-2" style="font-size: 24px;"></i>
                        <small>Nuovo Ticket</small>
                    </a>
                </div>
                <div class="col-md-4 col-6">
                    <a href="{{ route('interventions.index') }}" class="btn btn-light w-100 py-3">
                        <i class="bi bi-list-check d-block mb-2" style="font-size: 24px;"></i>
                        <small>Tutti i Tickets</small>
                    </a>
                </div>
                <div class="col-md-4 col-6">
                    <a href="{{ route('interventions.calendar') }}" class="btn btn-light w-100 py-3">
                        <i class="bi bi-calendar3 d-block mb-2" style="font-size: 24px;"></i>
                        <small>Calendario</small>
                    </a>
                </div>
            </div>
        </div>
    </div>

@else
    {{-- OPERATOR / MANUTENTORE DASHBOARD --}}

    {{-- Tasto apertura rapida intervento ordinario --}}
    <div class="mb-4">
        <button type="button" class="btn btn-warning btn-lg w-100 py-3" data-bs-toggle="modal" data-bs-target="#modalInterventoOrdinario">
            <i class="bi bi-wrench d-block mb-1" style="font-size: 28px;"></i>
            <strong>Apri Ticket Ordinario</strong>
        </button>
    </div>

    {{-- Interventi da Eseguire (solo manutentore) --}}
    @if($user->role === 'manutentore' && isset($availableInterventions))
        @php
            $priClasses = ['low' => 'bg-secondary', 'high' => 'bg-warning text-dark', 'fixed_date' => 'bg-purple'];
            $priLabels  = ['low' => 'Bassa', 'high' => 'Alta', 'fixed_date' => 'Data fissa'];
            $statusClasses = ['open' => 'bg-primary', 'planned' => 'bg-info'];
            $statusLabels  = ['open' => 'Aperto', 'planned' => 'Pianificato'];
        @endphp
        <div class="card border-primary shadow mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Tickets da Eseguire</h5>
                <span class="badge bg-white text-primary fs-6">{{ $availableInterventions->count() }}</span>
            </div>
            <div class="card-body p-0">
                @if($availableInterventions->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-check-circle" style="font-size: 48px;"></i>
                        <p class="mt-3 mb-0 fs-5">Nessun ticket disponibile al momento</p>
                    </div>
                @else
                    @foreach($availableInterventions as $intervention)
                        @php
                            $deadline = $intervention->deadline;
                            $isOverdue = $intervention->is_overdue;
                            $hoursLeft = $deadline ? now()->diffInHours($deadline, false) : null;
                            $isMine = $intervention->assigned_user_id === auth()->id();
                        @endphp
                        <div class="border-bottom px-3 py-3 {{ $isOverdue ? 'bg-danger bg-opacity-10' : ($intervention->priority === 'high' ? 'bg-warning bg-opacity-10' : '') }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 fw-bold">
                                        {{ $intervention->title }}
                                        @if($isMine)
                                            <span class="badge bg-success ms-2"><i class="bi bi-person-check me-1"></i>Assegnato a te</span>
                                        @endif
                                    </h6>
                                </div>
                                <div class="d-flex gap-1 flex-shrink-0 ms-2">
                                    <span class="badge {{ $statusClasses[$intervention->status] ?? 'bg-secondary' }}">
                                        {{ $statusLabels[$intervention->status] ?? $intervention->status }}
                                    </span>
                                    <span class="badge {{ $priClasses[$intervention->priority] ?? 'bg-secondary' }}">
                                        {{ $priLabels[$intervention->priority] ?? $intervention->priority }}
                                    </span>
                                </div>
                            </div>

                            {{-- Scadenza --}}
                            @if($deadline)
                                <div class="mb-2">
                                    @if($isOverdue)
                                        <span class="badge bg-danger px-3 py-2">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>SCADUTO — entro il {{ $deadline->format('d/m/Y H:i') }}
                                            <span class="ms-1">({{ $deadline->diffForHumans() }})</span>
                                        </span>
                                    @elseif($hoursLeft !== null && $hoursLeft <= 24)
                                        <span class="badge bg-warning text-dark px-3 py-2">
                                            <i class="bi bi-clock-fill me-1"></i>Scadenza: {{ $deadline->format('d/m/Y H:i') }}
                                            <span class="ms-1">({{ $deadline->diffForHumans() }})</span>
                                        </span>
                                    @else
                                        <span class="badge bg-light text-dark border px-3 py-2">
                                            <i class="bi bi-clock me-1"></i>Scadenza: {{ $deadline->format('d/m/Y H:i') }}
                                            <span class="ms-1 text-muted">({{ $deadline->diffForHumans() }})</span>
                                        </span>
                                    @endif
                                </div>
                            @endif

                            <div class="row g-2 small text-muted mb-2">
                                <div class="col-sm-6">
                                    @if($intervention->equipment)
                                        <i class="bi bi-gear me-1"></i><strong>{{ $intervention->equipment->name }}</strong>
                                        <br><i class="bi bi-geo-alt ms-3 me-1"></i>{{ $intervention->equipment->department->area->name ?? '' }} / {{ $intervention->equipment->department->name ?? '' }}
                                    @else
                                        <i class="bi bi-geo-alt me-1"></i><strong>{{ $intervention->area->name ?? '' }}</strong> / {{ $intervention->department->name ?? '' }}
                                    @endif
                                </div>
                                <div class="col-sm-6">
                                    @if($intervention->scheduled_date)
                                        <i class="bi bi-calendar-event me-1"></i>{{ $intervention->scheduled_date->format('d/m/Y') }}
                                        @if($intervention->scheduled_start_time)
                                            <span class="ms-1">ore {{ substr($intervention->scheduled_start_time, 0, 5) }}</span>
                                        @endif
                                    @endif
                                    @if($intervention->maintenanceRole)
                                        <br><i class="bi bi-person-badge me-1"></i>{{ $intervention->maintenanceRole->name }}
                                    @endif
                                </div>
                            </div>

                            @if($intervention->description)
                                <p class="mb-2 small text-muted">{{ Str::limit($intervention->description, 120) }}</p>
                            @endif

                            <a href="{{ route('interventions.show', $intervention) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>Dettagli
                            </a>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    @endif

    {{-- Interventi di Oggi --}}
    @if($todayInterventions->count() > 0)
        <div class="alert alert-info border-0 mb-4">
            <h5 class="alert-heading"><i class="bi bi-calendar-day me-2"></i>Tickets di Oggi</h5>
            <hr>
            @foreach($todayInterventions as $intervention)
                <div class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-3' : '' }}">
                    <div>
                        <h6 class="mb-1">{{ $intervention->title }}</h6>
                        <small><i class="bi bi-gear me-1"></i>{{ $intervention->equipment?->name ?? ($intervention->area?->name . ' / ' . $intervention->department?->name) }}</small>
                    </div>
                    <div class="text-end">
                        @if($intervention->scheduled_start_time)
                            <div class="badge bg-dark mb-1">{{ substr($intervention->scheduled_start_time, 0, 5) }}</div>
                        @endif
                        <div>
                            @if(auth()->user()->role === 'manutentore')
                                <a href="{{ route('interventions.reports.create', $intervention) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-file-earmark-plus me-1"></i>Crea Rapportino
                                </a>
                            @else
                                <a href="{{ route('interventions.show', $intervention) }}" class="btn btn-sm btn-light">Dettagli</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Statistiche Personali --}}
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded p-3">
                                <i class="bi bi-calendar-check text-primary" style="font-size: 24px;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0">{{ $myInterventions }}</h3>
                            <small class="text-muted">Miei Tickets</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded p-3">
                                <i class="bi bi-file-earmark-text text-success" style="font-size: 24px;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0">{{ $myReports }}</h3>
                            <small class="text-muted">Miei Rapportini</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Rapportini Personali --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-file-text me-2"></i>I Miei Rapportini</h5>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span>Completati</span>
                <span class="badge bg-success">{{ $myReportsCompleted }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span>Bozze</span>
                <span class="badge bg-secondary">{{ $myReportsDraft }}</span>
            </div>
        </div>
    </div>
    @if(\Illuminate\Support\Facades\Auth::user()->role !== 'manutentore')
    {{-- Azioni Rapide Operator --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Azioni Rapide</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-6">
                    <a href="{{ route('interventions.calendar') }}" class="btn btn-primary w-100 py-3">
                        <i class="bi bi-calendar3 d-block mb-2" style="font-size: 24px;"></i>
                        <small>Calendario</small>
                    </a>
                </div>
                <div class="col-6">
                    <a href="{{ route('interventions.index') }}" class="btn btn-light w-100 py-3">
                        <i class="bi bi-list-check d-block mb-2" style="font-size: 24px;"></i>
                        <small>Miei Tickets</small>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
@endif

@if(in_array($user->role, ['operator', 'manutentore']) && $errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = new bootstrap.Modal(document.getElementById('modalInterventoOrdinario'));
        modal.show();
    });
</script>
@endif

@if(in_array($user->role, ['operator', 'manutentore']))
{{-- Modal Apertura Rapida Intervento Ordinario --}}
<div class="modal fade" id="modalInterventoOrdinario" tabindex="-1" aria-labelledby="modalInterventoOrdinarioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content"
             x-data="{
                 selectedAreaId: '',
                 departments: {{ Js::from($quickDepartments->map(fn($d) => ['id' => $d->id, 'name' => $d->name, 'area_id' => $d->area_id])) }}
             }">
            <div class="modal-header">
                <h5 class="modal-title" id="modalInterventoOrdinarioLabel">
                    <i class="bi bi-wrench me-2"></i>Apri Ticket Ordinario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <form action="{{ route('interventions.quick-open') }}" method="POST">
                @csrf
                <div class="modal-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="quick_area_id" class="form-label">Area <span class="text-danger">*</span></label>
                        <select class="form-select" id="quick_area_id" name="area_id" required
                                x-model="selectedAreaId">
                            <option value="">Seleziona un'area...</option>
                            @foreach($quickAreas as $area)
                                <option value="{{ $area->id }}" {{ old('area_id') == $area->id ? 'selected' : '' }}>
                                    {{ $area->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="quick_department_id" class="form-label">Zona <span class="text-danger">*</span></label>
                        <select class="form-select" id="quick_department_id" name="department_id" required>
                            <option value="">Seleziona una zona...</option>
                            @foreach($quickDepartments as $dept)
                                <option value="{{ $dept->id }}"
                                        x-show="!selectedAreaId || selectedAreaId == {{ $dept->area_id }}"
                                        {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="quick_description" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="quick_description" name="description"
                                  rows="3" placeholder="Descrivi brevemente il ticket...">{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-wrench me-2"></i>Apri Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection
