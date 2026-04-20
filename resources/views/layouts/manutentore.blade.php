<!DOCTYPE html>
<html lang="it" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Manutenzione') · Rapportini</title>

    @vite(['resources/css/manutentore.css', 'resources/js/manutentore.js'])
    @stack('head')
</head>
<body x-data class="h-full bg-gray-50 font-sans text-gray-900 antialiased">

    <div class="min-h-full mx-auto max-w-[480px] flex flex-col">
        @isset($appBar)
            {{ $appBar }}
        @else
            <x-m.app-bar :title="View::getSection('title', 'Manutenzione')" />
        @endisset

        <main class="flex-1 pb-24">
            @yield('content')
        </main>
    </div>

    <x-m.bottom-nav />
    <x-m.drawer />
    <x-m.ticket-modal />
    <x-m.report-modal />
    <x-m.notification-sheet />
    <x-m.toast />

    @php
        $mUnreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
    @endphp
    <script>
        (function () {
            const initial = {{ $mUnreadCount }};
            const seed = () => window.Alpine?.store?.('notifications')?.setInitialCount(initial);
            if (window.Alpine && window.Alpine.store) { seed(); }
            else { document.addEventListener('alpine:initialized', seed, { once: true }); }
        })();
    </script>

    @stack('scripts')
</body>
</html>
