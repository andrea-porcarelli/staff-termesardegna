@extends('layouts.app')

@section('title', 'Rapportini - Rapportini')

@section('page-title', 'Rapportini')

@section('content')

@php
    $statusColors = ['draft' => 'bg-secondary', 'completed' => 'bg-success', 'chiuso' => 'bg-dark'];
    $statusLabels = ['draft' => 'Bozza', 'completed' => 'Completato', 'chiuso' => 'Chiuso'];
@endphp

{{-- Filtri --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtri</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('reports.index') }}">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Stato</label>
                    <select name="status" class="form-select">
                        <option value="">Tutti</option>
                        <option value="draft"     {{ request('status') === 'draft'     ? 'selected' : '' }}>Bozza</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completato</option>
                        <option value="chiuso"    {{ request('status') === 'chiuso'    ? 'selected' : '' }}>Chiuso</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Operatore</label>
                    <select name="user_id" class="form-select">
                        <option value="">Tutti</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Data dal</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Data al</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-light w-100">
                        <i class="bi bi-search me-1"></i>Filtra
                    </button>
                    @if(request()->hasAny(['status','user_id','date_from','date_to']))
                        <a href="{{ route('reports.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Tabella --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4><i class="bi bi-journal-text me-2"></i>Lista Rapportini</h4>
        <span class="badge bg-secondary">{{ $reports->total() }} totali</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Intervento</th>
                        <th>Destinazione</th>
                        <th>Operatore</th>
                        <th>Orario</th>
                        <th>Stato</th>
                        <th class="text-center">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                        <tr>
                            <td class="text-nowrap">
                                {{ $report->report_date->format('d/m/Y') }}
                            </td>
                            <td>
                                <strong>{{ $report->intervention->title }}</strong>
                            </td>
                            <td>
                                @if($report->intervention->equipment)
                                    <small><i class="bi bi-gear me-1"></i>{{ $report->intervention->equipment->name }}</small>
                                @else
                                    <small><i class="bi bi-building me-1"></i>{{ $report->intervention->area?->name }} / {{ $report->intervention->department?->name }}</small>
                                @endif
                            </td>
                            <td>
                                <i class="bi bi-person me-1"></i>{{ $report->user->name }}
                            </td>
                            <td class="text-nowrap">
                                @if($report->start_time && $report->end_time)
                                    {{ substr($report->start_time, 0, 5) }} – {{ substr($report->end_time, 0, 5) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $statusColors[$report->status] ?? 'bg-secondary' }}">
                                    {{ $statusLabels[$report->status] ?? $report->status }}
                                </span>
                            </td>
                            <td class="text-center text-nowrap">
                                <a href="{{ route('interventions.show', $report->intervention) }}"
                                   class="btn btn-info btn-sm" title="Dettagli intervento">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($report->status !== 'chiuso')
                                    <a href="{{ route('interventions.reports.edit', [$report->intervention, $report]) }}"
                                       class="btn btn-warning btn-sm" title="Modifica">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('interventions.reports.close', [$report->intervention, $report]) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Chiudere il rapportino? L\'intervento verrà marcato come completato.')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-dark btn-sm" title="Chiudi rapportino">
                                            <i class="bi bi-lock"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size:48px;color:#ccc"></i>
                                <p class="text-muted mt-2">Nessun rapportino trovato</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($reports->hasPages())
        <div class="card-body border-top">
            {{ $reports->links() }}
        </div>
    @endif
</div>

@endsection
