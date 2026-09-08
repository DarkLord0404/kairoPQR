<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Kairo') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('kairo.png') }}">
        <link rel="shortcut icon" type="image/png" href="{{ asset('kairo.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('kairo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased kairo-theme">
        <div class="kairo-shell min-h-screen flex flex-col">
            <div class="flex flex-col sm:justify-center items-center pt-6 sm:pt-0 flex-1">
                <div>
                    <a href="/" wire:navigate>
                        <img src="{{ asset('kairo.png') }}" alt="Kairo" class="w-20 h-20 kairo-avatar-ring object-cover">
                    </a>
                </div>

                <div class="w-full sm:max-w-md mt-6 px-6 py-4 kairo-panel overflow-hidden sm:rounded-lg" style="color: var(--kairo-text)">
                    {{ $slot }}
                </div>
            </div>

            <footer class="py-4 text-center text-xs" style="color: var(--kairo-text-dim)">
                Alexander Torres | 2026
            </footer>
        </div>
    </body>
</html>
