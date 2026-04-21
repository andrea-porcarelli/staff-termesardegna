@php
    $user = auth()->user();
    $active = request()->route()?->getName() ?? '';
    $isImpersonating = method_exists($user, 'isImpersonated') && $user->isImpersonated();
    $items = [
        ['name' => 'm.home',          'label' => 'Dashboard',     'icon' => 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z'],
        ['name' => 'm.tickets.index', 'label' => 'Elenco ticket', 'icon' => 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'],
        ['name' => 'm.reports.index', 'label' => 'Rapportini',    'icon' => 'M9 12h6m-6 4h4m2-12H7a2 2 0 0 0-2 2v14l3-3h9a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zM11 0v4m2-4v4'],
        ['name' => 'm.calendar',      'label' => 'Calendario',    'icon' => 'M8 3v3M16 3v3M4 9h16M5 6h14a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1z'],
        ['name' => 'm.schedule',      'label' => 'Piano orario',  'icon' => 'M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'],
        ['name' => 'm.profile',       'label' => 'Profilo',       'icon' => 'M16 11a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM4 21a8 8 0 0 1 16 0'],
    ];
@endphp

<div x-data
     x-cloak
     x-show="$store.drawer.isOpen"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @keydown.escape.window="$store.drawer.hide()"
     class="fixed inset-0 z-50 bg-black/50"
     @click.self="$store.drawer.hide()">

    <aside x-show="$store.drawer.isOpen"
           x-transition:enter="transition ease-out duration-250"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           class="w-72 max-w-[84vw] bg-white h-full flex flex-col shadow-xl">

        <div class="p-4 border-b border-gray-200 flex items-center gap-3 {{ $isImpersonating ? 'bg-purple-50' : '' }}">
            <div class="w-11 h-11 rounded-full {{ $isImpersonating ? 'bg-purple-100 text-purple-700 ring-2 ring-purple-300' : 'bg-brand-100 text-brand-700' }} flex items-center justify-center font-semibold">
                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="font-semibold text-gray-900 truncate">{{ $user->name }}</div>
                <div class="text-xs text-gray-500 truncate">
                    {{ ucfirst($user->role) }}
                    @if ($isImpersonating)
                        · <span class="text-purple-700 font-medium">impersonato</span>
                    @endif
                </div>
            </div>
            <button type="button"
                    @click="$store.drawer.hide()"
                    class="w-9 h-9 flex items-center justify-center rounded-full text-gray-500 active:bg-gray-100"
                    aria-label="Chiudi">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto py-2">
            @foreach ($items as $item)
                @php $isActive = $active === $item['name']; @endphp
                <a href="{{ route($item['name']) }}"
                   class="flex items-center gap-3 px-4 h-12 text-[15px] {{ $isActive ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-gray-800 active:bg-gray-100' }}">
                    <svg class="w-5 h-5 {{ $isActive ? 'text-brand-600' : 'text-gray-500' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                    </svg>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        @if ($isImpersonating)
            <a href="{{ route('impersonate.leave') }}"
               class="flex items-center gap-2 justify-center mx-3 mt-2 mb-1 h-11 rounded-xl bg-purple-600 text-white font-semibold active:bg-purple-700">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M16 17l5-5-5-5M21 12H9M13 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6"/>
                </svg>
                Termina impersonificazione
            </a>
        @endif

        <form method="POST" action="{{ route('logout') }}" class="p-3 border-t border-gray-200">
            @csrf
            <button type="submit"
                    class="w-full h-11 flex items-center justify-center gap-2 rounded-xl bg-gray-100 text-gray-800 font-semibold active:bg-gray-200">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 12H3m0 0 4-4m-4 4 4 4M9 4h9a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H9"/>
                </svg>
                Esci
            </button>
        </form>
    </aside>
</div>
