@extends('layouts.manutentore')

@section('title', 'Lista ticket')

@php
    // Con filtro "tutti" mostriamo una lista piatta (ordinata per ID desc).
    // Con filtri specifici manteniamo un singolo gruppo implicito.
    $isFlatAll = $filter === 'all';
    $grouped = $isFlatAll
        ? collect(['_' => $interventions])
        : collect(['_' => $interventions]);
    $grouped = $grouped->filter(fn ($c) => $c->isNotEmpty());
    $pageSize = 25;
    $totalCount = $interventions->count();
@endphp

@section('content')
    <div x-data="ticketsListView('{{ $filter }}')" class="pt-3 pb-6">

        {{-- Bottone + Nuovo ticket --}}
        <div class="px-3 mb-3">
            <button type="button"
                    @click="$store.quickOpen.show()"
                    class="w-full h-11 rounded-xl bg-gray-100 text-gray-700 font-semibold text-sm flex items-center justify-center gap-1.5 active:bg-gray-200">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                </svg>
                Nuovo ticket
            </button>
        </div>

        {{-- Search --}}
        <form method="GET" action="{{ route('m.tickets.index') }}" class="px-3 mb-3">
            @if ($filter !== 'all')
                <input type="hidden" name="filter" value="{{ $filter }}">
            @endif
            <div class="relative">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="7" stroke-linecap="round"/>
                    <path stroke-linecap="round" d="m20 20-3.5-3.5"/>
                </svg>
                <input type="search" name="q" value="{{ $q }}"
                       placeholder="Cerca titolo, zona, manutentore…"
                       class="w-full h-10 pl-9 pr-3 rounded-xl bg-gray-100 border border-gray-200 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
                @if ($q !== '')
                    <a href="{{ route('m.tickets.index', ['filter' => $filter]) }}"
                       class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center rounded-full text-gray-500 active:bg-gray-200"
                       aria-label="Pulisci">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
                        </svg>
                    </a>
                @endif
            </div>
        </form>

        {{-- Chip filtri --}}
        <div class="px-3 mb-5 pb-1">
            <div class="flex gap-2 overflow-x-auto scrollbar-none -mx-3 px-3 py-1">
                @foreach ($chips as $key => $label)
                    @php
                        $url = route('m.tickets.index', array_filter([
                            'filter' => $key === 'all' ? null : $key,
                            'q'      => $q ?: null,
                        ]));
                    @endphp
                    <a href="{{ $url }}"
                       @click.prevent="switchFilter('{{ $key }}')"
                       :class="currentFilter === '{{ $key }}'
                            ? 'bg-red-600 text-white border-red-600'
                            : 'bg-white text-gray-700 border-gray-300 active:bg-gray-100'"
                       class="shrink-0 h-8 px-3 inline-flex items-center rounded-full text-sm font-medium border transition-colors">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Loader sottile mentre si cambia filtro --}}
        <div x-show="loading" x-transition.opacity class="px-3 pb-2 text-center text-xs text-gray-400">
            Aggiorno…
        </div>

        {{-- Lista ticket --}}
        <div class="px-3" id="ticketsListContainer">
            @if ($interventions->isEmpty())
                <div class="py-12 text-center">
                    <div class="text-3xl mb-2">🔍</div>
                    <div class="font-medium text-gray-700">Nessun ticket trovato</div>
                    @if ($q !== '' || $filter !== 'all')
                        <a href="{{ route('m.tickets.index') }}" class="inline-block mt-3 text-sm text-brand-600 active:text-brand-700 font-semibold">
                            Rimuovi filtri
                        </a>
                    @endif
                </div>
            @else
                @php $ordinal = 0; @endphp
                <div data-load-more data-page-size="{{ $pageSize }}" data-total="{{ $totalCount }}">
                    @foreach ($grouped as $key => $items)
                        <div class="space-y-3">
                            @foreach ($items as $intervention)
                                <div data-lm-item data-ordinal="{{ $ordinal }}"
                                    @if ($ordinal >= $pageSize) style="display:none" @endif>
                                    @include('manutentore.partials.intervention-card', ['intervention' => $intervention])
                                </div>
                                @php $ordinal++; @endphp
                            @endforeach
                        </div>
                    @endforeach

                    @if ($totalCount > $pageSize)
                        <div class="mt-5 text-center" data-lm-footer>
                            <button type="button" data-lm-button
                                    class="h-10 px-5 rounded-xl bg-gray-100 text-gray-800 font-semibold text-sm active:bg-gray-200">
                                Carica altro <span class="text-gray-500 font-normal ml-1" data-lm-count></span>
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <x-m.quick-open :areas="$quickAreas" :departments="$quickDepartments"
                    :equipments="$quickEquipments" :maintenance-roles="$quickMaintenanceRoles"
                    :manutentori="$quickManutentori" />

    @push('scripts')
    <script>
        function ticketsListView(initialFilter) {
            return {
                currentFilter: initialFilter || 'all',
                loading: false,

                init() {
                    this.wireLoadMore();
                },

                wireLoadMore() {
                    const root = document.querySelector('[data-load-more]');
                    if (!root) return;
                    const pageSize = parseInt(root.dataset.pageSize || '25', 10);
                    const total = parseInt(root.dataset.total || '0', 10);
                    const button = root.querySelector('[data-lm-button]');
                    const countEl = root.querySelector('[data-lm-count]');
                    const footer = root.querySelector('[data-lm-footer]');
                    const updateCount = () => {
                        if (!button || !footer) return;
                        const hidden = root.querySelectorAll('[data-lm-item][style*="display:none"], [data-lm-item][style*="display: none"]').length;
                        if (hidden === 0) {
                            footer.style.display = 'none';
                        } else {
                            footer.style.display = '';
                            if (countEl) countEl.textContent = '(' + Math.min(pageSize, hidden) + ')';
                        }
                    };
                    updateCount();
                    if (button) {
                        button.addEventListener('click', () => {
                            const hidden = Array.from(root.querySelectorAll('[data-lm-item]'))
                                .filter(el => el.style.display === 'none');
                            hidden.slice(0, pageSize).forEach(el => { el.style.display = ''; });
                            updateCount();
                        });
                    }
                },

                async switchFilter(key) {
                    if (this.loading || this.currentFilter === key) return;
                    this.currentFilter = key;
                    this.loading = true;

                    const params = new URLSearchParams(window.location.search);
                    if (key === 'all') {
                        params.delete('filter');
                    } else {
                        params.set('filter', key);
                    }
                    const qs = params.toString();
                    const url = window.location.pathname + (qs ? '?' + qs : '');

                    try {
                        const res = await fetch(url, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const html = await res.text();
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const next = doc.getElementById('ticketsListContainer');
                        const cur  = document.getElementById('ticketsListContainer');
                        if (next && cur) {
                            cur.replaceChildren(...Array.from(next.childNodes));
                            this.wireLoadMore();
                        }
                        history.replaceState(null, '', url);
                    } catch (e) {
                        this.$store.toasts?.push('Impossibile aggiornare la lista.', 'error');
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }

        // Sincronizza il filtro quando l'utente usa back/forward del browser.
        window.addEventListener('popstate', () => { window.location.reload(); });
    </script>
    @endpush
@endsection
