<!DOCTYPE html>
<html lang="it" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#dc2626">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16.png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">

    <title>@yield('title', 'Accesso') · SGHT Staff</title>

    @vite(['resources/css/manutentore.css', 'resources/js/manutentore.js'])
</head>
<body class="h-full bg-gray-100 font-sans text-gray-900 antialiased">

    <div class="min-h-full mx-auto max-w-[480px] px-4 py-8 flex flex-col justify-center">

        {{-- Header brand --}}
        <header class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-brand-600 text-white shadow-lg shadow-brand-600/25 mb-4">
                <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12h6m-6 4h4m2-10H7a2 2 0 0 0-2 2v14l3-3h9a2 2 0 0 0 2-2V8l-6-6zM11 2v4m2-4v4"/>
                </svg>
            </div>
            <div class="text-[11px] uppercase tracking-[0.18em] text-gray-500">SGHT Staff</div>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">@yield('heading', 'Accesso')</h1>
            @hasSection('sub')
                <p class="text-sm text-gray-500 mt-1">@yield('sub')</p>
            @endif
        </header>

        {{-- Step indicator --}}
        <div class="flex items-center justify-center gap-1.5 mb-4">
            <span class="h-1.5 rounded-full transition-all @yield('step1', 'w-7 bg-brand-600')"></span>
            <span class="h-1.5 rounded-full transition-all @yield('step2', 'w-5 bg-gray-300')"></span>
        </div>

        {{-- Card form --}}
        <div class="bg-white rounded-3xl border border-gray-200 shadow-xl shadow-gray-900/5 p-6 space-y-4">

            @if (session('success'))
                <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-3 py-2 flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12l5 5L20 7"/>
                    </svg>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2 flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    </svg>
                    <div class="space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            @yield('form')
        </div>

        @hasSection('footer')
            <div class="mt-5 text-center">@yield('footer')</div>
        @endif

    </div>

</body>
</html>
