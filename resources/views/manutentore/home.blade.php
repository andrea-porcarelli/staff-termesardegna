@extends('layouts.manutentore')

@section('title', 'Dashboard')

@php
    $totalActive = $scaduti->count() + $altaPriorita->count() + $pianificati->count() + $bassaPriorita->count();
    $isManutentore = auth()->user()->role === 'manutentore';
@endphp

@section('content')
    <div class="px-4 pt-4 pb-6 space-y-6">

        <header class="flex items-center gap-3">
            <div class="flex-1 min-w-0">
                <div class="text-xs uppercase tracking-wide text-gray-500">
                    {{ now()->isoFormat('dddd D MMMM') }}
                </div>
                <h2 class="text-xl font-bold text-gray-900 truncate">
                    Ciao {{ explode(' ', $user->name)[0] }}
                </h2>
            </div>

            @if ($scaduti->isNotEmpty())
                <div class="text-right">
                    <div class="text-2xl font-bold text-red-600 leading-none">{{ $scaduti->count() }}</div>
                    <div class="text-[11px] uppercase tracking-wide text-red-600/80 mt-1">scaduti</div>
                </div>
            @else
                <div class="text-right">
                    <div class="text-2xl font-bold text-brand-600 leading-none">{{ $totalActive }}</div>
                    <div class="text-[11px] uppercase tracking-wide text-gray-500 mt-1">7 giorni</div>
                </div>
            @endif
        </header>

        {{-- Azioni rapide --}}
        <div class="grid {{ $isManutentore ? 'grid-cols-2' : 'grid-cols-1' }} gap-2">
            <button type="button"
                    @click="$store.quickOpen.show()"
                    class="h-11 rounded-xl bg-brand-600 text-white font-semibold text-sm flex items-center justify-center gap-1.5 active:bg-brand-700">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                </svg>
                Apri ticket
            </button>

            @if ($isManutentore)
                <button type="button"
                        @click="$dispatch('open-standalone-report')"
                        class="h-11 rounded-xl bg-gray-100 text-gray-800 font-semibold text-sm flex items-center justify-center gap-1.5 active:bg-gray-200">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h4m2-10H7a2 2 0 0 0-2 2v14l3-3h9a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2zM11 2v4m2-4v4"/>
                    </svg>
                    Apri rapportino
                </button>
            @endif
        </div>

        {{-- ─── 1. SCADUTI (tutti) ─────────────────────────────────── --}}
        @if ($scaduti->isNotEmpty())
            <section>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-red-600 flex items-center gap-1.5">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 9v4m0 4h.01M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        </svg>
                        Scaduti
                    </h3>
                    <span class="text-xs text-gray-400">{{ $scaduti->count() }}</span>
                </div>
                <div class="space-y-3">
                    @foreach ($scaduti as $intervention)
                        @include('manutentore.partials.intervention-card', ['intervention' => $intervention])
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ─── 2. ALTA PRIORITÀ (7 giorni) ────────────────────────── --}}
        @if ($altaPriorita->isNotEmpty())
            <section>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-orange-600">Alta priorità (7 giorni)</h3>
                    <span class="text-xs text-gray-400">{{ $altaPriorita->count() }}</span>
                </div>
                <div class="space-y-3">
                    @foreach ($altaPriorita as $intervention)
                        @include('manutentore.partials.intervention-card', ['intervention' => $intervention])
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ─── 3. PIANIFICATI (7 giorni) ──────────────────────────── --}}
        @if ($pianificati->isNotEmpty())
            <section>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700">Pianificati (7 giorni)</h3>
                    <span class="text-xs text-gray-400">{{ $pianificati->count() }}</span>
                </div>
                <div class="space-y-3">
                    @foreach ($pianificati as $intervention)
                        @include('manutentore.partials.intervention-card', ['intervention' => $intervention])
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ─── 4. BASSA PRIORITÀ (7 giorni) ───────────────────────── --}}
        @if ($bassaPriorita->isNotEmpty())
            <section>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700">Bassa priorità (7 giorni)</h3>
                    <span class="text-xs text-gray-400">{{ $bassaPriorita->count() }}</span>
                </div>
                <div class="space-y-3">
                    @foreach ($bassaPriorita as $intervention)
                        @include('manutentore.partials.intervention-card', ['intervention' => $intervention])
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ─── Empty state globale ────────────────────────────────── --}}
        @if ($totalActive === 0)
            <x-m.card class="text-center py-10">
                <div class="text-3xl mb-2">✓</div>
                <div class="font-medium text-gray-700">Nessuna attività nei prossimi 7 giorni</div>
                <div class="text-xs text-gray-500 mt-1">Apri l'elenco completo dei ticket dal menu.</div>
            </x-m.card>
        @endif

    </div>

    {{-- FAB --}}
    <button type="button"
            @click="$store.quickOpen.show()"
            class="fixed bottom-20 right-4 z-30 w-14 h-14 rounded-full bg-brand-600 text-white shadow-lg active:bg-brand-700 flex items-center justify-center"
            aria-label="Nuovo ticket">
        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
        </svg>
    </button>

    <x-m.quick-open :areas="$quickAreas" :departments="$quickDepartments"
                    :equipments="$quickEquipments" :maintenance-roles="$quickMaintenanceRoles" />

    @if ($isManutentore)
        <x-m.standalone-report-modal :areas="$quickAreas" :departments="$quickDepartments"
                                     :equipments="$quickEquipments" />
    @endif
@endsection
