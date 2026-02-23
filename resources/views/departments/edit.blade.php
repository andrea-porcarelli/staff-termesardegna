@extends('layouts.app')

@section('title', 'Modifica Zona - Rapportini')

@section('page-title', 'Modifica Zona')

@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('departments.update', $department) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="area_id" class="form-label">
                    <i class="bi bi-building me-1"></i>Area *
                </label>
                <select class="form-select @error('area_id') is-invalid @enderror"
                        id="area_id"
                        name="area_id"
                        required
                        autofocus>
                    <option value="">Seleziona un'area</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ old('area_id', $department->area_id) == $area->id ? 'selected' : '' }}>
                            {{ $area->name }}
                        </option>
                    @endforeach
                </select>
                @error('area_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">
                    <i class="bi bi-tag me-1"></i>Nome Zona *
                </label>
                <input type="text"
                       class="form-control @error('name') is-invalid @enderror"
                       id="name"
                       name="name"
                       value="{{ old('name', $department->name) }}"
                       required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">
                    <i class="bi bi-text-paragraph me-1"></i>Descrizione
                </label>
                <textarea class="form-control @error('description') is-invalid @enderror"
                          id="description"
                          name="description"
                          rows="4">{{ old('description', $department->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input"
                           type="checkbox"
                           id="active"
                           name="active"
                           {{ old('active', $department->active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="active">
                        <i class="bi bi-check-circle me-1"></i>Zona Attiva
                    </label>
                </div>
                <small class="text-muted">
                    Le zone disattivate non saranno disponibili per la creazione di nuovi rapportini
                </small>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Salva Modifiche
                </button>
                <a href="{{ route('departments.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-2"></i>Annulla
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
