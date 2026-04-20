@extends('layouts.manutentore')

@section('title', 'Oggi')

@php
    $overdueCount = $overduePianificati->count() + $overdueLiberi->count();
    $totalActive  = $overdueCount
                  + $pianificatiOggi->count()
                  + $altaPriorita->count()
                  + $bassaPriorita->count()
                  + $programmatiFuturi->count();
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

            @if ($overdueCount > 0)
                <div class="text-right">
                    <div class="text-2xl font-bold text-red-600 leading-none">{{ $overdueCount }}</div>
                    <div class="text-[11px] uppercase tracking-wide text-red-600/80 mt-1">in ritardo</div>
                </div>
            @else
                <div class="text-right">
                    <div class="text-2xl font-bold text-brand-600 leading-none">{{ $pianificatiOggi->count() }}</div>
                    <div class="text-[11px] uppercase tracking-wide text-gray-500 mt-1">oggi</div>
                </div>
            @endif
        </header>

        {{-- ─── 1. SCADUTI ────────────────────────────────────────── --}}
        @if ($overdueCount > 0)
            <section>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-red-600 flex items-center gap-1.5">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 9v4m0 4h.01M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        </svg>
                        Scaduti
                    </h3>
                    <span class="text-xs text-gray-400">{{ $overdueCount }}</span>
                </div>

                @if ($overduePianificati->isNotEmpty())
                    <div class="text-xs uppercase tracking-wide text-gray-500 mb-1.5 px-1">
                        Pianificati in ritardo
                    </div>
                    <div class="space-y-3 mb-3">
                        @foreach ($overduePianificati as $intervention)
                            @include('manutentore.partials.intervention-card', ['intervention' => $intervention])
                        @endforeach
                    </div>
                @endif

                @if ($overdueLiberi->isNotEmpty())
                    <div class="text-xs uppercase tracking-wide text-gray-500 mb-1.5 px-1">
                        Liberi in ritardo
                    </div>
                    <div class="space-y-3">
                        @foreach ($overdueLiberi as $intervention)
                            @include('manutentore.partials.intervention-card', ['intervention' => $intervention])
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        {{-- ─── 2. PIANIFICATI OGGI ────────────────────────────────── --}}
        @if ($pianificatiOggi->isNotEmpty())
            <section>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-900">Pianificati oggi</h3>
                    <span class="text-xs text-gray-400">{{ $pianificatiOggi->count() }}</span>
                </div>
                <div class="space-y-3">
                    @foreach ($pianificatiOggi as $intervention)
                        @include('manutentore.partials.intervention-card', ['intervention' => $intervention])
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ─── 3. ALTA PRIORITÀ ───────────────────────────────────── --}}
        @if ($altaPriorita->isNotEmpty())
            <section>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-orange-600">Alta priorità</h3>
                    <span class="text-xs text-gray-400">{{ $altaPriorita->count() }}</span>
                </div>
                <div class="space-y-3">
                    @foreach ($altaPriorita as $intervention)
                        @include('manutentore.partials.intervention-card', ['intervention' => $intervention])
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ─── 4. BASSA PRIORITÀ ──────────────────────────────────── --}}
        @if ($bassaPriorita->isNotEmpty())
            <section>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700">Bassa priorità</h3>
                    <span class="text-xs text-gray-400">{{ $bassaPriorita->count() }}</span>
                </div>
                <div class="space-y-3">
                    @foreach ($bassaPriorita as $intervention)
                        @include('manutentore.partials.intervention-card', ['intervention' => $intervention])
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ─── 5. PROGRAMMATI FUTURI ──────────────────────────────── --}}
        @if ($programmatiFuturi->isNotEmpty())
            <section>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700">Programmati (30 gg)</h3>
                    <span class="text-xs text-gray-400">{{ $programmatiFuturi->count() }}</span>
                </div>
                <div class="space-y-3">
                    @foreach ($programmatiFuturi as $intervention)
                        @include('manutentore.partials.intervention-card', ['intervention' => $intervention])
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ─── Empty state globale ────────────────────────────────── --}}
        @if ($totalActive === 0)
            <x-m.card class="text-center py-10">
                <div class="text-3xl mb-2">✓</div>
                <div class="font-medium text-gray-700">Nessun intervento attivo</div>
                <div class="text-xs text-gray-500 mt-1">Tocca + per aprire un intervento ordinario.</div>
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

    <x-m.quick-open :areas="$quickAreas" :departments="$quickDepartments" />
@endsection
