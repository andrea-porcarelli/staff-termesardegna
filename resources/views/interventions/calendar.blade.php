@extends('layouts.app')

@section('title', 'Calendario Interventi - Rapportini')

@section('page-title', 'Calendario Interventi')

@section('content')

{{-- VISTA MOBILE: Lista per settimane --}}
<div class="mobile-list-view">
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
                                                            'low' => 'circle',
                                                            'medium' => 'circle-fill',
                                                            'high' => 'exclamation-circle',
                                                            'critical' => 'exclamation-triangle-fill'
                                                        ];
                                                        $priorityColors = [
                                                            'low' => 'text-secondary',
                                                            'medium' => 'text-info',
                                                            'high' => 'text-warning',
                                                            'critical' => 'text-danger'
                                                        ];
                                                    @endphp
                                                    <i class="bi bi-{{ $priorityIcons[$intervention->priority] ?? 'circle' }} {{ $priorityColors[$intervention->priority] ?? 'text-secondary' }} me-2"></i>
                                                    {{ $intervention->title }}
                                                </h6>
                                                <small class="text-muted d-block">
                                                    <i class="bi bi-gear me-1"></i>{{ $intervention->equipment->name }}
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
                                                    'planned' => 'bg-info',
                                                    'in_progress' => 'bg-warning text-dark',
                                                    'completed' => 'bg-success',
                                                    'cancelled' => 'bg-secondary'
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
                @if(auth()->user()->role === 'admin' || auth()->user()->role === 'supervisor')
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
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalLabel">Dettagli Intervento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="eventModalBody">
                    <!-- Contenuto dinamico -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                    <a href="#" id="viewInterventionBtn" class="btn btn-light">
                        <i class="bi bi-eye me-2"></i>Visualizza Dettaglio
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<style>
    /* Vista mobile: mostra solo su schermi piccoli */
    .mobile-list-view {
        display: block;
    }

    .desktop-calendar-view {
        display: none;
    }

    /* Vista desktop: mostra su schermi grandi */
    @media (min-width: 768px) {
        .mobile-list-view {
            display: none;
        }

        .desktop-calendar-view {
            display: block;
        }
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
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza FullCalendar solo su desktop
    if (window.innerWidth >= 768) {
        var calendarEl = document.getElementById('calendar');
        var eventModal = new bootstrap.Modal(document.getElementById('eventModal'));

        var calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'it',
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            buttonText: {
                today: 'Oggi',
                month: 'Mese',
                week: 'Settimana',
                day: 'Giorno',
                list: 'Lista'
            },
            height: 'auto',
            events: '{{ route('interventions.calendar.data') }}',
            eventClick: function(info) {
                info.jsEvent.preventDefault();

                var props = info.event.extendedProps;

                // Traduzioni
                var statusLabels = {
                    'planned': 'Pianificato',
                    'in_progress': 'In corso',
                    'completed': 'Completato',
                    'cancelled': 'Annullato'
                };

                var priorityLabels = {
                    'low': 'Bassa',
                    'medium': 'Media',
                    'high': 'Alta',
                    'critical': 'Critica'
                };

                var statusColors = {
                    'planned': '#0dcaf0',
                    'in_progress': '#ffc107',
                    'completed': '#198754',
                    'cancelled': '#6c757d'
                };

                var priorityColors = {
                    'low': '#6c757d',
                    'medium': '#0dcaf0',
                    'high': '#ffc107',
                    'critical': '#dc3545'
                };

                var modalBody = `
                    <div class="mb-3">
                        <strong>Titolo:</strong><br>
                        ${info.event.title}
                    </div>
                    <div class="mb-3">
                        <strong>Apparato:</strong><br>
                        ${props.equipment}
                    </div>
                    <div class="mb-3">
                        <strong>Operatore:</strong><br>
                        <i class="bi bi-person me-1"></i>${props.operator}
                    </div>
                    <div class="mb-3">
                        <strong>Data e Ora:</strong><br>
                        ${info.event.start.toLocaleString('it-IT', {
                            dateStyle: 'full',
                            timeStyle: 'short'
                        })}
                        ${info.event.end ? ' - ' + info.event.end.toLocaleTimeString('it-IT', { timeStyle: 'short' }) : ''}
                    </div>
                    ${props.description ? `
                    <div class="mb-3">
                        <strong>Descrizione:</strong><br>
                        ${props.description}
                    </div>
                    ` : ''}
                    <div class="mb-3">
                        <strong>Stato:</strong><br>
                        <span class="badge" style="background-color: ${statusColors[props.status]}">
                            ${statusLabels[props.status]}
                        </span>
                    </div>
                    <div class="mb-3">
                        <strong>Priorità:</strong><br>
                        <span class="badge" style="background-color: ${priorityColors[props.priority]}">
                            ${priorityLabels[props.priority]}
                        </span>
                    </div>
                `;

                document.getElementById('eventModalBody').innerHTML = modalBody;
                document.getElementById('viewInterventionBtn').href = props.url;

                eventModal.show();
            },
            eventDidMount: function(info) {
                // Aggiungi tooltip
                info.el.title = info.event.title + ' - ' + info.event.extendedProps.equipment;
            }
        });

        calendar.render();
    }
});
</script>
@endpush
