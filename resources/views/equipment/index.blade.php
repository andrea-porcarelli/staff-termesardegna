@extends('layouts.app')

@section('title', 'Gestione Impianti/Macchine - Rapportini')

@section('page-title', 'Gestione Impianti/Macchine')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4><i class="bi bi-gear me-2"></i>Lista Impianti/Macchine</h4>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Cerca..." value="{{ $search ?? '' }}" style="width:200px">
                <button type="submit" class="btn btn-light btn-sm"><i class="bi bi-search"></i></button>
                @if($search ?? '')
                    <a href="{{ route('equipments.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x"></i>
                    </a>
                @endif
            </form>
            <a href="{{ route('equipments.create') }}" class="btn btn-light">
                <i class="bi bi-plus-circle me-2"></i>Nuovo Impianto/Macchina
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>
                            @php $nextDir = ($sort==='code' && $dir==='asc') ? 'desc' : 'asc'; @endphp
                            <a href="{{ route('equipments.index', ['search'=>$search,'sort'=>'code','direction'=>$nextDir]) }}" class="text-decoration-none text-dark">
                                Codice {!! ($sort==='code') ? ($dir==='asc' ? '▲' : '▼') : '⇅' !!}
                            </a>
                        </th>
                        <th>
                            @php $nextDir = ($sort==='name' && $dir==='asc') ? 'desc' : 'asc'; @endphp
                            <a href="{{ route('equipments.index', ['search'=>$search,'sort'=>'name','direction'=>$nextDir]) }}" class="text-decoration-none text-dark">
                                Nome {!! ($sort==='name') ? ($dir==='asc' ? '▲' : '▼') : '⇅' !!}
                            </a>
                        </th>
                        <th>Area / Zona</th>
                        <th>Produttore</th>
                        <th>Prossima Manutenzione</th>
                        <th>Stato</th>
                        <th class="text-center">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($equipment as $item)
                        <tr>
                            <td><strong>{{ $item->code }}</strong></td>
                            <td>{{ $item->name }}</td>
                            <td>
                                <small class="text-muted">{{ $item->department->area->name }}</small><br>
                                <span class="badge bg-secondary">{{ $item->department->name }}</span>
                            </td>
                            <td>{{ $item->manufacturer ?? 'N/A' }}</td>
                            <td>
                                @if($item->next_maintenance_date)
                                    @php
                                        $days = now()->diffInDays($item->next_maintenance_date, false);
                                        $isOverdue = $days < 0;
                                        $isNear = $days >= 0 && $days <= 7;
                                    @endphp
                                    <span class="badge {{ $isOverdue ? 'bg-danger' : ($isNear ? 'bg-warning' : 'bg-success') }}">
                                        {{ $item->next_maintenance_date->format('d/m/Y') }}
                                        @if($isOverdue)
                                            (Scaduta)
                                        @elseif($isNear)
                                            ({{ $days }} giorni)
                                        @endif
                                    </span>
                                @else
                                    <span class="badge bg-secondary">Non pianificata</span>
                                @endif
                            </td>
                            <td>
                                @if($item->active)
                                    <span class="badge badge-success">Attivo</span>
                                @else
                                    <span class="badge badge-danger">Disattivo</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('equipments.show', $item) }}" class="btn btn-info btn-sm" title="Visualizza">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('equipments.edit', $item) }}" class="btn btn-warning btn-sm" title="Modifica">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('equipments.destroy', $item) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Sei sicuro di voler eliminare questo impianto/macchina?');">
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
                                <p class="text-muted mt-2">Nessun impianto/macchina trovato</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
