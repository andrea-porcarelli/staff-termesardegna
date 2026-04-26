@extends('auth.manutentore._layout')

@section('title', 'Password')
@section('heading', 'Password')
@section('sub', 'Inserisci la tua password per accedere')

@section('step1', 'w-6 bg-brand-600')
@section('step2', 'w-8 bg-brand-600')

@section('form')
    <div class="flex items-center justify-center">
        <span class="inline-flex items-center gap-1.5 h-7 px-3 rounded-full bg-gray-100 text-gray-700 text-xs font-medium max-w-full truncate">
            <svg class="w-3.5 h-3.5 text-gray-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M22 6l-10 7L2 6m20 0v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6m20 0a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2"/>
            </svg>
            <span class="truncate">{{ $email }}</span>
        </span>
    </div>

    <form method="POST" action="{{ route('m.login.password') }}" class="space-y-4">
        @csrf
        {{-- usabile dal password manager --}}
        <input type="email" name="email" value="{{ $email }}" autocomplete="username" hidden>

        <div>
            <label for="password" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Password</label>
            <input type="password"
                   id="password" name="password"
                   autocomplete="current-password"
                   required autofocus
                   class="w-full h-12 px-3 rounded-xl border border-gray-300 bg-white text-base focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 @error('password') border-red-300 ring-1 ring-red-200 @enderror">
        </div>

        <label class="flex items-center gap-2 select-none cursor-pointer text-sm text-gray-600">
            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
            Ricordami su questo dispositivo
        </label>

        <button type="submit"
                class="w-full h-12 rounded-xl bg-brand-600 text-white font-semibold text-base inline-flex items-center justify-center gap-1.5 active:bg-brand-700 shadow-sm">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>
            </svg>
            Accedi
        </button>
    </form>
@endsection

@section('footer')
    <a href="{{ route('m.login') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m0 0 7 7m-7-7 7-7"/>
        </svg>
        Cambia email
    </a>
@endsection
