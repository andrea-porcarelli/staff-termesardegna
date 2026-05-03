@extends('layouts.app')

@section('title', 'Rapportini - Rapportini')

@section('page-title', 'Rapportini')

@section('content')

@php
    $statusColors = ['draft' => 'bg-secondary', 'completed' => 'bg-success', 'chiuso' => 'bg-dark'];
    $statusLabels = ['draft' => 'Bozza', 'completed' => 'Completato', 'chiuso' => 'Chiuso'];
@endphp

{{-- Filtri --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtri</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('reports.index') }}">
            <div class="row g-3 mb-2">
                <div class="col-12">
                    <label class="form-label">Cerca</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" name="q" value="{{ request('q') }}"
                               class="form-control"
                               placeholder="Titolo, descrizione, data, attività, note…">
                        @if(request('q'))
                            <a href="{{ route('reports.index', request()->except('q')) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Stato</label>
                    <select name="status" class="form-select">
                        <option value="">Tutti</option>
                        <option value="draft"     {{ request('status') === 'draft'     ? 'selected' : '' }}>Bozza</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completato</option>
                        <option value="chiuso"    {{ request('status') === 'chiuso'    ? 'selected' : '' }}>Chiuso</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Operatore</label>
                    <select name="user_id" class="form-select">
                        <option value="">Tutti</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Data dal</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Data al</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-light w-100">
                        <i class="bi bi-search me-1"></i>Filtra
                    </button>
                    @if(request()->hasAny(['status','user_id','date_from','date_to','q']))
                        <a href="{{ route('reports.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Tabella --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4><i class="bi bi-journal-text me-2"></i>Lista Rapportini</h4>
        <span class="badge bg-secondary">{{ $reports->total() }} totali</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Ticket</th>
                        <th>Destinazione</th>
                        <th>Operatore</th>
                        <th>Orario</th>
                        <th>Stato</th>
                        <th class="text-center">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                        <tr>
                            <td class="text-nowrap">
                                {{ $report->report_date->format('d/m/Y') }}
                            </td>
                            <td>
                                @if($report->intervention)
                                    <strong>{{ $report->intervention->title }}</strong>
                                @else
                                    <span class="badge bg-info text-dark">
                                        <i class="bi bi-journal me-1"></i>Rapportino libero
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($report->intervention)
                                    @if($report->intervention->equipment)
                                        <small><i class="bi bi-gear me-1"></i>{{ $report->intervention->equipment->name }}</small>
                                    @else
                                        <small><i class="bi bi-building me-1"></i>{{ $report->intervention->area?->name }} / {{ $report->intervention->department?->name }}</small>
                                    @endif
                                @else
                                    <small class="text-muted">—</small>
                                @endif
                            </td>
                            <td>
                                <i class="bi bi-person me-1"></i>{{ $report->user->name }}
                            </td>
                            <td class="text-nowrap">
                                @if($report->start_time && $report->end_time)
                                    {{ substr($report->start_time, 0, 5) }} – {{ substr($report->end_time, 0, 5) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $statusColors[$report->status] ?? 'bg-secondary' }}">
                                    {{ $statusLabels[$report->status] ?? $report->status }}
                                </span>
                            </td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-info btn-sm" title="Dettagli rapportino"
                                        onclick="viewReport({{ $report->id }})">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size:48px;color:#ccc"></i>
                                <p class="text-muted mt-2">Nessun rapportino trovato</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($reports->hasPages())
        <div class="card-body border-top">
            {{ $reports->links() }}
        </div>
    @endif
</div>

{{-- Modale dettagli rapportino --}}
<div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" id="reportModalHeader">
                <h5 class="modal-title" id="reportModalTitle">Dettagli Rapportino</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reportModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const statusColors = { draft: '#6c757d', completed: '#198754', chiuso: '#212529' };
const priorityBadge = { low: 'bg-secondary', high: 'bg-warning', fixed_date: 'bg-purple' };

