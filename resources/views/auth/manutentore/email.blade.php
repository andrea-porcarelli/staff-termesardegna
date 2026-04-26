@extends('auth.manutentore._layout')

@section('title', 'Accesso')
@section('heading', 'Accedi')
@section('sub', 'Inserisci la tua email per continuare')

@section('step1', 'w-8 bg-brand-600')
@section('step2', 'w-6 bg-gray-200')

@section('form')
    <form method="POST" action="{{ route('m.login') }}" autocomplete="on" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Email</label>
            <input type="email"
                   id="email" name="email"
                   value="{{ old('email') }}"
                   placeholder="nome@esempio.com"
                   autocomplete="username"
                   inputmode="email"
                   required autofocus
                   class="w-full h-12 px-3 rounded-xl border border-gray-300 bg-white text-base focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 @error('email') border-red-300 ring-1 ring-red-200 @enderror">
        </div>

        <button type="submit"
                class="w-full h-12 rounded-xl bg-brand-600 text-white font-semibold text-base inline-flex items-center justify-center gap-1.5 active:bg-brand-700 shadow-sm">
            Avanti
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0-5-5m5 5-5 5"/>
            </svg>
        </button>
    </form>
@endsection
