@extends('layouts.app')

@section('title', 'Nuovo Reparto - Rapportini')

@section('page-title', 'Nuovo Reparto')

@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('departments.store') }}">
            @csrf

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
                        <option value="{{ $area->id }}" {{ old('area_id') == $area->id ? 'selected' : '' }}>
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
                    <i class="bi bi-tag me-1"></i>Nome Reparto *
                </label>
                <input type="text"
                       class="form-control @error('name') is-invalid @enderror"
                       id="name"
                       name="name"
                       value="{{ old('name') }}"
                       placeholder="Es: Reparto Produzione A"
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
                          rows="4"
                          placeholder="Inserisci una descrizione del reparto (opzionale)">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Crea Reparto
                </button>
                <a href="{{ route('departments.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-2"></i>Annulla
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
