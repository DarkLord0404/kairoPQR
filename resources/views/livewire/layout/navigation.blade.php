<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    $u = auth()->user();
    $esMaster = $u->isMaster();

    $iconos = [
        'pqr' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 6.5V6a2 2 0 012-2h12a2 2 0 012 2v9a2 2 0 01-2 2H8.5L4 20.5z"/>',
        'ea' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 3.5h.01M10.3 3.9L2.4 17.5A2 2 0 004.1 20.5h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/>',
        'meet' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.36-1.86M17 20H7m10 0v-2c0-.65-.13-1.28-.36-1.86M7 20H2v-2a3 3 0 015.36-1.86M7 20v-2c0-.65.13-1.28.36-1.86m0 0a5 5 0 019.28 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'prompts' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.59a1 1 0 01.7.29l5.42 5.42a1 1 0 01.29.7V19a2 2 0 01-2 2z"/>',
        'consumo' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
        'drive' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>',
        'perfil' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5.12 17.8A13.94 13.94 0 0112 16c2.5 0 4.85.66 6.88 1.8M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'info' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'salir' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>',
    ];
    $svg = fn ($k) => '<svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">' . $iconos[$k] . '</svg>';

    $pqrActivo = request()->routeIs('dashboard') || request()->routeIs('historial*');
    $eaActivo = request()->routeIs('ea.*');
    $meetActivo = request()->routeIs('reuniones*');

    $titulos = [
        'dashboard' => ['Calidad', 'Análisis de PQR'],
        'historial' => ['Calidad', 'Historial de PQR'],
        'historial.detalle' => ['Calidad', 'Detalle de PQR'],
        'ea.analyzer' => ['Seguridad del paciente', 'Eventos adversos'],
        'ea.historial' => ['Seguridad del paciente', 'Historial de eventos adversos'],
        'ea.historial.detalle' => ['Seguridad del paciente', 'Detalle de evento adverso'],
        'reuniones' => ['Reuniones', 'Reuniones y actas'],
        'reuniones.detalle' => ['Reuniones', 'Detalle de reunión'],
        'reuniones.cuentas' => ['Reuniones', 'Calendarios conectados'],
        'reuniones.conectar' => ['Reuniones', 'Conectar a una reunión'],
        'reuniones.acerca-de' => ['Reuniones', 'Acerca de KairoMeet'],
        'users' => ['Administración', 'Usuarios'],
        'configuracion.prompts' => ['Administración', 'Prompts del sistema'],
        'configuracion.consumo' => ['Administración', 'Consumo de OpenClaw'],
        'configuracion.sepsis' => ['Administración', 'Sepsis · Google Drive'],
        'profile' => ['Cuenta', 'Perfil'],
        'acerca-de' => ['Clara', 'Acerca de'],
    ];
    [$eyebrow, $titulo] = $titulos[request()->route()?->getName()] ?? ['Clara', 'Calidad'];
@endphp

