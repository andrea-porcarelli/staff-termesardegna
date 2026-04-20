@extends('layouts.app')

@section('title', 'Calendario Interventi - Rapportini')

@section('page-title', 'Calendario Interventi')

@section('content')

{{-- VISTA MOBILE: Lista per settimane (nascosta, sostituita da FullCalendar) --}}
<div class="mobile-list-view" style="display:none!important">
    @if(count($weeklyInterventions) > 0)
        @foreach($weeklyInterventions as $weekKey => $week)
            <div class="card mb-3">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar-week me-2"></i>
                        Settimana {{ $week['start']->format('d/m') }} - {{ $week['end']->format('d/m/Y') }}
                    </h5>
                </div>
                <div class="card-body p-0">
                    @foreach($week['days'] as $dayKey => $day)
                        <div class="day-section border-bottom">
                            <div class="day-header p-3 bg-light">
                                <h6 class="mb-0">
                                    <i class="bi bi-calendar-day me-2"></i>
                                    <strong>{{ $day['date']->isoFormat('dddd D MMMM') }}</strong>
                                </h6>
                            </div>
                            <div class="interventions-list">
                                @foreach($day['interventions'] as $intervention)
                                    <a href="{{ route('interventions.reports.create', $intervention) }}" class="intervention-item d-block p-3 text-decoration-none text-dark border-bottom hover-bg">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">
                                                    @php
                                                        $priorityIcons = [
                                                            'low'        => 'circle',
                                                            'high'       => 'exclamation-circle',
                                                            'fixed_date' => 'calendar-event',
                                                        ];
                                                        $priorityColors = [
                                                            'low'        => 'text-secondary',
                                                            'high'       => 'text-warning',
                                                            'fixed_date' => 'text-purple',
                                                        ];
                                                    @endphp
                                                    <i class="bi bi-{{ $priorityIcons[$intervention->priority] ?? 'circle' }} {{ $priorityColors[$intervention->priority] ?? 'text-secondary' }} me-2"></i>
                                                    {{ $intervention->title }}
                                                </h6>
                                                <small class="text-muted d-block">
                                                    <i class="bi bi-gear me-1"></i>{{ $intervention->equipment?->name ?? ($intervention->area?->name . ' / ' . $intervention->department?->name) }}
                                                </small>
                                            </div>
                                            <div class="text-end ms-2">
                                                @if($intervention->scheduled_start_time)
                                                    <div class="badge bg-dark">
                                                        <i class="bi bi-clock me-1"></i>{{ substr($intervention->scheduled_start_time, 0, 5) }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2 align-items-center">
                                            @php
                                                $statusClasses = [
                                                    'open' => 'bg-primary',
                                                    'planned' => 'bg-info',
                                                    'in_progress' => 'bg-warning text-dark',
                                                    'completed' => 'bg-success',
                                                    'cancelled' => 'bg-secondary'
                                                ];
                                                $statusLabels = [
                                                    'open' => 'Aperto',
                                                    'planned' => 'Pianificato',
                                                    'in_progress' => 'In corso',
                                                    'completed' => 'Completato',
                                                    'cancelled' => 'Annullato'
                                                ];
                                            @endphp
                                            <span class="badge {{ $statusClasses[$intervention->status] ?? 'bg-secondary' }}">
                                                {{ $statusLabels[$intervention->status] ?? $intervention->status }}
                                            </span>

                                            @if(auth()->user()->role !== 'operator')
                                                <small class="text-muted">
                                                    <i class="bi bi-person me-1"></i>{{ $intervention->assignedUser->name }}
                                                </small>
                                            @endif
                                        </div>

                                        @if($intervention->estimated_duration_minutes)
                                            <small class="text-muted d-block mt-2">
                                                <i class="bi bi-hourglass-split me-1"></i>
                                                Durata: {{ $intervention->estimated_duration_minutes }} min
                                            </small>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-calendar-x" style="font-size: 48px; color: #ccc;"></i>
                <p class="text-muted mt-3">Nessun intervento pianificato per i prossimi 20 giorni</p>
            </div>
        </div>
    @endif
</div>

{{-- VISTA DESKTOP: Calendario FullCalendar --}}
<div class="desktop-calendar-view">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="bi bi-calendar3 me-2"></i>Calendario Pianificazione</h4>
            <div>
                @if(in_array(auth()->user()->role, ['admin', 'operator']))
                    <a href="{{ route('interventions.create') }}" class="btn btn-light btn-sm">
                        <i class="bi bi-plus-circle me-2"></i>Nuovo Intervento
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body">
            <!-- Legenda -->
            <div class="mb-3 p-3 bg-light rounded">
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted d-block mb-2"><strong>Stato:</strong></small>
                        <span class="badge me-2" style="background-color: #0d6efd;">Aperto</span>
                        <span class="badge me-2" style="background-color: #0dcaf0;">Pianificato</span>
                        <span class="badge me-2" style="background-color: #ffc107;">In corso</span>
                        <span class="badge me-2" style="background-color: #198754;">Completato</span>
                        <span class="badge me-2" style="background-color: #6c757d;">Annullato</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block mb-2"><strong>Priorità (bordo):</strong></small>
                        <span class="badge bg-secondary me-2">Bassa</span>
                        <span class="badge bg-info me-2">Media</span>
                        <span class="badge bg-warning me-2">Alta</span>
                        <span class="badge bg-danger me-2">Critica</span>
                    </div>
                </div>
            </div>

            <!-- Calendario -->
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Modal per dettagli evento -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" id="eventModalHeader">
                    <h5 class="modal-title" id="eventModalLabel">Dettagli Intervento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="eventModalBody">
                    <!-- Contenuto dinamico -->
                </div>
                <div class="modal-footer" id="eventModalFooter">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<style>
    .desktop-calendar-view {
        display: block;
    }

    /* Stili lista mobile */
    .day-section:last-child {
        border-bottom: none !important;
    }

    .intervention-item {
        transition: background-color 0.2s ease;
    }

    .intervention-item:hover,
    .intervention-item:active {
        background-color: #f8f9fa;
    }

    .intervention-item:last-child {
        border-bottom: none !important;
    }

    @media (max-width: 767px) {
        .fc-toolbar-title {
            font-size: 1rem !important;
        }
        .fc-toolbar {
            gap: 0.5rem;
        }
    }

    /* Stili calendario desktop */
    #calendar {
        max-width: 100%;
        margin: 0 auto;
    }

    .fc-event {
        cursor: pointer;
        border-width: 3px !important;
    }

    .fc-event:hover {
        opacity: 0.8;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/it.global.min.js"></script>
<script>
const userRole = '{{ auth()->user()->role }}';

const statusLabels = { open: 'Aperto', planned: 'Pianificato', in_progress: 'In corso', completed: 'Completato', cancelled: 'Annullato' };
const statusColors = { open: '#0d6efd', planned: '#0dcaf0', in_progress: '#ffc107', completed: '#198754', cancelled: '#6c757d' };
const priorityLabels = { low: 'Bassa', high: 'Alta', fixed_date: 'Data fissa' };
const priorityColors = { low: '#6c757d', high: '#ffc107', fixed_date: '#6f42c1' };
const priorityBgClasses = { low: 'bg-secondary', high: 'bg-warning', fixed_date: 'bg-purple' };

function openEventModal(title, props, startDate, endDate) {
    const header = document.getElementById('eventModalHeader');
    const body   = document.getElementById('eventModalBody');
    const footer = document.getElementById('eventModalFooter');

    const statusColor = statusColors[props.status] || '#6c757d';
    header.style.background = statusColor;
    header.style.color = (props.status === 'in_progress') ? '#000' : '#fff';
    document.querySelector('#eventModalHeader .btn-close').className =
        (props.status === 'in_progress') ? 'btn-close' : 'btn-close btn-close-white';
    document.getElementById('eventModalLabel').textContent = title;

    const dateStr = startDate.toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    const timeStr = startDate.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
    const endTimeStr = endDate ? endDate.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' }) : null;

    body.innerHTML = `
        <div class="row g-3">
            <div class="col-12">
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge" style="background-color:${statusColor}; color:${props.status==='in_progress'?'#000':'#fff'}">
                        <i class="bi bi-circle-fill me-1" style="font-size:8px"></i>${statusLabels[props.status] || props.status}
                    </span>
                    <span class="badge ${priorityBgClasses[props.priority] || 'bg-secondary'}">
                        <i class="bi bi-flag-fill me-1"></i>Priorità: ${priorityLabels[props.priority] || props.priority}
                    </span>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 rounded bg-light h-100">
                    <div class="text-muted small mb-1"><i class="bi bi-calendar3 me-1"></i>Data</div>
                    <div class="fw-semibold">${dateStr}</div>
                    ${endTimeStr
                        ? `<div class="text-muted small mt-1"><i class="bi bi-clock me-1"></i>${timeStr} → ${endTimeStr}</div>`
                        : (timeStr !== '00:00' ? `<div class="text-muted small mt-1"><i class="bi bi-clock me-1"></i>${timeStr}</div>` : '')
                    }
                    ${props.duration ? `<div class="text-muted small mt-1"><i class="bi bi-hourglass-split me-1"></i>Durata: ${props.duration} min</div>` : ''}
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 rounded bg-light h-100">
                    <div class="text-muted small mb-1"><i class="bi bi-gear me-1"></i>Destinazione</div>
                    <div class="fw-semibold">${props.equipment}</div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 rounded bg-light h-100">
                    <div class="text-muted small mb-1"><i class="bi bi-person me-1"></i>Assegnato a</div>
                    <div class="fw-semibold">${props.operator}</div>
                </div>
            </div>

            ${props.description ? `
            <div class="col-12">
                <div class="p-3 rounded bg-light">
                    <div class="text-muted small mb-1"><i class="bi bi-text-paragraph me-1"></i>Descrizione</div>
                    <div>${props.description}</div>
                </div>
            </div>` : ''}

            ${props.notes ? `
            <div class="col-12">
                <div class="p-3 rounded bg-light">
                    <div class="text-muted small mb-1"><i class="bi bi-sticky me-1"></i>Note</div>
                    <div>${props.notes}</div>
                </div>
            </div>` : ''}
        </div>
    `;

    footer.innerHTML = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
        ${userRole !== 'manutentore' ? `
            <a href="${props.showUrl}" class="btn btn-light">
                <i class="bi bi-eye me-2"></i>Dettagli
            </a>` : ''}
        <a href="${props.reportUrl}" class="btn btn-primary">
            <i class="bi bi-file-earmark-plus me-2"></i>Crea Rapportino
        </a>
    `;

    new bootstrap.Modal(document.getElementById('eventModal')).show();
}

document.addEventListener('DOMContentLoaded', function() {
    var isMobile = window.innerWidth < 768;
    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'it',
        initialView: isMobile ? 'listWeek' : 'dayGridMonth',
        headerToolbar: isMobile ? {
            left: 'prev,next',
            center: 'title',
            right: 'listWeek,listMonth'
        } : {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        buttonText: { today: 'Oggi', month: 'Mese', week: 'Settimana', day: 'Giorno', list: 'Lista' },
        height: 'auto',
        events: '{{ route('interventions.calendar.data') }}',
        noEventsText: 'Nessun intervento in questo periodo',
        eventClick: function(info) {
            info.jsEvent.preventDefault();
            openEventModal(info.event.title, info.event.extendedProps, info.event.start, info.event.end);
        },
        eventDidMount: function(info) {
            info.el.title = info.event.title + ' — ' + info.event.extendedProps.equipment;
        }
    });

    calendar.render();
});
</script>
@endpush
