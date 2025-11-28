@extends('layouts.app')

@section('title', 'Dettagli Intervento - Rapportini')

@section('page-title', 'Dettagli Intervento')

@section('content')
    <div class="mb-3">
        <a href="{{ route('interventions.calendar') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left me-2"></i>Torna al Calendario
        </a>
    </div>

@if(auth()->user()->role === 'operator')
    {{-- VISTA OPERATORE: Pulsante crea rapportino prioritario --}}
    <div class="card mb-3 border-primary">
        <div class="card-body text-center py-4">
            <h4 class="mb-3"><i class="bi bi-file-earmark-text me-2"></i>Crea Nuovo Rapportino</h4>
            <p class="text-muted mb-4">Compila il rapportino e carica le foto direttamente durante la creazione</p>
            <a href="{{ route('interventions.reports.create', $intervention) }}" class="btn btn-light btn-lg">
                <i class="bi bi-plus-circle me-2"></i>Inizia Rapportino
            </a>
        </div>
    </div>

    {{-- Info intervento compatte per operatore --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="bi bi-info-circle me-2"></i>{{ $intervention->title }}</h4>
        </div>
        <div class="card-body">
            <p><strong>Apparato:</strong> {{ $intervention->equipment->name }} ({{ $intervention->equipment->code }})</p>
            <p><strong>Data:</strong> {{ $intervention->scheduled_date->format('d/m/Y') }}
               @if($intervention->scheduled_start_time)
                   alle {{ substr($intervention->scheduled_start_time, 0, 5) }}
               @endif
            </p>
            @if($intervention->description)
                <p><strong>Descrizione:</strong><br>{{ $intervention->description }}</p>
            @endif
        </div>
    </div>
@else
    {{-- VISTA ADMIN/SUPERVISOR: Layout originale --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="bi bi-calendar-check me-2"></i>{{ $intervention->title }}</h4>
            <div>
                <a href="{{ route('interventions.edit', $intervention) }}" class="btn btn-warning btn-sm">
                    <i class="bi bi-pencil me-2"></i>Modifica
                </a>
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
                                <th>Apparato:</th>
                                <td>
                                    <strong>{{ $intervention->equipment->name }}</strong> ({{ $intervention->equipment->code }})<br>
                                    <small class="text-muted">
                                        {{ $intervention->equipment->department->area->name }} /
                                        {{ $intervention->equipment->department->name }}
                                    </small>
                                </td>
                            </tr>
                            <tr>
                                <th>Operatore Assegnato:</th>
                                <td>
                                    <i class="bi bi-person me-1"></i>{{ $intervention->assignedUser->name }}<br>
                                    <small class="text-muted">{{ $intervention->assignedUser->email }}</small>
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
                                <th style="width: 40%;">Data Pianificata:</th>
                                <td>
                                    <strong>{{ $intervention->scheduled_date->format('d/m/Y') }}</strong>
                                    <small class="text-muted">({{ $intervention->scheduled_date->diffForHumans() }})</small>
                                </td>
                            </tr>
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
                            <tr>
                                <th>Stato:</th>
                                <td>
                                    @php
                                        $statusClasses = [
                                            'planned' => 'bg-info',
                                            'in_progress' => 'bg-warning',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger'
                                        ];
                                        $statusLabels = [
                                            'planned' => 'Pianificato',
                                            'in_progress' => 'In corso',
                                            'completed' => 'Completato',
                                            'cancelled' => 'Annullato'
                                        ];
                                    @endphp
                                    <span class="badge {{ $statusClasses[$intervention->status] ?? 'bg-secondary' }}">
                                        {{ $statusLabels[$intervention->status] ?? $intervention->status }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Priorità:</th>
                                <td>
                                    @php
                                        $priorityClasses = [
                                            'low' => 'bg-secondary',
                                            'medium' => 'bg-info',
                                            'high' => 'bg-warning',
                                            'critical' => 'bg-danger'
                                        ];
                                        $priorityLabels = [
                                            'low' => 'Bassa',
                                            'medium' => 'Media',
                                            'high' => 'Alta',
                                            'critical' => 'Critica'
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
@endif

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
</script>
@endpush
