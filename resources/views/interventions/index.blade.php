@extends('layouts.app')

@section('title', 'Gestione Tickets - Rapportini')

@section('page-title', 'Gestione Tickets')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4><i class="bi bi-calendar-check me-2"></i>Lista Tickets</h4>
        <a href="{{ route('interventions.create') }}" class="btn btn-light">
            <i class="bi bi-plus-circle me-2"></i>Nuovo Ticket
        </a>
    </div>

    {{-- Filtri --}}
    @php
        $isAdmin = auth()->user()->role === 'admin';
        $filterKeys = $isAdmin ? ['tipo', 'title', 'id', 'operator_id', 'manutentore_id'] : ['tipo'];
        $hasActiveFilters = request()->hasAny($filterKeys);
    @endphp
    <div class="card-body border-bottom pb-3">
        <form method="GET" action="{{ route('interventions.index') }}" class="row g-2 align-items-end">
            <div class="col-auto">
                <label for="filter_tipo" class="form-label form-label-sm mb-1">Tipo</label>
                <select id="filter_tipo" name="tipo" class="form-select form-select-sm" style="min-width: 180px;">
                    <option value="">Tutti i tipi</option>
                    <option value="pianificazione" {{ request('tipo') === 'pianificazione' ? 'selected' : '' }}>Pianificazione</option>
                    <option value="ordinario" {{ request('tipo') === 'ordinario' ? 'selected' : '' }}>Ticket Ordinario</option>
                </select>
            </div>

            @if($isAdmin)
                <div class="col-auto">
                    <label for="filter_title" class="form-label form-label-sm mb-1">Titolo</label>
                    <input type="text" id="filter_title" name="title" value="{{ request('title') }}"
                           class="form-control form-control-sm" placeholder="Cerca per titolo" style="min-width: 200px;">
                </div>

                <div class="col-auto">
                    <label for="filter_id" class="form-label form-label-sm mb-1">ID</label>
                    <input type="number" min="1" id="filter_id" name="id" value="{{ request('id') }}"
                           class="form-control form-control-sm" placeholder="#" style="width: 110px;">
                </div>

                <div class="col-auto">
                    <label for="filter_operator" class="form-label form-label-sm mb-1">Operatore</label>
                    <select id="filter_operator" name="operator_id" class="form-select form-select-sm" style="min-width: 180px;">
                        <option value="">Tutti</option>
                        @foreach($operators as $op)
                            <option value="{{ $op->id }}" {{ (string) request('operator_id') === (string) $op->id ? 'selected' : '' }}>
                                {{ $op->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-auto">
                    <label for="filter_manutentore" class="form-label form-label-sm mb-1">Manutentore</label>
                    <select id="filter_manutentore" name="manutentore_id" class="form-select form-select-sm" style="min-width: 180px;">
                        <option value="">Tutti</option>
                        @foreach($manutentori as $m)
                            <option value="{{ $m->id }}" {{ (string) request('manutentore_id') === (string) $m->id ? 'selected' : '' }}>
                                {{ $m->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">
                    <i class="bi bi-funnel me-1"></i>Filtra
                </button>
                @if($hasActiveFilters)
                    <a href="{{ route('interventions.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x me-1"></i>Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Titolo</th>
                        <th>Tipo</th>
                        <th>Destinazione</th>
                        <th>Operatore</th>
                        <th>Aperto da</th>
                        <th>Data</th>
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
                                @if($intervention->tipo === 'pianificazione')
                                    <span class="badge bg-primary"><i class="bi bi-gear me-1"></i>Pianificazione</span>
                                @else
                                    <span class="badge bg-warning text-dark"><i class="bi bi-wrench me-1"></i>Ordinario</span>
                                @endif
                            </td>
                            <td>
                                @if($intervention->equipment)
                                    <small class="text-muted">{{ $intervention->equipment->department->area->name ?? '-' }} / {{ $intervention->equipment->department->name ?? '-' }}</small><br>
                                    <span class="badge bg-secondary">{{ $intervention->equipment->name }}</span>
                                @else
                                    <small class="text-muted">
                                        {{ $intervention->area->name ?? '-' }}@if($intervention->department) / {{ $intervention->department->name }}@else <em class="text-muted">(tutta l'area)</em>@endif
                                    </small>
                                @endif
                            </td>
                            <td>
                                @if($intervention->assignedUser)
                                    <i class="bi bi-person me-1"></i>{{ $intervention->assignedUser->name }}
                                @elseif($intervention->maintenanceRole)
                                    <i class="bi bi-person-badge me-1"></i><small class="text-muted">{{ $intervention->maintenanceRole->name }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($intervention->creator)
                                    <small><i class="bi bi-person-plus me-1"></i>{{ $intervention->creator->name }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($intervention->scheduled_date)
                                    {{ $intervention->scheduled_date->format('d/m/Y') }}
                                    @if($intervention->scheduled_start_time)
                                        <br><small class="text-muted">{{ substr($intervention->scheduled_start_time, 0, 5) }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusClasses = [
                                        'open' => 'bg-primary',
                                        'planned' => 'bg-info',
                                        'in_progress' => 'bg-warning',
                                        'completed' => 'bg-success',
                                        'cancelled' => 'bg-danger'
                                    ];
                                    $statusLabels = [
                                        'open' => 'Aperto',
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
                                        'low'        => 'bg-secondary',
                                        'medium'     => 'bg-info',
                                        'high'       => 'bg-warning',
                                        'fixed_date' => 'bg-purple',
                                    ];
                                    $priorityLabels = [
                                        'low'        => 'Bassa',
                                        'medium'     => 'Media',
                                        'high'       => 'Alta',
                                        'fixed_date' => 'Data fissa',
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
                                @if($intervention->canBeEditedBy(auth()->user()))
                                    <a href="{{ route('interventions.edit', $intervention) }}" class="btn btn-warning btn-sm" title="Modifica">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                                @if($intervention->canBeDeletedBy(auth()->user()))
                                    <form action="{{ route('interventions.destroy', $intervention) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Sei sicuro di voler eliminare questo ticket?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Elimina">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                                <p class="text-muted mt-2">Nessun ticket trovato</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