function viewReport(reportId) {
    const modal = new bootstrap.Modal(document.getElementById('reportModal'));
    document.getElementById('reportModalBody').innerHTML = `
        <div class="text-center py-5"><div class="spinner-border" role="status"></div></div>`;
    modal.show();

    fetch(`/api/reports/${reportId}`)
        .then(r => r.json())
        .then(d => {
            const iv = d.intervention;
            const header = document.getElementById('reportModalHeader');
            const color  = statusColors[d.status] || '#6c757d';
            header.style.background = color;

            document.getElementById('reportModalTitle').textContent =
                (iv ? iv.title : 'Rapportino libero') + ' — ' + d.report_date;

            const interventionBlock = iv ? `
            <div class="mb-4 p-3 rounded" style="background:#f8f9fa;border-left:4px solid ${color}">
                <div class="row g-2">
                    <div class="col-12">
                        <div class="text-muted small mb-1"><i class="bi bi-calendar-check me-1"></i><strong>Ticket richiesto</strong></div>
                        <div class="fw-semibold">${iv.title}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small"><i class="bi bi-gear me-1"></i>Destinazione</div>
                        <div>${iv.destination}</div>
                        ${iv.location ? `<div class="text-muted small">${iv.location}</div>` : ''}
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i>Data pianificata</div>
                        <div>${iv.scheduled_date || '—'}${iv.scheduled_time ? ' alle ' + iv.scheduled_time : ''}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small"><i class="bi bi-flag me-1"></i>Priorità</div>
                        <span class="badge ${priorityBadge[iv.priority] || 'bg-secondary'}">${iv.priority_label}</span>
                    </div>
                    ${iv.description ? `
                    <div class="col-12">
                        <div class="text-muted small"><i class="bi bi-text-paragraph me-1"></i>Descrizione</div>
                        <div>${iv.description}</div>
                    </div>` : ''}
                    ${iv.notes ? `
                    <div class="col-12">
                        <div class="text-muted small"><i class="bi bi-sticky me-1"></i>Note ticket</div>
                        <div>${iv.notes}</div>
                    </div>` : ''}
                </div>
            </div>` : `
            <div class="mb-4 p-3 rounded" style="background:#f8f9fa;border-left:4px solid ${color}">
                <div class="text-muted small mb-1"><i class="bi bi-journal me-1"></i><strong>Rapportino libero</strong></div>
                <div class="text-muted small">Non collegato a un ticket.</div>
            </div>`;

            let html = interventionBlock + `

            {{-- Dati rapportino --}}
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="p-3 rounded bg-light h-100">
                        <div class="text-muted small mb-1"><i class="bi bi-person me-1"></i>Compilato da</div>
                        <div class="fw-semibold">${d.user_name}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded bg-light h-100">
                        <div class="text-muted small mb-1"><i class="bi bi-calendar3 me-1"></i>Data rapportino</div>
                        <div class="fw-semibold">${d.report_date}</div>
                        ${d.time_range ? `<div class="text-muted small"><i class="bi bi-clock me-1"></i>${d.time_range}</div>` : ''}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded bg-light h-100">
                        <div class="text-muted small mb-1"><i class="bi bi-info-circle me-1"></i>Stato</div>
                        <span class="badge" style="background:${color}">${d.status_label}</span>
                    </div>
                </div>
                ${d.activities ? `
                <div class="col-12">
                    <div class="p-3 rounded bg-light">
                        <div class="text-muted small mb-1"><i class="bi bi-list-check me-1"></i>Attività svolte</div>
                        <div style="white-space:pre-wrap">${d.activities}</div>
                    </div>
                </div>` : ''}
                ${d.notes ? `
                <div class="col-12">
                    <div class="p-3 rounded" style="background:#fff3cd">
                        <div class="text-muted small mb-1"><i class="bi bi-sticky me-1"></i>Note rapportino</div>
                        <div style="white-space:pre-wrap">${d.notes}</div>
                    </div>
                </div>` : ''}
            </div>`;

            if (d.media && d.media.length > 0) {
                const images = d.media.filter(m => m.is_image);
                const docs   = d.media.filter(m => !m.is_image);
                html += `<hr class="my-3">`;
                if (images.length) {
                    html += `<div class="mb-3"><div class="text-muted small mb-2"><i class="bi bi-images me-1"></i>Foto (${images.length})</div><div class="row g-2">`;
                    images.forEach(m => {
                        html += `<div class="col-4 col-md-3">
                            <a href="${m.url}" target="_blank">
                                <img src="${m.url}" class="img-fluid rounded" style="height:90px;width:100%;object-fit:cover">
                            </a>
                        </div>`;
                    });
                    html += `</div></div>`;
                }
                if (docs.length) {
                    html += `<div><div class="text-muted small mb-2"><i class="bi bi-paperclip me-1"></i>Documenti (${docs.length})</div>`;
                    docs.forEach(m => {
                        html += `<div class="d-flex justify-content-between align-items-center p-2 border rounded mb-1">
                            <span class="small">${m.file_name} <span class="text-muted">(${m.file_size})</span></span>
                            <a href="${m.url}" target="_blank" class="btn btn-sm btn-light"><i class="bi bi-download"></i></a>
                        </div>`;
                    });
                    html += `</div>`;
                }
            }

            document.getElementById('reportModalBody').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('reportModalBody').innerHTML =
                `<div class="alert alert-danger">Errore nel caricamento dei dati.</div>`;
        });
}
</script>
@endpush

@endsection
