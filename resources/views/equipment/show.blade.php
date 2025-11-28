@extends('layouts.app')

@section('title', 'Dettagli Apparato - Rapportini')

@section('page-title', 'Dettagli Apparato')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4><i class="bi bi-gear me-2"></i>{{ $equipment->name }}</h4>
        <div>
            <a href="{{ route('equipments.edit', $equipment) }}" class="btn btn-warning btn-sm">
                <i class="bi bi-pencil me-2"></i>Modifica
            </a>
            <a href="{{ route('equipments.index') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left me-2"></i>Torna alla Lista
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5 class="mb-3"><i class="bi bi-info-circle me-2"></i>Informazioni Generali</h5>
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <th style="width: 40%;">Codice:</th>
                            <td><strong>{{ $equipment->code }}</strong></td>
                        </tr>
                        <tr>
                            <th>Nome:</th>
                            <td>{{ $equipment->name }}</td>
                        </tr>
                        <tr>
                            <th>Descrizione:</th>
                            <td>{{ $equipment->description ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Area:</th>
                            <td>{{ $equipment->department->area->name }}</td>
                        </tr>
                        <tr>
                            <th>Reparto:</th>
                            <td><span class="badge bg-secondary">{{ $equipment->department->name }}</span></td>
                        </tr>
                        <tr>
                            <th>Stato:</th>
                            <td>
                                @if($equipment->active)
                                    <span class="badge badge-success">Attivo</span>
                                @else
                                    <span class="badge badge-danger">Disattivo</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="col-md-6">
                <h5 class="mb-3"><i class="bi bi-tools me-2"></i>Specifiche Tecniche</h5>
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <th style="width: 40%;">Produttore:</th>
                            <td>{{ $equipment->manufacturer ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Modello:</th>
                            <td>{{ $equipment->model ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Numero di Serie:</th>
                            <td>{{ $equipment->serial_number ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Data Installazione:</th>
                            <td>
                                @if($equipment->installation_date)
                                    {{ $equipment->installation_date->format('d/m/Y') }}
                                    <small class="text-muted">({{ $equipment->installation_date->diffForHumans() }})</small>
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <hr class="my-4">

        <div class="row">
            <div class="col-12">
                <h5 class="mb-3"><i class="bi bi-calendar-check me-2"></i>Informazioni Manutenzione</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <small class="text-muted d-block mb-2">Frequenza Manutenzione</small>
                                <h4 class="mb-0">{{ $equipment->maintenance_frequency_days }}</h4>
                                <small>giorni</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <small class="text-muted d-block mb-2">Ultima Manutenzione</small>
                                @if($equipment->last_maintenance_date)
                                    <h5 class="mb-0">{{ $equipment->last_maintenance_date->format('d/m/Y') }}</h5>
                                    <small class="text-muted">{{ $equipment->last_maintenance_date->diffForHumans() }}</small>
                                @else
                                    <h5 class="mb-0 text-muted">Non Registrata</h5>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <small class="text-muted d-block mb-2">Prossima Manutenzione</small>
                                @if($equipment->next_maintenance_date)
                                    @php
                                        $days = now()->diffInDays($equipment->next_maintenance_date, false);
                                        $isOverdue = $days < 0;
                                        $isNear = $days >= 0 && $days <= 7;
                                    @endphp
                                    <h5 class="mb-0">{{ $equipment->next_maintenance_date->format('d/m/Y') }}</h5>
                                    <span class="badge {{ $isOverdue ? 'bg-danger' : ($isNear ? 'bg-warning' : 'bg-success') }}">
                                        @if($isOverdue)
                                            Scaduta da {{ abs($days) }} giorni
                                        @elseif($isNear)
                                            Tra {{ $days }} giorni
                                        @else
                                            Tra {{ $days }} giorni
                                        @endif
                                    </span>
                                @else
                                    <h5 class="mb-0 text-muted">Non Pianificata</h5>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <div class="row">
            <div class="col-12">
                <h5 class="mb-3"><i class="bi bi-paperclip me-2"></i>Documenti Allegati</h5>
                @livewire('media-manager', ['mediableType' => 'App\\Models\\Equipment', 'mediableId' => $equipment->id])
            </div>
        </div>

        <hr class="my-4">

        <div class="row">
            <div class="col-md-6">
                <small class="text-muted">
                    <i class="bi bi-clock-history me-1"></i>
                    Creato il {{ $equipment->created_at->format('d/m/Y H:i') }}
                </small>
            </div>
            <div class="col-md-6 text-end">
                <small class="text-muted">
                    <i class="bi bi-pencil-square me-1"></i>
                    Ultima modifica: {{ $equipment->updated_at->format('d/m/Y H:i') }}
                </small>
            </div>
        </div>
    </div>
</div>
@endsection