<div x-data="{ open: false }" @keydown.escape.window="open = false">

    {{-- Fondo oscuro para el menú móvil --}}
    <div x-show="open" x-cloak x-transition.opacity @click="open = false"
         class="fixed inset-0 z-30 bg-black/60 lg:hidden"></div>

    {{-- ============================ Barra lateral ============================ --}}
    <aside class="clara-sidebar" :class="{ 'is-open': open }">

        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 px-5 pt-5 pb-4">
            <img src="{{ asset('clara.webp') }}" alt="Clara" class="w-11 h-11 kairo-avatar-ring">
            <div class="leading-tight">
                <div class="clara-brand-name">CLARA<span class="clara-brand-dot"></span></div>
                <div class="clara-brand-sub">Calidad · Kairo</div>
            </div>
        </a>

        <nav class="flex-1 overflow-y-auto px-3 pb-4" style="border-top:1px solid var(--c-border)">

            @if ($u->tieneAccesoPqr() || $u->tieneAccesoEa())
                <div class="clara-nav-group">Calidad</div>

                @if ($u->tieneAccesoPqr())
                    <a href="{{ route('dashboard') }}" wire:navigate class="clara-nav-link {{ $pqrActivo ? 'is-active' : '' }}">
                        {!! $svg('pqr') !!} PQR
                    </a>
                    @if ($esMaster)
                        <div class="clara-nav-sub">
                            <a href="{{ route('dashboard') }}" wire:navigate class="clara-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">Analizar</a>
                            <a href="{{ route('historial') }}" wire:navigate class="clara-nav-link {{ request()->routeIs('historial*') ? 'is-active' : '' }}">Historial</a>
                        </div>
                    @endif
                @endif

                @if ($u->tieneAccesoEa())
                    <a href="{{ route('ea.analyzer') }}" wire:navigate class="clara-nav-link mt-1 {{ $eaActivo ? 'is-active' : '' }}">
                        {!! $svg('ea') !!} Eventos adversos
                    </a>
                    @if ($esMaster)
                        <div class="clara-nav-sub">
                            <a href="{{ route('ea.analyzer') }}" wire:navigate class="clara-nav-link {{ request()->routeIs('ea.analyzer') ? 'is-active' : '' }}">Analizar</a>
                            <a href="{{ route('ea.historial') }}" wire:navigate class="clara-nav-link {{ request()->routeIs('ea.historial*') ? 'is-active' : '' }}">Historial</a>
                        </div>
                    @endif
                @endif
            @endif

            @if ($u->tieneAccesoReuniones())
                <div class="clara-nav-group">Reuniones</div>
                <a href="{{ route('reuniones') }}" wire:navigate class="clara-nav-link {{ $meetActivo ? 'is-active' : '' }}">
                    {!! $svg('meet') !!} Reuniones y actas
                </a>
                @if ($esMaster)
                    <div class="clara-nav-sub">
                        <a href="{{ route('reuniones') }}" wire:navigate class="clara-nav-link {{ request()->routeIs('reuniones') || request()->routeIs('reuniones.detalle') ? 'is-active' : '' }}">Listado</a>
                        <a href="{{ route('reuniones.cuentas') }}" wire:navigate class="clara-nav-link {{ request()->routeIs('reuniones.cuentas') ? 'is-active' : '' }}">Calendarios</a>
                        <a href="{{ route('reuniones.conectar') }}" wire:navigate class="clara-nav-link {{ request()->routeIs('reuniones.conectar') ? 'is-active' : '' }}">Conectar manualmente</a>
                        <a href="{{ route('reuniones.acerca-de') }}" wire:navigate class="clara-nav-link {{ request()->routeIs('reuniones.acerca-de') ? 'is-active' : '' }}">Acerca de KairoMeet</a>
                    </div>
                @endif
            @endif

            @if ($esMaster)
                <div class="clara-nav-group">Administración</div>
                <a href="{{ route('users') }}" wire:navigate class="clara-nav-link {{ request()->routeIs('users') ? 'is-active' : '' }}">
                    {!! $svg('users') !!} Usuarios
                </a>
                <a href="{{ route('configuracion.prompts') }}" wire:navigate class="clara-nav-link {{ request()->routeIs('configuracion.prompts') ? 'is-active' : '' }}">
                    {!! $svg('prompts') !!} Prompts del sistema
                </a>
                <a href="{{ route('configuracion.consumo') }}" wire:navigate class="clara-nav-link {{ request()->routeIs('configuracion.consumo') ? 'is-active' : '' }}">
                    {!! $svg('consumo') !!} Consumo de OpenClaw
                </a>
                @if (Route::has('configuracion.sepsis'))
                    <a href="{{ route('configuracion.sepsis') }}" class="clara-nav-link {{ request()->routeIs('configuracion.sepsis') ? 'is-active' : '' }}">
                        {!! $svg('drive') !!} Sepsis · Google Drive
                    </a>
                @endif
            @endif
        </nav>

        {{-- Cuenta --}}
        <div class="px-3 py-3" style="border-top:1px solid var(--c-border)" x-data="{ cuenta: false }" @click.outside="cuenta = false">
            <div x-show="cuenta" x-cloak x-transition class="mb-2 py-1 rounded-lg" style="background:var(--c-surface-2);border:1px solid var(--c-border)">
                <a href="{{ route('profile') }}" wire:navigate class="clara-nav-link">{!! $svg('perfil') !!} Perfil</a>
                @if ($esMaster)
                    <a href="{{ route('acerca-de') }}" wire:navigate class="clara-nav-link">{!! $svg('info') !!} Acerca de</a>
                @endif
                <button wire:click="logout" class="clara-nav-link w-full text-start">{!! $svg('salir') !!} Cerrar sesión</button>
            </div>
            <button type="button" @click="cuenta = !cuenta" class="w-full flex items-center gap-3 px-2 py-2 rounded-lg transition-colors hover:bg-[var(--c-surface-3)]">
                <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                      style="background:var(--c-surface-3);border:1px solid var(--c-border-strong);color:var(--c-text-soft)">
                    {{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}
                </span>
                <span class="flex-1 min-w-0 text-left leading-tight">
                    <span class="block text-sm font-medium truncate" style="color:var(--c-text)"
                          x-data="{{ json_encode(['name' => $u->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></span>
                    <span class="block text-xs truncate" style="color:var(--c-text-dim)">{{ $u->email }}</span>
                </span>
                <svg class="w-4 h-4 flex-shrink-0" style="color:var(--c-text-dim)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/></svg>
            </button>
        </div>
    </aside>

    {{-- ============================ Barra superior ============================ --}}
    <header class="clara-topbar flex items-center gap-3 px-4 sm:px-6 lg:px-8">
        <button @click="open = !open" class="lg:hidden -ml-1 p-2 rounded-md" style="color:var(--c-text-dim)" aria-label="Abrir menú">
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="min-w-0 flex-1 leading-tight">
            <div class="clara-page-eyebrow">{{ $eyebrow }}</div>
            <div class="font-display text-[1.05rem] font-semibold truncate" style="color:var(--c-text)">{{ $titulo }}</div>
        </div>
        <div class="flex items-center">
            @livewire('health-status')
        </div>
    </header>
</div>
