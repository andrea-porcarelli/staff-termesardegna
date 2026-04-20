@props([
    'as' => 'div',
    'href' => null,
    'padding' => 'p-4',
])

@php
    $tag = $href ? 'a' : $as;
    $base = 'block bg-white rounded-2xl border border-gray-200 shadow-sm';
    $interactive = $href ? ' active:bg-gray-50 transition-colors' : '';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => trim($base . ' ' . $padding . $interactive)]) }}>
    {{ $slot }}
</{{ $tag }}>
