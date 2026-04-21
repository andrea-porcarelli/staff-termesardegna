@php
    $active = request()->route()?->getName() ?? '';
    $tabs = [
        ['name' => 'm.home',           'label' => 'Dashboard', 'icon' => 'home',   'match' => ['m.home']],
        ['name' => 'm.schedule',       'label' => 'Piano',   'icon' => 'clock',  'match' => ['m.schedule']],
        ['name' => 'm.profile',        'label' => 'Profilo', 'icon' => 'user',   'match' => ['m.profile']],
    ];

    $icons = [
        'home'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/>',
        'clock'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>',
        'user'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 11a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM4 21a8 8 0 0 1 16 0"/>',
    ];
@endphp

<nav class="fixed bottom-0 inset-x-0 z-30 bg-white border-t border-gray-200 pb-[env(safe-area-inset-bottom)]">
    <div class="mx-auto max-w-[480px] grid grid-cols-3 h-16">
        @foreach ($tabs as $tab)
            @php $isActive = in_array($active, $tab['match']); @endphp
            <a href="{{ route($tab['name']) }}"
               class="flex flex-col items-center justify-center gap-0.5 text-[11px] font-medium transition-colors {{ $isActive ? 'text-brand-600' : 'text-gray-500 active:text-gray-800' }}">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $isActive ? '2.2' : '1.8' }}">
                    {!! $icons[$tab['icon']] !!}
                </svg>
                <span>{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
