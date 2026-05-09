@extends('layouts.manutentore')

@section('title', 'Calendario')

@php
    $today = today();
    $weekDays = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];

    $priorityColor = [
        'high'       => 'bg-orange-500',
        'medium'     => 'bg-amber-400',
        'fixed_date' => 'bg-violet-500',
        'low'        => 'bg-sky-500',
    ];
@endphp

@section('content')
    <div x-data="{ selectedDate: '{{ $today->toDateString() }}' }" class="px-4 pt-4 pb-6 space-y-4">

        {{-- Navigazione mese --}}
        <header class="flex items-center justify-between">
            <a href="{{ route('m.calendar', ['year' => $prev->year, 'month' => $prev->month]) }}"
               class="w-10 h-10 flex items-center justify-center rounded-full text-gray-700 active:bg-gray-100"
               aria-label="Mese precedente">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/>
                </svg>
            </a>
            <div class="text-center">
                <div class="text-xs uppercase tracking-wide text-gray-500">Calendario</div>
                <h2 class="text-lg font-bold text-gray-900">{{ $current->isoFormat('MMMM YYYY') }}</h2>
            </div>
            <a href="{{ route('m.calendar', ['year' => $next->year, 'month' => $next->month]) }}"
               class="w-10 h-10 flex items-center justify-center rounded-full text-gray-700 active:bg-gray-100"
               aria-label="Mese successivo">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/>
                </svg>
            </a>
        </header>

        {{-- Griglia mese --}}
        <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
            <div class="grid grid-cols-7 bg-gray-50 border-b border-gray-200 text-center">
                @foreach ($weekDays as $d)
                    <div class="py-2 text-[11px] font-semibold uppercase text-gray-500">{{ $d }}</div>
                @endforeach
            </div>

            @foreach ($weeks as $week)
                <div class="grid grid-cols-7">
                    @foreach ($week as $day)
                        @php
                            $dateStr = $day->toDateString();
                            $items = $byDay->get($dateStr, collect());
                            $isCurrentMonth = $day->month === $current->month;
                            $isToday = $day->isSameDay($today);
                        @endphp
                        <button type="button"
                                @click="selectedDate = '{{ $dateStr }}'"
                                class="aspect-square flex flex-col items-center justify-start py-1 border-t border-r border-gray-100 text-xs transition-colors {{ $isCurrentMonth ? 'bg-white' : 'bg-gray-50' }}"
                                :class="selectedDate === '{{ $dateStr }}' ? 'ring-2 ring-brand-500 ring-inset' : ''">
                            <div class="w-7 h-7 flex items-center justify-center rounded-full text-[12px] font-semibold
                                        {{ $isToday ? 'bg-brand-600 text-white' : ($isCurrentMonth ? 'text-gray-800' : 'text-gray-400') }}">
                                {{ $day->day }}
                            </div>
                            @if ($items->isNotEmpty())
                                <div class="flex gap-0.5 mt-0.5">
                                    @foreach ($items->take(3) as $i)
                                        <span class="w-1.5 h-1.5 rounded-full {{ $priorityColor[$i->priority] ?? 'bg-gray-400' }}"></span>
                                    @endforeach
                                </div>
                                @if ($items->count() > 3)
                                    <div class="text-[9px] text-gray-500 leading-none mt-0.5">+{{ $items->count() - 3 }}</div>
                                @endif
                            @endif
                        </button>
                    @endforeach
                </div>
            @endforeach
        </div>

        {{-- Lista ticket del giorno selezionato --}}
        @foreach ($weeks as $week)
            @foreach ($week as $day)
                @php
                    $dateStr = $day->toDateString();
                    $items = $byDay->get($dateStr, collect());
                @endphp
                <div x-show="selectedDate === '{{ $dateStr }}'" x-cloak class="space-y-2">
                    <div class="text-sm font-semibold text-gray-900">
                        {{ $day->isoFormat('dddd D MMMM') }}
                        @if ($items->isNotEmpty())
                            <span class="text-xs font-normal text-gray-500">· {{ $items->count() }} ticket</span>
                        @endif
                    </div>

                    @if ($items->isEmpty())
                        <x-m.card class="text-center py-6">
                            <div class="text-xs text-gray-500">Nessun ticket questo giorno.</div>
                        </x-m.card>
                    @else
                        @foreach ($items as $i)
                            <button type="button"
                                    @click="$dispatch('open-ticket', { id: {{ $i->id }} })"
                                    class="w-full text-left rounded-xl border border-gray-200 bg-white p-3 active:bg-gray-50 flex items-start gap-3">
                                <span class="w-2 h-2 rounded-full mt-1.5 shrink-0 {{ $priorityColor[$i->priority] ?? 'bg-gray-400' }}"></span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 text-[11px] uppercase tracking-wide text-gray-500">
                                        <span class="font-mono">#{{ $i->id }}</span>
                                        @if ($i->scheduled_start_time)
                                            <span>·</span>
                                            <span>{{ substr($i->scheduled_start_time, 0, 5) }}</span>
                                        @endif
                                    </div>
                                    <div class="text-sm font-semibold text-gray-900 truncate">{{ $i->title }}</div>
                                    @php
                                        $place = collect([$i->area?->name, $i->department?->name, $i->equipment?->name])->filter()->implode(' · ');
                                    @endphp
                                    @if ($place)
                                        <div class="text-xs text-gray-500 truncate">{{ $place }}</div>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    @endif
                </div>
            @endforeach
        @endforeach

    </div>
@endsection
