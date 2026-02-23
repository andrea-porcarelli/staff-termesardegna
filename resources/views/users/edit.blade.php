@extends('layouts.app')

@section('title', 'Gestione Utenti - Modifica utente')

@section('page-title', 'Modifica utente')

@section('content')

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">
                                <i class="bi bi-person me-1"></i>Nome Completo
                            </label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name', $user->name) }}"
                                   required
                                   autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope me-1"></i>Email
                            </label>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="email"
                                   name="email"
                                   value="{{ old('email', $user->email) }}"
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Lascia i campi password vuoti se non vuoi modificarla
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock me-1"></i>Nuova Password
                            </label>
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="password"
                                   name="password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">
                                <i class="bi bi-lock-fill me-1"></i>Conferma Nuova Password
                            </label>
                            <input type="password"
                                   class="form-control"
                                   id="password_confirmation"
                                   name="password_confirmation">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="role" class="form-label">
                            <i class="bi bi-shield-check me-1"></i>Ruolo
                        </label>
                        <select class="form-select @error('role') is-invalid @enderror"
                                id="role"
                                name="role"
                                required
                                onchange="toggleRoleSections()">
                            <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="operator" {{ old('role', $user->role) == 'operator' ? 'selected' : '' }}>Operatore</option>
                            <option value="manutentore" {{ old('role', $user->role) == 'manutentore' ? 'selected' : '' }}>Manutentore</option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Sezione Manutentore (visibile solo per Manutentore) -->
                    <div id="manutentore-section" class="mb-4" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="maintenance_role_id" class="form-label">
                                    <i class="bi bi-award me-1"></i>Specializzazione
                                </label>
                                <select class="form-select @error('maintenance_role_id') is-invalid @enderror"
                                        id="maintenance_role_id"
                                        name="maintenance_role_id">
                                    <option value="">Nessuna specializzazione</option>
                                    @foreach($maintenanceRoles as $mRole)
                                        <option value="{{ $mRole->id }}" {{ old('maintenance_role_id', $user->maintenance_role_id) == $mRole->id ? 'selected' : '' }}>
                                            {{ $mRole->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('maintenance_role_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        @php $userTeamIds = old('teams', $user->teams->pluck('id')->toArray()); @endphp
                        <label class="form-label">
                            <i class="bi bi-people-fill me-1"></i>Team
                        </label>
                        <div class="card">
                            <div class="card-body">
                                <p class="text-muted mb-3">Seleziona i team di appartenenza:</p>
                                <div class="row">
                                    @forelse($teams as $team)
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       name="teams[]"
                                                       value="{{ $team->id }}"
                                                       id="team_{{ $team->id }}"
                                                       {{ in_array($team->id, $userTeamIds) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="team_{{ $team->id }}">
                                                    {{ $team->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-muted mb-0">Nessun team disponibile. <a href="{{ route('teams.create') }}">Crea un team</a>.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sezione Zone (visibile per Operator e Manutentore) -->
                    <div id="departments-section" class="mb-4" style="display: none;">
                        <label class="form-label">
                            <i class="bi bi-building me-1"></i>Zone Assegnate
                        </label>
                        <div class="card">
                            <div class="card-body">
                                <p class="text-muted mb-3">Seleziona le zone assegnate a questo utente:</p>
                                @php
                                    $userDepartmentIds = old('departments', $user->departments->pluck('id')->toArray());
                                @endphp
                                @forelse($areas as $area)
                                    <div class="mb-3">
                                        <h6 class="fw-bold text-primary">
                                            <i class="bi bi-geo-alt-fill me-1"></i>{{ $area->name }}
                                        </h6>
                                        @forelse($area->departments as $department)
                                            <div class="form-check ms-4">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       name="departments[]"
                                                       value="{{ $department->id }}"
                                                       id="dept_{{ $department->id }}"
                                                       {{ in_array($department->id, $userDepartmentIds) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="dept_{{ $department->id }}">
                                                    {{ $department->name }}
                                                </label>
                                            </div>
                                        @empty
                                            <p class="text-muted ms-4 mb-0">Nessuna zona disponibile</p>
                                        @endforelse
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">Nessuna area configurata</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Salva Modifiche
                        </button>
                        <a href="{{ route('users.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>Annulla
                        </a>
                    </div>
                </form>
            </div>
        </div>

@push('scripts')
<script>
function toggleRoleSections() {
    const role = document.getElementById('role').value;
    const manutentoreSection = document.getElementById('manutentore-section');
    const departmentsSection = document.getElementById('departments-section');

    manutentoreSection.style.display = (role === 'manutentore') ? 'block' : 'none';
    departmentsSection.style.display = (role === 'operator' || role === 'manutentore') ? 'block' : 'none';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleRoleSections();
});
</script>
@endpush
@endsection
