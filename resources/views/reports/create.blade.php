@extends('layouts.app')

@section('title', 'Crea Rapportino - Rapportini')

@section('page-title', 'Crea Rapportino')

@section('content')
<div class="mb-3">
    <a href="{{ route('interventions.calendar') }}" class="text-decoration-none">
        <i class="bi bi-arrow-left me-2"></i>Torna al Calendario
    </a>
</div>

{{-- Scheda dettagli intervento --}}
@php
    $statusColors  = ['planned' => 'bg-info', 'in_progress' => 'bg-warning', 'completed' => 'bg-success', 'cancelled' => 'bg-danger'];
    $statusLabels  = ['planned' => 'Pianificato', 'in_progress' => 'In corso', 'completed' => 'Completato', 'cancelled' => 'Annullato'];
    $priorityColors = ['low' => 'bg-secondary', 'medium' => 'bg-info', 'high' => 'bg-warning', 'critical' => 'bg-danger'];
    $priorityLabels = ['low' => 'Bassa', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Critica'];
@endphp

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i>{{ $intervention->title }}</h5>
        <div class="d-flex gap-2">
            <span class="badge {{ $statusColors[$intervention->status] ?? 'bg-secondary' }}">
                {{ $statusLabels[$intervention->status] ?? $intervention->status }}
            </span>
            <span class="badge {{ $priorityColors[$intervention->priority] ?? 'bg-secondary' }}">
                <i class="bi bi-flag-fill me-1"></i>{{ $priorityLabels[$intervention->priority] ?? $intervention->priority }}
            </span>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">

            {{-- Destinazione --}}
            <div class="col-md-4">
                <div class="p-3 rounded bg-light h-100">
                    @if($intervention->equipment)
                        <div class="text-muted small mb-1"><i class="bi bi-gear me-1"></i>Impianto/Macchina</div>
                        <div class="fw-semibold">{{ $intervention->equipment->name }}</div>
                        <div class="text-muted small">Codice: {{ $intervention->equipment->code }}</div>
                        @if($intervention->equipment->department)
                            <div class="text-muted small mt-1">
                                <i class="bi bi-geo-alt me-1"></i>
                                {{ $intervention->equipment->department->area->name ?? '' }} /
                                {{ $intervention->equipment->department->name }}
                            </div>
                        @endif
                    @else
                        <div class="text-muted small mb-1"><i class="bi bi-building me-1"></i>Area / Zona</div>
                        <div class="fw-semibold">{{ $intervention->area?->name ?? '-' }}</div>
                        <div class="text-muted small">{{ $intervention->department?->name ?? '-' }}</div>
                    @endif
                </div>
            </div>

            {{-- Data e ora --}}
            <div class="col-md-4">
                <div class="p-3 rounded bg-light h-100">
                    <div class="text-muted small mb-1"><i class="bi bi-calendar3 me-1"></i>Pianificazione</div>
                    <div class="fw-semibold">{{ $intervention->scheduled_date->isoFormat('dddd D MMMM YYYY') }}</div>
                    @if($intervention->scheduled_start_time)
                        <div class="text-muted small mt-1">
                            <i class="bi bi-clock me-1"></i>Inizio: {{ substr($intervention->scheduled_start_time, 0, 5) }}
                        </div>
                    @endif
                    @if($intervention->estimated_duration_minutes)
                        <div class="text-muted small mt-1">
                            <i class="bi bi-hourglass-split me-1"></i>
                            Durata stimata: {{ $intervention->estimated_duration_minutes }} min
                            @if($intervention->estimated_duration_minutes >= 60)
                                ({{ floor($intervention->estimated_duration_minutes / 60) }}h {{ $intervention->estimated_duration_minutes % 60 }}m)
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Assegnato a --}}
            <div class="col-md-4">
                <div class="p-3 rounded bg-light h-100">
                    <div class="text-muted small mb-1"><i class="bi bi-person me-1"></i>Assegnato a</div>
                    <div class="fw-semibold">{{ $intervention->assignedUser->name }}</div>
                    <div class="text-muted small">{{ $intervention->assignedUser->email }}</div>
                </div>
            </div>

            {{-- Descrizione --}}
            @if($intervention->description)
                <div class="col-12">
                    <div class="p-3 rounded bg-light">
                        <div class="text-muted small mb-1"><i class="bi bi-text-paragraph me-1"></i>Descrizione intervento</div>
                        <div>{{ $intervention->description }}</div>
                    </div>
                </div>
            @endif

            {{-- Note --}}
            @if($intervention->notes)
                <div class="col-12">
                    <div class="p-3 rounded" style="background:#fff3cd;">
                        <div class="text-muted small mb-1"><i class="bi bi-sticky me-1"></i>Note</div>
                        <div>{{ $intervention->notes }}</div>
                    </div>
                </div>
            @endif

        </div>

        {{-- Componenti impianto --}}
        @if($intervention->equipment && $intervention->equipment->components->count() > 0)
            <hr class="my-3">
            <div>
                <div class="text-muted small mb-2"><i class="bi bi-list-check me-1"></i><strong>Componenti dell'impianto</strong></div>
                <div class="row g-2">
                    @foreach($intervention->equipment->components as $component)
                        <div class="col-md-6">
                            <div class="p-2 rounded border d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold small">{{ $component->name }}</div>
                                    @if($component->description)
                                        <div class="text-muted" style="font-size:12px">{{ $component->description }}</div>
                                    @endif
                                </div>
                                @if($component->next_maintenance_date)
                                    <span class="badge {{ $component->next_maintenance_date->isPast() ? 'bg-danger' : 'bg-light text-dark' }} ms-2 text-nowrap" style="font-size:11px">
                                        <i class="bi bi-calendar-event me-1"></i>{{ $component->next_maintenance_date->format('d/m/Y') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Form rapportino --}}
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-file-text me-2"></i>Compila Rapportino</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('interventions.reports.store', $intervention) }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="report_date" class="form-label">Data <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('report_date') is-invalid @enderror"
                           id="report_date" name="report_date" value="{{ old('report_date', now()->format('Y-m-d')) }}" required>
                    @error('report_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="start_time" class="form-label">Ora Inizio</label>
                    <input type="time" class="form-control @error('start_time') is-invalid @enderror"
                           id="start_time" name="start_time" value="{{ old('start_time', $intervention->scheduled_start_time ? substr($intervention->scheduled_start_time, 0, 5) : '') }}">
                    @error('start_time')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="end_time" class="form-label">Ora Fine</label>
                    <input type="time" class="form-control @error('end_time') is-invalid @enderror"
                           id="end_time" name="end_time" value="{{ old('end_time') }}">
                    @error('end_time')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="activities" class="form-label">Attività Svolte</label>
                <textarea class="form-control @error('activities') is-invalid @enderror"
                          id="activities" name="activities" rows="4" placeholder="Descrivi le attività svolte...">{{ old('activities') }}</textarea>
                @error('activities')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">Note</label>
                <textarea class="form-control @error('notes') is-invalid @enderror"
                          id="notes" name="notes" rows="3" placeholder="Note aggiuntive...">{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Stato</label>
                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                    <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Bozza</option>
                    <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completato</option>
                    @if(in_array(auth()->user()->role, ['admin', 'operator']))
                        <option value="chiuso" {{ old('status') == 'chiuso' ? 'selected' : '' }}>Chiuso</option>
                    @endif
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                @livewire('media-manager', ['mediableType' => 'TempMedia', 'mediableId' => $tempMediaId])
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle me-2"></i>Salva Rapportino
                </button>
                <a href="{{ route('interventions.calendar') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-2"></i>Annulla
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
