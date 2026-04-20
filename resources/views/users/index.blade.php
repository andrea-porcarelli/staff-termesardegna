@extends('layouts.app')

@section('title', 'Gestione Utenti - Rapportini')

@section('page-title', 'Gestione Utenti')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4><i class="bi bi-people me-2"></i>Lista Utenti</h4>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Cerca..." value="{{ $search ?? '' }}" style="width:200px">
                <button type="submit" class="btn btn-light btn-sm"><i class="bi bi-search"></i></button>
                @if($search ?? '')
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x"></i>
                    </a>
                @endif
            </form>
            <a href="{{ route('users.create') }}" class="btn btn-light">
                <i class="bi bi-plus-circle me-2"></i>Nuovo Utente
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
                            <a href="{{ route('users.index', ['search'=>$search,'sort'=>'id','direction'=>$nextDir]) }}" class="text-decoration-none text-dark">
                                ID {!! ($sort==='id') ? ($dir==='asc' ? '▲' : '▼') : '⇅' !!}
                            </a>
                        </th>
                        <th>
                            @php $nextDir = ($sort==='name' && $dir==='asc') ? 'desc' : 'asc'; @endphp
                            <a href="{{ route('users.index', ['search'=>$search,'sort'=>'name','direction'=>$nextDir]) }}" class="text-decoration-none text-dark">
                                Nome {!! ($sort==='name') ? ($dir==='asc' ? '▲' : '▼') : '⇅' !!}
                            </a>
                        </th>
                        <th>
                            @php $nextDir = ($sort==='email' && $dir==='asc') ? 'desc' : 'asc'; @endphp
                            <a href="{{ route('users.index', ['search'=>$search,'sort'=>'email','direction'=>$nextDir]) }}" class="text-decoration-none text-dark">
                                Email {!! ($sort==='email') ? ($dir==='asc' ? '▲' : '▼') : '⇅' !!}
                            </a>
                        </th>
                        <th>Ruolo</th>
                        <th>Stato</th>
                        <th>
                            @php $nextDir = ($sort==='created_at' && $dir==='asc') ? 'desc' : 'asc'; @endphp
                            <a href="{{ route('users.index', ['search'=>$search,'sort'=>'created_at','direction'=>$nextDir]) }}" class="text-decoration-none text-dark">
                                Data Creazione {!! ($sort==='created_at') ? ($dir==='asc' ? '▲' : '▼') : '⇅' !!}
                            </a>
                        </th>
                        <th class="text-center">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td><strong>{{ $user->name }}</strong></td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->role }}</td>
                            <td>
                                @if($user->active)
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Attivo</span>
                                @else
                                    <span class="badge bg-secondary"><i class="bi bi-slash-circle me-1"></i>Disattivato</span>
                                @endif
                            </td>
                            <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-center">
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-warning btn-sm">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if($user->id !== auth()->id())
                                    <form action="{{ route('users.toggle-active', $user) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('{{ $user->active ? "Disattivare" : "Riattivare" }} {{ $user->name }}?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="btn btn-sm {{ $user->active ? 'btn-secondary' : 'btn-success' }}"
                                                title="{{ $user->active ? 'Disattiva utente' : 'Riattiva utente' }}">
                                            <i class="bi {{ $user->active ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                        </button>
                                    </form>
                                    <a href="{{ route('impersonate', $user->id) }}" class="btn btn-info btn-sm" title="Impersona utente">
                                        <i class="bi bi-person-badge"></i>
                                    </a>
                                    <form action="{{ route('users.destroy', $user) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Sei sicuro di voler eliminare questo utente?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                                <p class="text-muted mt-2">Nessun utente trovato</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
