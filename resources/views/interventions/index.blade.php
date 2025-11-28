@extends('layouts.app')

@section('title', 'Gestione Interventi - Rapportini')

@section('page-title', 'Gestione Interventi')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4><i class="bi bi-calendar-check me-2"></i>Lista Interventi</h4>
        <a href="{{ route('interventions.create') }}" class="btn btn-light">
            <i class="bi bi-plus-circle me-2"></i>Nuovo Intervento
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Titolo</th>
                        <th>Apparato</th>
                        <th>Operatore</th>
                        <th>Data Pianificata</th>
                        <th>Stato</th>
                        <th>Priorità</th>
                        <th class="text-center">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($interventions as $intervention)
                        <tr>
                            <td><strong>{{ $intervention->title }}</strong></td>
                            <td>
                                <small class="text-muted">{{ $intervention->equipment->department->area->name }} / {{ $intervention->equipment->department->name }}</small><br>
                                <span class="badge bg-secondary">{{ $intervention->equipment->name }}</span>
                            </td>
                            <td>
                                <i class="bi bi-person me-1"></i>{{ $intervention->assignedUser->name }}
                            </td>
                            <td>
                                {{ $intervention->scheduled_date->format('d/m/Y') }}
                                @if($intervention->scheduled_start_time)
                                    <br><small class="text-muted">{{ substr($intervention->scheduled_start_time, 0, 5) }}</small>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusClasses = [
                                        'planned' => 'bg-info',
                                        'in_progress' => 'bg-warning',
                                        'completed' => 'bg-success',
                                        'cancelled' => 'bg-danger'
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
                            </td>
                            <td>
                                @php
                                    $priorityClasses = [
                                        'low' => 'bg-secondary',
                                        'medium' => 'bg-info',
                                        'high' => 'bg-warning',
                                        'critical' => 'bg-danger'
                                    ];
                                    $priorityLabels = [
                                        'low' => 'Bassa',
                                        'medium' => 'Media',
                                        'high' => 'Alta',
                                        'critical' => 'Critica'
                                    ];
                                @endphp
                                <span class="badge {{ $priorityClasses[$intervention->priority] ?? 'bg-secondary' }}">
                                    {{ $priorityLabels[$intervention->priority] ?? $intervention->priority }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('interventions.show', $intervention) }}" class="btn btn-info btn-sm" title="Visualizza">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('interventions.edit', $intervention) }}" class="btn btn-warning btn-sm" title="Modifica">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('interventions.destroy', $intervention) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Sei sicuro di voler eliminare questo intervento?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Elimina">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                                <p class="text-muted mt-2">Nessun intervento trovato</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
