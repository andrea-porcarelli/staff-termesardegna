@extends('layouts.manutentore')

@section('title', 'Piano orario')

@php
    $dayLabels = [
        1 => 'Lunedì',
        2 => 'Martedì',
        3 => 'Mercoledì',
        4 => 'Giovedì',
        5 => 'Venerdì',
        6 => 'Sabato',
        0 => 'Domenica',
    ];

    $typeChip = [
        'lavorativo'    => ['Turno', 'bg-emerald-100 text-emerald-700 border-emerald-200'],
        'pausa_pranzo'  => ['Pausa pranzo', 'bg-amber-100 text-amber-800 border-amber-200'],
        'ferie'         => ['Ferie', 'bg-sky-100 text-sky-700 border-sky-200'],
        'riposi'        => ['Riposo', 'bg-gray-100 text-gray-600 border-gray-200'],
    ];

    $fmtTime = fn ($t) => $t ? substr($t, 0, 5) : null;
@endphp

@section('content')
    <div class="px-4 pt-4 pb-6 space-y-5">

        <header>
            <div class="text-xs uppercase tracking-wide text-gray-500">Turnazione</div>
            <h2 class="text-xl font-bold text-gray-900">Piano orario</h2>
            <p class="text-xs text-gray-500 mt-1">{{ $user->name }}</p>
        </header>

        {{-- ─── Settimana tipo (slot ricorrenti) ──────────────────── --}}
        <section>
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700 mb-2">Settimana tipo</h3>
            <div class="space-y-2">
                @foreach ($dayLabels as $dow => $label)
                    @php $daySlots = $recurringByDay->get($dow, collect()); @endphp
                    <div class="rounded-xl border border-gray-200 bg-white p-3">
                        <div class="flex items-center justify-between mb-1">
                            <div class="text-sm font-semibold text-gray-900">{{ $label }}</div>
                            <span class="text-xs text-gray-400">{{ $daySlots->count() }}</span>
                        </div>
                        @if ($daySlots->isEmpty())
                            <div class="text-[12px] text-gray-400 italic">Nessun turno</div>
                        @else
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($daySlots as $slot)
                                    @php [$tLabel, $tClass] = $typeChip[$slot->type] ?? ['—', 'bg-gray-100 text-gray-600 border-gray-200']; @endphp
                                    <span class="inline-flex items-center gap-1 h-7 px-2 rounded-md border text-[11px] font-medium {{ $tClass }}">
                                        <span>{{ $tLabel }}</span>
                                        @if ($fmtTime($slot->start_time) && $fmtTime($slot->end_time))
                                            <span class="opacity-60">{{ $fmtTime($slot->start_time) }}–{{ $fmtTime($slot->end_time) }}</span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ─── Eccezioni (slot non ricorrenti) ───────────────────── --}}
        <section>
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700 mb-2">Prossimi 30 giorni</h3>
            @if ($oneOff->isEmpty())
                <x-m.card class="text-center py-6">
                    <div class="text-xs text-gray-500">Nessuna variazione programmata.</div>
                </x-m.card>
            @else
                <div class="space-y-2">
                    @foreach ($oneOff as $slot)
                        @php [$tLabel, $tClass] = $typeChip[$slot->type] ?? ['—', 'bg-gray-100 text-gray-600 border-gray-200']; @endphp
                        <div class="rounded-xl border border-gray-200 bg-white p-3 flex items-center gap-3">
                            <div class="text-center w-12 shrink-0">
                                <div class="text-[10px] uppercase text-gray-500">{{ $slot->date->isoFormat('MMM') }}</div>
                                <div class="text-lg font-bold text-gray-900 leading-none">{{ $slot->date->format('d') }}</div>
                                <div class="text-[10px] text-gray-500">{{ $slot->date->isoFormat('ddd') }}</div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="inline-flex items-center gap-1 h-7 px-2 rounded-md border text-[11px] font-medium {{ $tClass }}">
                                    <span>{{ $tLabel }}</span>
                                    @if ($fmtTime($slot->start_time) && $fmtTime($slot->end_time))
                                        <span class="opacity-60">{{ $fmtTime($slot->start_time) }}–{{ $fmtTime($slot->end_time) }}</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

    </div>
@endsection
