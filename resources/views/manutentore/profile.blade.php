@extends('layouts.manutentore')

@section('title', 'Profilo')

@section('content')
    <div class="px-4 py-6 space-y-4">
        <x-m.card>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-semibold text-lg">
                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-gray-900 truncate">{{ $user->name }}</div>
                    <div class="text-sm text-gray-500 truncate">{{ $user->email }}</div>
                </div>
                <x-m.badge variant="brand">{{ ucfirst($user->role) }}</x-m.badge>
            </div>
        </x-m.card>

        @if ($user->maintenanceRoles->isNotEmpty())
            <x-m.card>
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Specializzazioni</div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($user->maintenanceRoles as $mr)
                        <x-m.badge variant="gray">{{ $mr->name }}</x-m.badge>
                    @endforeach
                </div>
            </x-m.card>
        @endif

        @if ($user->departments->isNotEmpty())
            <x-m.card>
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Zone assegnate</div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($user->departments as $d)
                        <x-m.badge variant="gray">{{ $d->name }}</x-m.badge>
                    @endforeach
                </div>
            </x-m.card>
        @endif

        <form method="POST" action="{{ route('logout') }}" class="pt-2">
            @csrf
            <x-m.btn type="submit" variant="outline" :block="true">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 12H3m0 0 4-4m-4 4 4 4M9 4h9a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H9"/>
                </svg>
                Esci
            </x-m.btn>
        </form>
    </div>
@endsection
