<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="bi bi-file-earmark-text" style="font-size: 40px;"></i>
        <h3>Rapportini</h3>
        <p>Sistema Gestionale</p>
    </div>
    <div class="sidebar-menu">
        <a href="{{ route('dashboard') }}" class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        <div style="padding: 10px 25px; opacity: 0.5; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">
            Pianificazione
        </div>
        @if(Auth::user()->role == 'admin')
            <a href="{{ route('areas.index') }}" class="menu-item {{ request()->routeIs('areas.*') ? 'active' : '' }}">
                <i class="bi bi-building"></i>
                <span>Aree</span>
            </a>
            <a href="{{ route('departments.index') }}" class="menu-item {{ request()->routeIs('departments.*') ? 'active' : '' }}">
                <i class="bi bi-diagram-3"></i>
                <span>Zone</span>
            </a>
            <a href="{{ route('equipments.index') }}" class="menu-item {{ request()->routeIs('equipments.*') ? 'active' : '' }}">
                <i class="bi bi-gear"></i>
                <span>Impianti/Macchine</span>
            </a>
            <a href="{{ route('interventions.index') }}" class="menu-item {{ request()->routeIs('interventions.index') || request()->routeIs('interventions.create') || request()->routeIs('interventions.edit') || request()->routeIs('interventions.show') ? 'active' : '' }}">
                <i class="bi bi-calendar-check"></i>
                <span>Interventi</span>
            </a>
        @endif
        <a href="{{ route('interventions.calendar') }}" class="menu-item {{ request()->routeIs('interventions.calendar') ? 'active' : '' }}">
            <i class="bi bi-calendar3"></i>
            <span>Calendario</span>
        </a>

        @if(auth()->user()->role === 'admin')
        <div style="padding: 10px 25px; opacity: 0.5; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin-top: 15px;">
            Amministrazione
        </div>
        @endif

        @if(auth()->user()->role === 'admin')
        <a href="{{ route('users.index') }}" class="menu-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <i class="bi bi-people"></i>
            <span>Utenti</span>
        </a>
        <a href="{{ route('maintenance_roles.index') }}" class="menu-item {{ request()->routeIs('maintenance_roles.*') ? 'active' : '' }}">
            <i class="bi bi-award"></i>
            <span>Specializzazioni</span>
        </a>
        <a href="{{ route('teams.index') }}" class="menu-item {{ request()->routeIs('teams.*') ? 'active' : '' }}">
            <i class="bi bi-people-fill"></i>
            <span>Team</span>
        </a>
        @endif
    </div>
    <form method="POST" action="{{ route('logout') }}" style="position: relative; height: 80px;">
        @csrf
        <button type="submit" class="logout-btn">
            <i class="bi bi-box-arrow-right me-2"></i>Esci
        </button>
    </form>
</div>
