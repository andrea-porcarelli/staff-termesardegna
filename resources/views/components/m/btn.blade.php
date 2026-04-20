@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'block' => false,
])

@php
    $variants = [
        'primary'   => 'bg-brand-600 text-white active:bg-brand-700 shadow-sm',
        'secondary' => 'bg-gray-100 text-gray-900 active:bg-gray-200',
        'danger'    => 'bg-red-600 text-white active:bg-red-700 shadow-sm',
        'ghost'     => 'bg-transparent text-gray-700 active:bg-gray-100',
        'outline'   => 'bg-white text-gray-900 border border-gray-300 active:bg-gray-50',
    ];
    $sizes = [
        'sm' => 'h-9 px-3 text-sm',
        'md' => 'h-11 px-4 text-[15px]',
        'lg' => 'h-12 px-5 text-base',
    ];
    $classes = collect([
        'inline-flex items-center justify-center gap-2 font-semibold rounded-xl select-none transition-colors disabled:opacity-50 disabled:pointer-events-none',
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
        $block ? 'w-full' : '',
    ])->filter()->implode(' ');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
