@extends('layouts.app')

@section('title', 'Modifica Area - Rapportini')

@section('page-title', 'Modifica Area')

@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('areas.update', $area) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">
                    <i class="bi bi-tag me-1"></i>Nome Area *
                </label>
                <input type="text"
                       class="form-control @error('name') is-invalid @enderror"
                       id="name"
                       name="name"
                       value="{{ old('name', $area->name) }}"
                       required
                       autofocus>
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
                          rows="4">{{ old('description', $area->description) }}</textarea>
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
                           {{ old('active', $area->active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="active">
                        <i class="bi bi-check-circle me-1"></i>Area Attiva
                    </label>
                </div>
                <small class="text-muted">
                    Le aree disattivate non saranno disponibili per la creazione di nuovi rapportini
                </small>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Salva Modifiche
                </button>
                <a href="{{ route('areas.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-2"></i>Annulla
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
