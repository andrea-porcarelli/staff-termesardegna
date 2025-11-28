@extends('layouts.app')

@section('title', 'Crea Rapportino - Rapportini')

@section('page-title', 'Crea Rapportino')

@section('content')
<div class="mb-3">
    <a href="{{ route('interventions.calendar') }}" class="text-decoration-none">
        <i class="bi bi-arrow-left me-2"></i>Torna al Calendario
    </a>
</div>

{{-- Info intervento --}}
<div class="alert alert-primary mb-4">
    <h6 class="mb-2"><strong>{{ $intervention->title }}</strong></h6>
    <small>
        <i class="bi bi-gear me-1"></i>{{ $intervention->equipment->name }} ({{ $intervention->equipment->code }})<br>
        <i class="bi bi-calendar3 me-1"></i>{{ $intervention->scheduled_date->format('d/m/Y') }}
        @if($intervention->scheduled_start_time)
            alle {{ substr($intervention->scheduled_start_time, 0, 5) }}
        @endif
    </small>
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
                           id="start_time" name="start_time" value="{{ old('start_time') }}">
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
                <a href="{{ route('interventions.show', $intervention) }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-2"></i>Annulla
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
