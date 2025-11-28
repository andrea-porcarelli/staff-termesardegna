@extends('layouts.app')

@section('title', 'Nuovo Intervento - Rapportini')

@section('page-title', 'Nuovo Intervento')

@section('content')
<div class="card">
    <div class="card-header">
        <h4><i class="bi bi-plus-circle me-2"></i>Pianifica Nuovo Intervento</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('interventions.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="equipment_id" class="form-label">Apparato <span class="text-danger">*</span></label>
                    <select class="form-select @error('equipment_id') is-invalid @enderror"
                            id="equipment_id" name="equipment_id" required>
                        <option value="">Seleziona un apparato...</option>
                        @foreach($equipments as $equipment)
                            <option value="{{ $equipment->id }}" {{ old('equipment_id') == $equipment->id ? 'selected' : '' }}>
                                {{ $equipment->name }} - {{ $equipment->code }}
                            </option>
                        @endforeach
                    </select>
                    @error('equipment_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="assigned_user_id" class="form-label">Operatore Assegnato <span class="text-danger">*</span></label>
                    <select class="form-select @error('assigned_user_id') is-invalid @enderror"
                            id="assigned_user_id" name="assigned_user_id" required>
                        <option value="">Seleziona un operatore...</option>
                        @foreach($operators as $operator)
                            <option value="{{ $operator->id }}" {{ old('assigned_user_id') == $operator->id ? 'selected' : '' }}>
                                {{ $operator->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_user_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="title" class="form-label">Titolo Intervento <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('title') is-invalid @enderror"
                       id="title" name="title" value="{{ old('title') }}" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Descrizione</label>
                <textarea class="form-control @error('description') is-invalid @enderror"
                          id="description" name="description" rows="3">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="scheduled_date" class="form-label">Data Pianificata <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('scheduled_date') is-invalid @enderror"
                           id="scheduled_date" name="scheduled_date" value="{{ old('scheduled_date') }}" required>
                    @error('scheduled_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="scheduled_start_time" class="form-label">Ora Inizio</label>
                    <input type="time" class="form-control @error('scheduled_start_time') is-invalid @enderror"
                           id="scheduled_start_time" name="scheduled_start_time" value="{{ old('scheduled_start_time') }}">
                    @error('scheduled_start_time')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="estimated_duration_minutes" class="form-label">Durata Stimata (minuti)</label>
                    <input type="number" class="form-control @error('estimated_duration_minutes') is-invalid @enderror"
                           id="estimated_duration_minutes" name="estimated_duration_minutes"
                           value="{{ old('estimated_duration_minutes') }}" min="1">
                    @error('estimated_duration_minutes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="status" class="form-label">Stato</label>
                    <select class="form-select @error('status') is-invalid @enderror"
                            id="status" name="status">
                        <option value="planned" {{ old('status', 'planned') == 'planned' ? 'selected' : '' }}>Pianificato</option>
                        <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>In corso</option>
                        <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completato</option>
                        <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Annullato</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="priority" class="form-label">Priorità</label>
                    <select class="form-select @error('priority') is-invalid @enderror"
                            id="priority" name="priority">
                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Bassa</option>
                        <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Media</option>
                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>Alta</option>
                        <option value="critical" {{ old('priority') == 'critical' ? 'selected' : '' }}>Critica</option>
                    </select>
                    @error('priority')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">Note</label>
                <textarea class="form-control @error('notes') is-invalid @enderror"
                          id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-light">
                    <i class="bi bi-check-circle me-2"></i>Salva Intervento
                </button>
                <a href="{{ route('interventions.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-2"></i>Annulla
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
