@extends('layouts.app')

@section('title', 'Gestione Aree - Rapportini')

@section('page-title', 'Gestione Aree')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4><i class="bi bi-building me-2"></i>Lista Aree</h4>
        <a href="{{ route('areas.create') }}" class="btn btn-light">
            <i class="bi bi-plus-circle me-2"></i>Nuova Area
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Descrizione</th>
                        <th>N° Reparti</th>
                        <th>Stato</th>
                        <th>Data Creazione</th>
                        <th class="text-center">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($areas as $area)
                        <tr>
                            <td>{{ $area->id }}</td>
                            <td><strong>{{ $area->name }}</strong></td>
                            <td>{{ Str::limit($area->description ?? 'N/A', 50) }}</td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ $area->departments_count }} reparti
                                </span>
                            </td>
                            <td>
                                @if($area->active)
                                    <span class="badge badge-success">Attivo</span>
                                @else
                                    <span class="badge badge-danger">Disattivo</span>
                                @endif
                            </td>
                            <td>{{ $area->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-center">
                                <a href="{{ route('areas.show', $area) }}" class="btn btn-info btn-sm" title="Visualizza">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('areas.edit', $area) }}" class="btn btn-warning btn-sm" title="Modifica">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('areas.destroy', $area) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Sei sicuro di voler eliminare quest\'area? Verranno eliminati anche tutti i reparti e gli apparati associati!');">
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
                                <p class="text-muted mt-2">Nessuna area trovata</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
