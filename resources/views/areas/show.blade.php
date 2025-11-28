@extends('layouts.app')

@section('title', 'Dettagli Area - Rapportini')

@section('page-title', 'Dettagli Area')

@section('content')
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h4><i class="bi bi-building me-2"></i>Informazioni Area</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong><i class="bi bi-tag me-2"></i>Nome:</strong>
                    <p class="mb-0">{{ $area->name }}</p>
                </div>
                <div class="mb-3">
                    <strong><i class="bi bi-text-paragraph me-2"></i>Descrizione:</strong>
                    <p class="mb-0">{{ $area->description ?? 'N/A' }}</p>
                </div>
                <div class="mb-3">
                    <strong><i class="bi bi-check-circle me-2"></i>Stato:</strong>
                    <p class="mb-0">
                        @if($area->active)
                            <span class="badge badge-success">Attivo</span>
                        @else
                            <span class="badge badge-danger">Disattivo</span>
                        @endif
                    </p>
                </div>
                <div class="mb-3">
                    <strong><i class="bi bi-calendar me-2"></i>Data Creazione:</strong>
                    <p class="mb-0">{{ $area->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <strong><i class="bi bi-calendar-check me-2"></i>Ultima Modifica:</strong>
                    <p class="mb-0">{{ $area->updated_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('areas.edit', $area) }}" class="btn btn-warning">
                    <i class="bi bi-pencil me-2"></i>Modifica
                </a>
                <a href="{{ route('areas.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Torna alla Lista
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="bi bi-diagram-3 me-2"></i>Reparti ({{ $area->departments->count() }})</h4>
            </div>
            <div class="card-body">
                @if($area->departments->count() > 0)
                    <div class="list-group">
                        @foreach($area->departments as $department)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $department->name }}</h6>
                                        <small class="text-muted">
                                            {{ $department->equipment->count() }} apparati
                                        </small>
                                    </div>
                                    <div>
                                        @if($department->active)
                                            <span class="badge badge-success">Attivo</span>
                                        @else
                                            <span class="badge badge-danger">Disattivo</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                        <p class="text-muted mt-2">Nessun reparto associato a questa area</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
