@extends('layouts.app')

@section('title', 'Dettagli Intervento - Rapportini')

@section('page-title', 'Dettagli Intervento')

@section('content')
    <div class="mb-3">
        <a href="{{ route('interventions.calendar') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left me-2"></i>Torna al Calendario
        </a>
    </div>

@if(auth()->user()->role === 'manutentore')
    <div class="card mb-4 border-warning border-3 shadow-lg" id="takeChargeCard">
        <div class="card-body text-center py-5 px-4 d-flex justify-content-center gap-3 flex-wrap align-items-center" id="takeChargeCardBody">
            <div id="takeChargeAlert" class="w-100" style="display:none;"></div>
            <form
                action="{{ route('interventions.take-charge', $intervention) }}"
                method="POST"
                class="flex-grow-1"
                id="takeChargeForm"
                style="{{ $intervention->preso_in_carico_at === null && !in_array($intervention->status, ['completed', 'cancelled']) ? '' : 'display:none;' }}"
            >
                @csrf
                <button type="submit" class="btn btn-warning fw-bold" style="font-size: 1.3rem; padding: 0.75rem 2rem; min-width: 300px;" id="takeChargeButton">
                    <i class="bi bi-hand-index-thumb me-2"></i>Prendi in Carico
                </button>
            </form>
            <a
                href="{{ route('interventions.reports.create', $intervention) }}"
                class="btn btn-light fw-bold"
                style="font-size: 1.2rem; padding: 0.75rem 1.5rem; {{ $intervention->assigned_user_id === auth()->id() && $intervention->preso_in_carico_at !== null ? '' : 'display:none;' }}"
                id="createReportLink"
            >
                <i class="bi bi-plus-circle me-2"></i>Crea Rapportino
            </a>
        </div>
    </div>
