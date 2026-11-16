<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0b0b0f">

        <title>Clara · Kairo</title>
        <link rel="icon" type="image/png" href="{{ asset('clara.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('clara.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|space-grotesk:500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased kairo-theme">
        <div class="kairo-shell min-h-screen flex flex-col">
            <div class="flex flex-col justify-center items-center px-4 py-10 flex-1">
                <a href="/" wire:navigate class="flex flex-col items-center text-center">
                    <img src="{{ asset('clara.webp') }}" alt="Clara" class="w-20 h-20 kairo-avatar-ring">
                    <div class="clara-brand-name mt-4 text-base">CLARA<span class="clara-brand-dot"></span></div>
                    <div class="clara-brand-sub mt-1">Subagente de calidad · Kairo</div>
                </a>

                <div class="w-full sm:max-w-md mt-7 px-6 py-6 kairo-panel" style="color: var(--c-text)">
                    {{ $slot }}
                </div>
            </div>

            <footer class="py-4 text-center text-xs" style="color: var(--c-text-faint)">
                Alexander Torres · 2026
            </footer>
        </div>
    </body>
</html>
