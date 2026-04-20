@props([
    'title' => null,
    'back' => null,
    'hamburger' => true,
    'bell' => true,
])

<header class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-gray-200">
    <div class="mx-auto max-w-[480px] h-14 flex items-center gap-1 px-2">
        @if ($back)
            <a href="{{ $back }}"
               class="w-10 h-10 flex items-center justify-center rounded-full text-gray-700 active:bg-gray-100"
               aria-label="Indietro">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        @elseif ($hamburger)
            <button type="button"
                    @click="$store.drawer.show()"
                    class="w-10 h-10 flex items-center justify-center rounded-full text-gray-800 active:bg-gray-100"
                    aria-label="Menu">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>
        @endif

        <h1 class="flex-1 text-lg font-semibold text-gray-900 truncate px-1">
            {{ $title ?? $slot }}
        </h1>

        @isset($actions)
            <div class="flex items-center gap-1">{{ $actions }}</div>
        @endisset

        @if ($bell)
            <button type="button"
                    @click="$store.notifications.show()"
                    class="relative w-10 h-10 flex items-center justify-center rounded-full text-gray-800 active:bg-gray-100"
                    aria-label="Notifiche">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14V11a6 6 0 1 0-12 0v3a2 2 0 0 1-.6 1.6L4 17h5m6 0a3 3 0 1 1-6 0"/>
                </svg>
                <span x-show="$store.notifications.unreadCount > 0"
                      x-text="$store.notifications.unreadCount > 9 ? '9+' : $store.notifications.unreadCount"
                      class="absolute top-1 right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center border-2 border-white"></span>
            </button>
        @endif
    </div>
</header>
