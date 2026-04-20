@extends('layouts.manutentore')

@section('title', $title)

@section('content')
    <div class="px-4 py-10 flex flex-col items-center text-center">
        <div class="w-16 h-16 rounded-full bg-brand-50 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 8v4l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
            </svg>
        </div>
        <h2 class="text-lg font-semibold text-gray-900 mb-1">{{ $title }}</h2>
        <p class="text-gray-500 text-sm max-w-[280px]">{{ $message }}</p>
        <x-m.btn :href="route('m.home')" variant="ghost" class="mt-6">Torna alla Home</x-m.btn>
    </div>
@endsection
