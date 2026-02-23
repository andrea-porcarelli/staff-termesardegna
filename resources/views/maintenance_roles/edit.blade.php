@extends('layouts.app')

@section('title', 'Modifica Specializzazione - Rapportini')

@section('page-title', 'Modifica Specializzazione')

@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('maintenance_roles.update', $maintenanceRole) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">
                    <i class="bi bi-award me-1"></i>Nome Specializzazione *
                </label>
                <input type="text"
                       class="form-control @error('name') is-invalid @enderror"
                       id="name" name="name"
                       value="{{ old('name', $maintenanceRole->name) }}" required autofocus>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">
                    <i class="bi bi-text-paragraph me-1"></i>Descrizione
                </label>
                <textarea class="form-control @error('description') is-invalid @enderror"
                          id="description" name="description" rows="4">{{ old('description', $maintenanceRole->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Salva Modifiche
                </button>
                <a href="{{ route('maintenance_roles.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-2"></i>Annulla
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
