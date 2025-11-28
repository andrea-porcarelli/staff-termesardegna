@extends('layouts.app')

@section('title', 'Gestione Reparti - Rapportini')

@section('page-title', 'Gestione Reparti')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4><i class="bi bi-diagram-3 me-2"></i>Lista Reparti</h4>
        <a href="{{ route('departments.create') }}" class="btn btn-light">
            <i class="bi bi-plus-circle me-2"></i>Nuovo Reparto
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Area</th>
                        <th>Descrizione</th>
                        <th>N° Apparati</th>
                        <th>Stato</th>
                        <th>Data Creazione</th>
                        <th class="text-center">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                        <tr>
                            <td>{{ $department->id }}</td>
                            <td><strong>{{ $department->name }}</strong></td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ $department->area->name }}
                                </span>
                            </td>
                            <td>{{ Str::limit($department->description ?? 'N/A', 50) }}</td>
                            <td>
                                <span class="badge bg-info">
                                    {{ $department->equipments_count }} apparati
                                </span>
                            </td>
                            <td>
                                @if($department->active)
                                    <span class="badge badge-success">Attivo</span>
                                @else
                                    <span class="badge badge-danger">Disattivo</span>
                                @endif
                            </td>
                            <td>{{ $department->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-center">
                                <a href="{{ route('departments.show', $department) }}" class="btn btn-info btn-sm" title="Visualizza">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('departments.edit', $department) }}" class="btn btn-warning btn-sm" title="Modifica">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('departments.destroy', $department) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Sei sicuro di voler eliminare questo reparto? Verranno eliminati anche tutti gli apparati associati!');">
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
                            <td colspan="8" class="text-center py-4">
                                <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                                <p class="text-muted mt-2">Nessun reparto trovato</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
