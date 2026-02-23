@extends('layouts.app')

@section('title', 'Specializzazioni Manutentori - Rapportini')

@section('page-title', 'Specializzazioni Manutentori')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4><i class="bi bi-award me-2"></i>Lista Specializzazioni</h4>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Cerca..." value="{{ $search ?? '' }}" style="width:200px">
                <button type="submit" class="btn btn-light btn-sm"><i class="bi bi-search"></i></button>
                @if($search ?? '')
                    <a href="{{ route('maintenance_roles.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x"></i>
                    </a>
                @endif
            </form>
            <a href="{{ route('maintenance_roles.create') }}" class="btn btn-light">
                <i class="bi bi-plus-circle me-2"></i>Nuova Specializzazione
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>
                            @php $nextDir = ($sort==='name' && $dir==='asc') ? 'desc' : 'asc'; @endphp
                            <a href="{{ route('maintenance_roles.index', ['search'=>$search,'sort'=>'name','direction'=>$nextDir]) }}" class="text-decoration-none text-dark">
                                Nome {!! ($sort==='name') ? ($dir==='asc' ? '▲' : '▼') : '⇅' !!}
                            </a>
                        </th>
                        <th>Descrizione</th>
                        <th class="text-center">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($maintenanceRoles as $role)
                        <tr>
                            <td>{{ $role->id }}</td>
                            <td><strong>{{ $role->name }}</strong></td>
                            <td>{{ Str::limit($role->description ?? 'N/A', 60) }}</td>
                            <td class="text-center">
                                <a href="{{ route('maintenance_roles.edit', $role) }}" class="btn btn-warning btn-sm" title="Modifica">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('maintenance_roles.destroy', $role) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Eliminare questa specializzazione?');">
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
                            <td colspan="4" class="text-center py-4">
                                <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                                <p class="text-muted mt-2">Nessuna specializzazione trovata</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
