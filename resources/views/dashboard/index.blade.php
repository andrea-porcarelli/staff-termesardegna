@extends('layouts.app')

@section('title', 'Dashboard - Rapportini')

@section('page-title', 'Dashboard')

@section('content')

{{-- Welcome Section --}}
<div class="alert alert-light border-0 mb-4">
    <h4 class="mb-1">Benvenuto, {{ explode(' ', $user->name)[0] }}!</h4>
    <p class="mb-0 text-muted">
        @if($user->role === 'admin')
            Gestisci l'intero sistema di rapportini e interventi.
        @elseif($user->role === 'supervisor')
            Monitora gli interventi e supervisiona i rapportini degli operatori.
        @else
            Visualizza i tuoi interventi e crea rapportini operativi.
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
                            <small class="text-muted">Interventi Totali</small>
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
                    <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Stato Interventi</h5>
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
                    <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Prossimi Interventi (7 gg)</h5>
                    <a href="{{ route('interventions.calendar') }}" class="btn btn-sm btn-light">Vedi Tutti</a>
                </div>
                <div class="card-body p-0">
                    @if($upcomingInterventions->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-x" style="font-size: 32px;"></i>
                            <p class="mt-2 mb-0">Nessun intervento programmato</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($upcomingInterventions as $intervention)
                                <a href="{{ route('interventions.show', $intervention) }}" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $intervention->title }}</h6>
                                            <small class="text-muted">
                                                <i class="bi bi-gear me-1"></i>{{ $intervention->equipment->name }}
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
                        <small>Nuovo Intervento</small>
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

@elseif($user->role === 'supervisor')
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
                            <small class="text-muted">Interventi Totali</small>
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
                    <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Stato Interventi</h5>
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
                    <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Prossimi Interventi (7 gg)</h5>
                    <a href="{{ route('interventions.calendar') }}" class="btn btn-sm btn-light">Vedi Tutti</a>
                </div>
                <div class="card-body p-0">
                    @if($upcomingInterventions->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-x" style="font-size: 32px;"></i>
                            <p class="mt-2 mb-0">Nessun intervento programmato</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($upcomingInterventions as $intervention)
                                <a href="{{ route('interventions.show', $intervention) }}" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $intervention->title }}</h6>
                                            <small class="text-muted">
                                                <i class="bi bi-gear me-1"></i>{{ $intervention->equipment->name }}
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
                        <small>Nuovo Intervento</small>
                    </a>
                </div>
                <div class="col-md-4 col-6">
                    <a href="{{ route('interventions.index') }}" class="btn btn-light w-100 py-3">
                        <i class="bi bi-list-check d-block mb-2" style="font-size: 24px;"></i>
                        <small>Tutti gli Interventi</small>
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
    {{-- OPERATOR DASHBOARD --}}

    {{-- Interventi di Oggi --}}
    @if($todayInterventions->count() > 0)
        <div class="alert alert-info border-0 mb-4">
            <h5 class="alert-heading"><i class="bi bi-calendar-day me-2"></i>Interventi di Oggi</h5>
            <hr>
            @foreach($todayInterventions as $intervention)
                <div class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-3' : '' }}">
                    <div>
                        <h6 class="mb-1">{{ $intervention->title }}</h6>
                        <small><i class="bi bi-gear me-1"></i>{{ $intervention->equipment->name }}</small>
                    </div>
                    <div class="text-end">
                        @if($intervention->scheduled_start_time)
                            <div class="badge bg-dark mb-1">{{ substr($intervention->scheduled_start_time, 0, 5) }}</div>
                        @endif
                        <div>
                            <a href="{{ route('interventions.show', $intervention) }}" class="btn btn-sm btn-light">Dettagli</a>
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
                            <small class="text-muted">Miei Interventi</small>
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

    {{-- Stato Interventi Personali --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Stato dei Miei Interventi</h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-4">
                    <div class="p-2">
                        <h3 class="mb-1 text-info">{{ $myInterventionsPlanned }}</h3>
                        <small class="text-muted">Pianificati</small>
                    </div>
                </div>
                <div class="col-4 border-start border-end">
                    <div class="p-2">
                        <h3 class="mb-1 text-warning">{{ $myInterventionsInProgress }}</h3>
                        <small class="text-muted">In Corso</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2">
                        <h3 class="mb-1 text-success">{{ $myInterventionsCompleted }}</h3>
                        <small class="text-muted">Completati</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Prossimi Interventi Personali --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Prossimi Interventi (7 gg)</h5>
            <a href="{{ route('interventions.calendar') }}" class="btn btn-sm btn-light">Calendario</a>
        </div>
        <div class="card-body p-0">
            @if($myUpcomingInterventions->isEmpty())
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-calendar-x" style="font-size: 32px;"></i>
                    <p class="mt-2 mb-0">Nessun intervento programmato</p>
                </div>
            @else
                <div class="list-group list-group-flush">
                    @foreach($myUpcomingInterventions as $intervention)
                        <a href="{{ route('interventions.show', $intervention) }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ $intervention->title }}</h6>
                                    <small class="text-muted">
                                        <i class="bi bi-gear me-1"></i>{{ $intervention->equipment->name }}
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
                        <small>Miei Interventi</small>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif

@endsection
