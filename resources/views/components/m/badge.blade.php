@props([
    'variant' => 'gray',
])

@php
    $variants = [
        'gray'    => 'bg-gray-100 text-gray-700',
        'brand'   => 'bg-brand-50 text-brand-700',
        'success' => 'bg-green-100 text-green-700',
        'warn'    => 'bg-amber-100 text-amber-800',
        'danger'  => 'bg-red-100 text-red-700',
        'urgent'  => 'bg-red-600 text-white',
        'high'    => 'bg-orange-500 text-white',
        'medium'  => 'bg-amber-400 text-amber-900',
        'low'     => 'bg-sky-100 text-sky-700',
        'fixed'   => 'bg-violet-100 text-violet-700',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 h-6 rounded-md text-xs font-semibold ' . ($variants[$variant] ?? $variants['gray'])]) }}>
    {{ $slot }}
</span>