@endif

    {{-- VISTA DETTAGLIO (tutti i ruoli) --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>
                <i class="bi bi-calendar-check me-2"></i>{{ $intervention->title }}
                @if($intervention->tipo === 'pianificazione')
                    <span class="badge bg-primary ms-2"><i class="bi bi-gear me-1"></i>Pianificazione</span>
                @else
                    <span class="badge bg-warning text-dark ms-2"><i class="bi bi-wrench me-1"></i>Ordinario</span>
                @endif
            </h4>
            <div>
                @if(in_array(auth()->user()->role, ['admin', 'operator']))
                    <a href="{{ route('interventions.edit', $intervention) }}" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil me-2"></i>Modifica
                    </a>
                @endif
                <a href="{{ route('interventions.index') }}" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left me-2"></i>Torna alla Lista
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="bi bi-info-circle me-2"></i>Informazioni Generali</h5>
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <th style="width: 40%;">Titolo:</th>
                                <td><strong>{{ $intervention->title }}</strong></td>
                            </tr>
                            <tr>
                                <th>Descrizione:</th>
                                <td>{{ $intervention->description ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Destinazione:</th>
                                <td>
                                    @if($intervention->equipment)
                                        <strong>{{ $intervention->equipment->name }}</strong> ({{ $intervention->equipment->code }})<br>
                                        <small class="text-muted">
                                            {{ $intervention->equipment->department->area->name }} /
                                            {{ $intervention->equipment->department->name }}
                                        </small>
                                    @else
                                        <span class="badge bg-primary me-1">Area/Zona</span>
                                        <strong>{{ $intervention->area->name ?? '-' }}</strong> /
                                        {{ $intervention->department->name ?? '-' }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Operatore Assegnato:</th>
                                <td>
                                    @if($intervention->assignedUser)
                                        <i class="bi bi-person me-1"></i>{{ $intervention->assignedUser->name }}<br>
                                        <small class="text-muted">{{ $intervention->assignedUser->email }}</small>
                                    @elseif($intervention->maintenanceRole)
                                        <span class="text-muted"><i class="bi bi-person-badge me-1"></i>{{ $intervention->maintenanceRole->name }}</span>
                                        <br><small class="text-warning">Non ancora assegnato</small>
                                    @else
                                        <span class="text-muted">Non assegnato</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="col-md-6">
                    <h5 class="mb-3"><i class="bi bi-calendar3 me-2"></i>Pianificazione</h5>
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <th style="width: 40%;">Data:</th>
                                <td>
                                    @if($intervention->scheduled_date)
                                        <strong>{{ $intervention->scheduled_date->format('d/m/Y') }}</strong>
                                        <small class="text-muted">({{ $intervention->scheduled_date->diffForHumans() }})</small>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                            </tr>
                            @if($intervention->tipo === 'pianificazione')
                            <tr>
                                <th>Ora Inizio:</th>
                                <td>{{ $intervention->scheduled_start_time ? substr($intervention->scheduled_start_time, 0, 5) : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Durata Stimata:</th>
                                <td>
                                    @if($intervention->estimated_duration_minutes)
                                        {{ $intervention->estimated_duration_minutes }} minuti
                                        @if($intervention->estimated_duration_minutes >= 60)
                                            <small class="text-muted">({{ floor($intervention->estimated_duration_minutes / 60) }}h {{ $intervention->estimated_duration_minutes % 60 }}m)</small>
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <th>Stato:</th>
                                <td>
                                    @php
                                        $statusClasses = [
                                            'open' => 'bg-primary',
                                            'planned' => 'bg-info',
                                            'in_progress' => 'bg-warning',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger'
                                        ];
                                        $statusLabels = [
                                            'open' => 'Aperto',
                                            'planned' => 'Pianificato',
                                            'in_progress' => 'In corso',
                                            'completed' => 'Completato',
                                            'cancelled' => 'Annullato'
                                        ];
                                    @endphp
                                    <span class="badge {{ $statusClasses[$intervention->status] ?? 'bg-secondary' }}" id="statusBadge">
                                        {{ $statusLabels[$intervention->status] ?? $intervention->status }}
                                    </span>
                                    <span id="presoInCaricoBadge">
                                        @if($intervention->preso_in_carico_at)
                                            <span class="badge bg-success ms-1" title="{{ $intervention->preso_in_carico_at->format('d/m/Y H:i') }}">
                                                <i class="bi bi-hand-index-thumb me-1"></i>Preso in carico il {{ $intervention->preso_in_carico_at->format('d/m/Y H:i') }}
                                            </span>
                                        @elseif($intervention->assigned_user_id && !in_array($intervention->status, ['completed', 'cancelled']))
                                            <span class="badge bg-secondary ms-1">
                                                <i class="bi bi-hourglass-split me-1"></i>In attesa di presa in carico
                                            </span>
                                        @endif
                                    </span>
                                </td>
                            </tr>
                            @if($intervention->deadline)
                            <tr>
                                <th>Scadenza:</th>
                                <td>
                                    @if($intervention->is_overdue)
                                        <span class="badge bg-danger px-3 py-2">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>SCADUTO — {{ $intervention->deadline->format('d/m/Y H:i') }}
                                            ({{ $intervention->deadline->diffForHumans() }})
                                        </span>
                                    @elseif(now()->diffInHours($intervention->deadline, false) <= 24)
                                        <span class="badge bg-warning text-dark px-3 py-2">
                                            <i class="bi bi-clock-fill me-1"></i>{{ $intervention->deadline->format('d/m/Y H:i') }}
                                            ({{ $intervention->deadline->diffForHumans() }})
                                        </span>
                                    @else
                                        <span class="badge bg-light text-dark border px-2 py-2">
                                            <i class="bi bi-clock me-1"></i>{{ $intervention->deadline->format('d/m/Y H:i') }}
                                            <span class="text-muted">({{ $intervention->deadline->diffForHumans() }})</span>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <th>Priorità:</th>
                                <td>
                                    @php
                                        $priorityClasses = [
                                            'low'        => 'bg-secondary',
                                            'high'       => 'bg-warning',
                                            'fixed_date' => 'bg-purple',
                                        ];
                                        $priorityLabels = [
                                            'low'        => 'Bassa',
                                            'high'       => 'Alta',
                                            'fixed_date' => 'Data fissa',
                                        ];
                                    @endphp
                                    <span class="badge {{ $priorityClasses[$intervention->priority] ?? 'bg-secondary' }}">
                                        {{ $priorityLabels[$intervention->priority] ?? $intervention->priority }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            @if($intervention->notes)
                <hr class="my-4">
                <div class="row">
                    <div class="col-12">
                        <h5 class="mb-3"><i class="bi bi-chat-left-text me-2"></i>Note</h5>
                        <div class="card bg-light">
                            <div class="card-body">
                                {{ $intervention->notes }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if($intervention->completed_at)
                <hr class="my-4">
                <div class="row">
                    <div class="col-12">
                        <h5 class="mb-3"><i class="bi bi-check-circle me-2"></i>Completamento</h5>
                        <div class="card bg-success bg-opacity-10">
                            <div class="card-body">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                Intervento completato il <strong>{{ $intervention->completed_at->format('d/m/Y H:i') }}</strong>
                                <small class="text-muted">({{ $intervention->completed_at->diffForHumans() }})</small>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <hr class="my-4">

            <div class="row">
                <div class="col-12">
                    <h5 class="mb-3"><i class="bi bi-paperclip me-2"></i>Foto e Documenti</h5>
                    @livewire('media-manager', ['mediableType' => 'App\\Models\\Intervention', 'mediableId' => $intervention->id])
                </div>
            </div>

            <hr class="my-4">

            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">
                        <i class="bi bi-clock-history me-1"></i>
                        Creato il {{ $intervention->created_at->format('d/m/Y H:i') }}
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">
                        <i class="bi bi-pencil-square me-1"></i>
                        Ultima modifica: {{ $intervention->updated_at->format('d/m/Y H:i') }}
                    </small>
                </div>
            </div>
        </div>
    </div>

{{-- RAPPORTINI (visibile a tutti) --}}
<div class="card">
    <div class="card-header">
        <h4><i class="bi bi-journal-text me-2"></i>Rapportini ({{ $intervention->reports->count() }})</h4>
    </div>
    <div class="card-body">
        @if($intervention->reports->count() > 0)
            {{-- Filtri --}}
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="filterStatus" class="form-label">Stato</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">Tutti</option>
                        <option value="Completato">Completato</option>
                        <option value="Bozza">Bozza</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filterOperator" class="form-label">Operatore</label>
                    <select id="filterOperator" class="form-select form-select-sm">
                        <option value="">Tutti</option>
                        @foreach($intervention->reports->unique('user_id') as $report)
                            <option value="{{ $report->user->name }}">{{ $report->user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filterDate" class="form-label">Cerca per data</label>
                    <input type="text" id="filterDate" class="form-control form-control-sm" placeholder="gg/mm/aaaa">
                </div>
            </div>

            {{-- Tabella rapportini --}}
            <div class="table-responsive">
                <table id="reportsTable" class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Orario</th>
                            <th>Operatore</th>
                            <th>Stato</th>
                            <th>Allegati</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($intervention->reports as $report)
                            <tr>
                                <td>{{ $report->report_date->format('d/m/Y') }}</td>
                                <td>
                                    @if($report->start_time && $report->end_time)
                                        {{ substr($report->start_time, 0, 5) }} - {{ substr($report->end_time, 0, 5) }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <i class="bi bi-person me-1"></i>{{ $report->user->name }}
                                </td>
                                <td>
                                    @if($report->status === 'completed')
                                        <span class="badge bg-success">Completato</span>
                                    @else
                                        <span class="badge bg-secondary">Bozza</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info">{{ $report->media->count() }}</span>
                                </td>
                                <td>
                                    <button class="btn btn-light btn-sm" onclick="viewReport({{ $report->id }})" title="Visualizza dettagli">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    @if(auth()->user()->role === 'admin' || $report->user_id === auth()->id())
                                        <a href="{{ route('interventions.reports.edit', [$intervention, $report]) }}" class="btn btn-warning btn-sm" title="Modifica">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('interventions.reports.destroy', [$intervention, $report]) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Sei sicuro di voler eliminare questo rapportino?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Elimina">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox" style="font-size: 48px;"></i>
                <p class="mt-3">Nessun rapportino disponibile</p>
            </div>
        @endif
    </div>
</div>

{{-- STORICO AZIONI --}}
@php
    $interventionLogs = $intervention->logs()->with('user:id,name')->limit(100)->get();
@endphp
<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="bi bi-journals me-2"></i>Storico azioni ({{ $interventionLogs->count() }})</h4>
        @if(auth()->user()->role === 'admin')
            <a href="{{ route('intervention_logs.index', ['intervention_id' => $intervention->id]) }}" class="btn btn-sm btn-light">
                <i class="bi bi-box-arrow-up-right me-1"></i>Apri in pannello
            </a>
        @endif
    </div>
    <div class="card-body p-0">
        @if($interventionLogs->isEmpty())
            <div class="text-center py-4 text-muted">
                <i class="bi bi-inbox" style="font-size: 36px;"></i>
                <p class="mt-2 mb-0">Nessuna azione registrata.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 170px;">Data / ora</th>
                            <th>Utente</th>
                            <th>Azione</th>
                            <th>Dettagli</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($interventionLogs as $log)
                            <tr>
                                <td><small class="text-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</small></td>
                                <td>
                                    @if($log->user)
                                        <i class="bi bi-person me-1"></i>{{ $log->user->name }}
                                    @else
                                        <span class="text-muted"><i class="bi bi-cpu me-1"></i>Sistema</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark">{{ \App\Support\InterventionLogActions::label($log->action) }}</span>
                                </td>
                                <td>
                                    @if(!empty($log->metadata))
                                        <code class="small text-muted">{{ json_encode($log->metadata, JSON_UNESCAPED_UNICODE) }}</code>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Modal Dettagli Rapportino --}}
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportModalLabel">Dettagli Rapportino</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="reportModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Caricamento...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
<style>
    .media-thumbnail {
        cursor: pointer;
        transition: transform 0.2s;
        border: 2px solid #dee2e6;
    }
    .media-thumbnail:hover {
        transform: scale(1.05);
        border-color: #0dcaf0;
    }
    .file-card {
        transition: all 0.2s;
        border: 1px solid #dee2e6;
    }
    .file-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        border-color: #0dcaf0;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>

<script>
$(document).ready(function() {
    // Configura Lightbox2
    lightbox.option({
        'resizeDuration': 200,
        'wrapAround': true,
        'albumLabel': 'Immagine %1 di %2',
        'fadeDuration': 300,
        'imageFadeDuration': 300
    });

    @if($intervention->reports->count() > 0)
        // Inizializza DataTable
        var table = $('#reportsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/it-IT.json'
            },
            order: [[0, 'desc']], // Ordina per data decrescente
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: [5] } // Disabilita ordinamento su colonna Azioni
            ]
        });

        // Filtro per stato
        $('#filterStatus').on('change', function() {
            table.column(3).search(this.value).draw();
        });

        // Filtro per operatore
        $('#filterOperator').on('change', function() {
            table.column(2).search(this.value).draw();
        });

        // Filtro per data
        $('#filterDate').on('keyup', function() {
            table.column(0).search(this.value).draw();
        });
    @endif
});

// Funzione per visualizzare dettagli rapportino in modale
function viewReport(reportId) {
    var modal = new bootstrap.Modal(document.getElementById('reportModal'));
    var modalBody = document.getElementById('reportModalBody');

    // Mostra spinner
    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Caricamento...</span>
            </div>
        </div>
    `;

    modal.show();

    // Carica i dati del rapportino
    fetch(`/api/reports/${reportId}`)
        .then(response => response.json())
        .then(data => {
            var html = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6><i class="bi bi-calendar3 me-2"></i>Data Rapportino</h6>
                        <p>${data.report_date}</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-clock me-2"></i>Orario</h6>
                        <p>${data.time_range || 'Non specificato'}</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6><i class="bi bi-person me-2"></i>Operatore</h6>
                        <p>${data.user_name}</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-info-circle me-2"></i>Stato</h6>
                        <p><span class="badge ${data.status === 'completed' ? 'bg-success' : 'bg-secondary'}">${data.status_label}</span></p>
                    </div>
                </div>
            `;

            if (data.activities) {
                html += `
                    <div class="mb-3">
                        <h6><i class="bi bi-list-check me-2"></i>Attività Svolte</h6>
                        <div class="card bg-light">
                            <div class="card-body">${data.activities}</div>
                        </div>
                    </div>
                `;
            }

            if (data.notes) {
                html += `
                    <div class="mb-3">
                        <h6><i class="bi bi-chat-left-text me-2"></i>Note</h6>
                        <div class="card bg-light">
                            <div class="card-body">${data.notes}</div>
                        </div>
                    </div>
                `;
            }

            // Allegati
            if (data.media && data.media.length > 0) {
                // Separa immagini da altri file
                var images = data.media.filter(item => item.is_image);
                var documents = data.media.filter(item => !item.is_image);

                // Sezione Immagini
                if (images.length > 0) {
                    html += `
                        <div class="mb-4">
                            <h6><i class="bi bi-image me-2"></i>Foto (${images.length})</h6>
                            <div class="row g-2">
                    `;
                    images.forEach(function(item, index) {
                        html += `
                            <div class="col-md-3 col-6">
                                <a href="${item.url}" data-lightbox="report-gallery" data-title="${item.file_name}${item.description ? ' - ' + item.description : ''}">
                                    <img src="${item.url}" class="img-fluid rounded media-thumbnail" style="width: 100%; height: 140px; object-fit: cover;" alt="${item.file_name}">
                                </a>
                                <div class="text-center mt-1">
                                    <small class="text-muted d-block text-truncate">${item.file_size}</small>
                                    <a href="${item.url}" download="${item.file_name}" class="btn btn-sm btn-light mt-1">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </div>
                            </div>
                        `;
                    });
                    html += `
                            </div>
                        </div>
                    `;
                }

                // Sezione Documenti
                if (documents.length > 0) {
                    html += `
                        <div class="mb-3">
                            <h6><i class="bi bi-file-earmark me-2"></i>Documenti (${documents.length})</h6>
                            <div class="list-group">
                    `;
                    documents.forEach(function(item) {
                        // Determina l'icona in base al tipo file
                        var icon = 'file-earmark';
                        var iconColor = 'text-secondary';
                        if (item.file_type.includes('pdf')) {
                            icon = 'file-earmark-pdf';
                            iconColor = 'text-danger';
                        } else if (item.file_type.includes('zip') || item.file_type.includes('rar')) {
                            icon = 'file-earmark-zip';
                            iconColor = 'text-warning';
                        } else if (item.file_type.includes('word') || item.file_type.includes('document')) {
                            icon = 'file-earmark-word';
                            iconColor = 'text-primary';
                        } else if (item.file_type.includes('excel') || item.file_type.includes('sheet')) {
                            icon = 'file-earmark-excel';
                            iconColor = 'text-success';
                        }

                        html += `
                            <div class="list-group-item file-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center flex-grow-1">
                                        <i class="bi bi-${icon} ${iconColor} me-3" style="font-size: 32px;"></i>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">${item.file_name}</h6>
                                            <small class="text-muted">
                                                <i class="bi bi-hdd me-1"></i>${item.file_size}
                                                <i class="bi bi-calendar ms-2 me-1"></i>${item.created_at}
                                            </small>
                                            ${item.description ? `<p class="mb-0 mt-1 small text-muted"><i class="bi bi-chat-left-text me-1"></i>${item.description}</p>` : ''}
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="${item.url}" target="_blank" class="btn btn-sm btn-light" title="Visualizza">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="${item.url}" download="${item.file_name}" class="btn btn-sm btn-primary" title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += `
                            </div>
                        </div>
                    `;
                }
            } else {
                html += `
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Nessun allegato disponibile
                    </div>
                `;
            }

            modalBody.innerHTML = html;
        })
        .catch(error => {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>Errore nel caricamento dei dati
                </div>
            `;
        });
}

(function () {
    const form = document.getElementById('takeChargeForm');
    if (!form) return;

    const alertBox = document.getElementById('takeChargeAlert');
    const button = document.getElementById('takeChargeButton');
    const createReportLink = document.getElementById('createReportLink');
    const statusBadge = document.getElementById('statusBadge');
    const presoBadge = document.getElementById('presoInCaricoBadge');

    const statusClasses = {
        'open': 'bg-primary',
        'planned': 'bg-info',
        'in_progress': 'bg-warning',
        'completed': 'bg-success',
        'cancelled': 'bg-danger',
    };

    function showAlert(type, message) {
        alertBox.style.display = '';
        alertBox.innerHTML = `<div class="alert alert-${type} mb-3"><i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>${message}</div>`;
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        button.disabled = true;
        const originalHtml = button.innerHTML;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Attendere...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json();

            if (!response.ok || !data.ok) {
                showAlert('danger', data.message || 'Errore durante la presa in carico.');
                button.disabled = false;
                button.innerHTML = originalHtml;
                return;
            }

            form.style.display = 'none';

            if (createReportLink) {
                createReportLink.style.display = '';
            }

            if (statusBadge && data.intervention) {
                const newClass = statusClasses[data.intervention.status] || 'bg-secondary';
                statusBadge.className = 'badge ' + newClass;
                statusBadge.id = 'statusBadge';
                statusBadge.textContent = data.intervention.status_label;
            }

            if (presoBadge && data.intervention) {
                presoBadge.innerHTML = `<span class="badge bg-success ms-1" title="${data.intervention.preso_in_carico_at}"><i class="bi bi-hand-index-thumb me-1"></i>Preso in carico il ${data.intervention.preso_in_carico_at}</span>`;
            }

            showAlert('success', data.message);
        } catch (error) {
            showAlert('danger', 'Errore di rete. Riprova.');
            button.disabled = false;
            button.innerHTML = originalHtml;
        }
    });
})();
</script>
@endpush
