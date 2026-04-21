@extends('layouts.manutentore')

@section('title', 'Rapportini')

@php
    $isManutentore = $isManutentore ?? false;
    $formatDuration = function (?int $minutes): string {
        if (! $minutes) return '—';
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        if ($h > 0 && $m > 0) return "{$h}h {$m}m";
        if ($h > 0) return "{$h}h";
        return "{$m}m";
    };
@endphp

@section('content')
    <div class="px-4 pt-4 pb-6 space-y-4">

        <header>
            <div class="text-xs uppercase tracking-wide text-gray-500">Storico</div>
            <h2 class="text-xl font-bold text-gray-900">Rapportini</h2>
            <p class="text-xs text-gray-500 mt-1">
                @if ($isManutentore)
                    Tutti i rapportini che hai inserito.
                @else
                    Rapportini dei ticket nelle tue zone di competenza.
                @endif
            </p>
        </header>

        @forelse ($reports as $report)
            @php
                $intervention = $report->intervention;
                $isStandalone = ! $intervention;
                $place = $intervention
                    ? collect([$intervention->area?->name, $intervention->department?->name, $intervention->equipment?->name])
                        ->filter()->implode(' · ')
                    : null;
            @endphp
            <article @if ($intervention) @click="$dispatch('open-ticket', { id: {{ $intervention->id }} })" role="button" @endif
                     class="rounded-xl border border-gray-200 bg-white p-3 space-y-2 {{ $intervention ? 'active:bg-gray-50 cursor-pointer' : '' }}">
                <div class="flex items-start gap-2">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 text-[11px] uppercase tracking-wide text-gray-500">
                            <span>{{ $report->report_date->isoFormat('ddd D MMM YYYY') }}</span>
                            <span>·</span>
                            <span>{{ $formatDuration($report->duration_minutes) }}</span>
                        </div>
                        @if ($isStandalone)
                            <div class="mt-0.5 text-[13px] font-semibold text-sky-700 inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h4m2-10H7a2 2 0 0 0-2 2v14l3-3h9a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2z"/>
                                </svg>
                                Rapportino libero
                            </div>
                        @else
                            <div class="mt-0.5 text-[14px] font-semibold text-gray-900 truncate">
                                <span class="font-mono text-gray-400 text-[12px]">#{{ $intervention->id }}</span>
                                {{ $intervention->title }}
                            </div>
                            @if ($place)
                                <div class="text-xs text-gray-500 truncate">{{ $place }}</div>
                            @endif
                        @endif
                    </div>
                    @if ($report->is_final)
                        <span class="shrink-0 inline-flex h-6 px-2 items-center rounded-md bg-emerald-100 text-emerald-700 text-[10px] font-semibold">
                            ✓ finale
                        </span>
                    @elseif ($report->next_work_date)
                        <span class="shrink-0 inline-flex h-6 px-2 items-center rounded-md bg-amber-100 text-amber-800 text-[10px] font-semibold">
                            ↻ {{ $report->next_work_date->isoFormat('D MMM') }}
                        </span>
                    @endif
                </div>

                @if ($report->activities)
                    <p class="text-[13px] text-gray-700 line-clamp-3 whitespace-pre-line">{{ $report->activities }}</p>
                @endif

                @if (! $isManutentore)
                    <div class="text-[11px] text-gray-500 inline-flex items-center gap-1">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM4 21a8 8 0 0 1 16 0"/>
                        </svg>
                        {{ $report->user?->name ?? '—' }}
                    </div>
                @endif
            </article>
        @empty
            <x-m.card class="text-center py-10">
                <div class="text-3xl mb-2">📝</div>
                <div class="font-medium text-gray-700">Nessun rapportino</div>
                <div class="text-xs text-gray-500 mt-1">
                    @if ($isManutentore)
                        Quando salverai un rapportino lo troverai qui.
                    @else
                        Non ci sono ancora rapportini sui ticket di tua competenza.
                    @endif
                </div>
            </x-m.card>
        @endforelse

        @if ($reports->hasPages())
            <div class="pt-2">
                {{ $reports->links() }}
            </div>
        @endif

    </div>
@endsection
