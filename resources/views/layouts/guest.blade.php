<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#ffffff">

        <title>Clara · Kairo</title>
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}?v=4">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=4">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=4">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased kairo-theme">
        <x-clara-bg />
        <div class="kairo-shell min-h-screen flex flex-col">
            <div class="flex flex-col justify-center items-center px-4 py-10 flex-1">
                <a href="/" wire:navigate class="flex flex-col items-center text-center">
                    <span class="c-clara" style="width:72px;height:72px"><img src="{{ asset('clara.webp') }}" alt="Clara"></span>
                    <div class="c-brand-name mt-3 text-lg">Clara<span class="c-brand-dot"></span></div>
                    <div class="c-brand-sub mt-0.5">Calidad · Kairo</div>
                </a>

                <div class="w-full sm:max-w-md mt-7 px-7 py-7 kairo-panel">
                    {{ $slot }}
                </div>
            </div>

            <footer class="py-5 text-center text-xs" style="color: var(--c-text-faint)">
                Alexander Torres · 2026
            </footer>
        </div>
    </body>
</html>
