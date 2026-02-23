@extends('layouts.app')

@section('title', 'Gestione Zone - Rapportini')

@section('page-title', 'Gestione Zone')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4><i class="bi bi-diagram-3 me-2"></i>Lista Zone</h4>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Cerca..." value="{{ $search ?? '' }}" style="width:200px">
                <button type="submit" class="btn btn-light btn-sm"><i class="bi bi-search"></i></button>
                @if($search ?? '')
                    <a href="{{ route('departments.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x"></i>
                    </a>
                @endif
            </form>
            <a href="{{ route('departments.create') }}" class="btn btn-light">
                <i class="bi bi-plus-circle me-2"></i>Nuova Zona
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>
                            @php $nextDir = ($sort==='id' && $dir==='asc') ? 'desc' : 'asc'; @endphp
                            <a href="{{ route('departments.index', ['search'=>$search,'sort'=>'id','direction'=>$nextDir]) }}" class="text-decoration-none text-dark">
                                ID {!! ($sort==='id') ? ($dir==='asc' ? '▲' : '▼') : '⇅' !!}
                            </a>
                        </th>
                        <th>
                            @php $nextDir = ($sort==='name' && $dir==='asc') ? 'desc' : 'asc'; @endphp
                            <a href="{{ route('departments.index', ['search'=>$search,'sort'=>'name','direction'=>$nextDir]) }}" class="text-decoration-none text-dark">
                                Nome {!! ($sort==='name') ? ($dir==='asc' ? '▲' : '▼') : '⇅' !!}
                            </a>
                        </th>
                        <th>Area</th>
                        <th>Descrizione</th>
                        <th>N° Impianti</th>
                        <th>Stato</th>
                        <th>
                            @php $nextDir = ($sort==='created_at' && $dir==='asc') ? 'desc' : 'asc'; @endphp
                            <a href="{{ route('departments.index', ['search'=>$search,'sort'=>'created_at','direction'=>$nextDir]) }}" class="text-decoration-none text-dark">
                                Data Creazione {!! ($sort==='created_at') ? ($dir==='asc' ? '▲' : '▼') : '⇅' !!}
                            </a>
                        </th>
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
                                    {{ $department->equipments_count }} impianti
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
                                      onsubmit="return confirm('Sei sicuro di voler eliminare questa zona? Verranno eliminati anche tutti gli impianti associati!');">
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
                                <p class="text-muted mt-2">Nessuna zona trovata</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
