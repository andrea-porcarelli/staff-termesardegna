@extends('layouts.app')

@section('title', 'Storia tickets - Rapportini')

@section('page-title', 'Storia tickets')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="bi bi-journals me-2"></i>Log attività sui ticket</h4>
    </div>

    <div class="card-body border-bottom pb-3">
        <form method="GET" action="{{ route('intervention_logs.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label for="f_action" class="form-label form-label-sm mb-1">Azione</label>
                <select id="f_action" name="action" class="form-select form-select-sm">
                    <option value="">Tutte</option>
                    @foreach($actions as $value => $label)
                        <option value="{{ $value }}" {{ request('action') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="f_user" class="form-label form-label-sm mb-1">Utente</label>
                <select id="f_user" name="user_id" class="form-select form-select-sm">
                    <option value="">Tutti</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ (string) request('user_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="f_intervention" class="form-label form-label-sm mb-1">Ticket (ID)</label>
                <input type="number" id="f_intervention" name="intervention_id" class="form-control form-control-sm" value="{{ request('intervention_id') }}" min="1" placeholder="es. 42">
            </div>
            <div class="col-md-2">
                <label for="f_from" class="form-label form-label-sm mb-1">Dal</label>
                <input type="date" id="f_from" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
            </div>
            <div class="col-md-2">
                <label for="f_to" class="form-label form-label-sm mb-1">Al</label>
                <input type="date" id="f_to" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-secondary">
                    <i class="bi bi-funnel me-1"></i>Filtra
                </button>
                @if(request()->hasAny(['action', 'user_id', 'intervention_id', 'from', 'to']))
                    <a href="{{ route('intervention_logs.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x me-1"></i>Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th style="width: 170px;">Data / ora</th>
                        <th>Ticket</th>
                        <th>Utente</th>
                        <th>Azione</th>
                        <th>Dettagli</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>
                                <small class="text-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</small>
                            </td>
                            <td>
                                @if($log->intervention)
                                    <a href="{{ route('interventions.show', $log->intervention_id) }}">
                                        #{{ $log->intervention_id }} — {{ $log->intervention->title }}
                                    </a>
                                @else
                                    <span class="text-muted">#{{ $log->intervention_id }} (eliminato)</span>
                                @endif
                            </td>
                            <td>
                                @if($log->user)
                                    <i class="bi bi-person me-1"></i>{{ $log->user->name }}
                                @else
                                    <span class="text-muted"><i class="bi bi-cpu me-1"></i>Sistema</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-info text-dark">{{ \App\Support\InterventionLogActions::label($log->action) }}</span>
                            </td>
                            <td>
                                @if(!empty($log->metadata))
                                    <code class="small text-muted">{{ json_encode($log->metadata, JSON_UNESCAPED_UNICODE) }}</code>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                                <p class="text-muted mt-2 mb-0">Nessuna voce di log trovata</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($logs->hasPages())
        <div class="card-footer">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
