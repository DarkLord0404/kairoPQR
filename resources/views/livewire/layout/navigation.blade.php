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
    $ruta = request()->route()?->getName();

    // Módulos principales (según permisos vigentes; no se cambian rutas ni middleware)
    $modulos = [];
    if ($u->tieneAccesoPqr()) {
        $modulos['pqr'] = ['PQR', route('dashboard'), request()->routeIs('dashboard') || request()->routeIs('historial*'),
            array_filter([
                ['Analizar', route('dashboard'), request()->routeIs('dashboard')],
                $esMaster ? ['Historial', route('historial'), request()->routeIs('historial*')] : null,
            ])];
    }
    if ($u->tieneAccesoEa()) {
        $modulos['ea'] = ['Eventos adversos', route('ea.analyzer'), request()->routeIs('ea.*'),
            array_filter([
                ['Analizar', route('ea.analyzer'), request()->routeIs('ea.analyzer')],
                $esMaster ? ['Historial', route('ea.historial'), request()->routeIs('ea.historial*')] : null,
            ])];
    }
    if ($u->tieneAccesoReuniones()) {
        $modulos['meet'] = ['Reuniones', route('reuniones'), request()->routeIs('reuniones*'),
            array_filter([
                ['Reuniones y actas', route('reuniones'), request()->routeIs('reuniones') || request()->routeIs('reuniones.detalle')],
                $esMaster ? ['Calendarios', route('reuniones.cuentas'), request()->routeIs('reuniones.cuentas')] : null,
                $esMaster ? ['Conectar manualmente', route('reuniones.conectar'), request()->routeIs('reuniones.conectar')] : null,
                $esMaster ? ['Acerca de KairoMeet', route('reuniones.acerca-de'), request()->routeIs('reuniones.acerca-de')] : null,
            ])];
    }
    if ($esMaster) {
        $modulos['admin'] = ['Administración', route('users'), request()->routeIs('users') || request()->routeIs('configuracion.*'),
            array_filter([
                ['Usuarios', route('users'), request()->routeIs('users')],
                ['Prompts del sistema', route('configuracion.prompts'), request()->routeIs('configuracion.prompts')],
                ['Consumo de OpenClaw', route('configuracion.consumo'), request()->routeIs('configuracion.consumo')],
                Route::has('configuracion.sepsis') ? ['Sepsis · Google Drive', route('configuracion.sepsis'), request()->routeIs('configuracion.sepsis')] : null,
            ])];
    }
    $activo = collect($modulos)->first(fn ($m) => $m[2]);
    $subnav = $activo[3] ?? [];

    // Título de página para las vistas que no tienen encabezado propio
    $titulos = [
        'historial' => ['Calidad · PQR', 'Historial de PQR'],
        'ea.historial' => ['Seguridad del paciente', 'Historial de eventos adversos'],
        'reuniones' => ['Reuniones', 'Reuniones y actas'],
        'reuniones.cuentas' => ['Reuniones', 'Calendarios conectados'],
        'reuniones.conectar' => ['Reuniones', 'Conectar a una reunión'],
        'users' => ['Administración', 'Usuarios'],
        'configuracion.prompts' => ['Administración', 'Prompts del sistema'],
        'profile' => ['Cuenta', 'Perfil'],
        'acerca-de' => ['Clara', 'Acerca de'],
    ];
    $encabezado = $titulos[$ruta] ?? null;
@endphp

<div x-data="{ open: false }" @keydown.escape.window="open = false">
    <header class="c-header">
        <div class="c-container c-header-row">
            <a href="{{ route('dashboard') }}" wire:navigate class="c-brand">
                <img src="{{ asset('clara.webp') }}" alt="Clara">
                <span>
                    <span class="c-brand-name block">Clara<span class="c-brand-dot"></span></span>
                    <span class="c-brand-sub block">Calidad · Kairo</span>
                </span>
            </a>

            <nav class="c-mainnav hidden md:flex">
                @foreach ($modulos as [$nombre, $url, $esActivo])
                    <a href="{{ $url }}" wire:navigate class="{{ $esActivo ? 'is-active' : '' }}">{{ $nombre }}</a>
                @endforeach
            </nav>

            <div class="ml-auto flex items-center gap-3">
                @livewire('health-status')

                <div class="relative hidden md:block" x-data="{ cuenta: false }" @click.outside="cuenta = false">
                    <button type="button" @click="cuenta = !cuenta" class="c-avatar-btn" title="{{ $u->name }}">
                        {{ collect(explode(' ', $u->name))->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') }}
                    </button>
                    <div x-show="cuenta" x-cloak x-transition class="c-menu absolute right-0 mt-2 w-60 z-50">
                        <div class="px-3 py-2 mb-1" style="border-bottom:1px solid var(--c-border)">
                            <div class="text-sm font-bold" style="color:var(--c-text)" x-data="{{ json_encode(['name' => $u->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                            <div class="text-xs truncate" style="color:var(--c-text-dim)">{{ $u->email }}</div>
                        </div>
                        <a href="{{ route('profile') }}" wire:navigate>Perfil</a>
                        @if ($esMaster)
                            <a href="{{ route('acerca-de') }}" wire:navigate>Acerca de</a>
                        @endif
                        <button wire:click="logout">Cerrar sesión</button>
                    </div>
                </div>

                <button @click="open = !open" class="md:hidden p-2 -mr-2 rounded-md" style="color:var(--c-text-dim)" aria-label="Abrir menú">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>

        {{-- Menú móvil --}}
        <div x-show="open" x-cloak x-transition class="md:hidden" style="border-top:1px solid var(--c-border)">
            <div class="c-container py-3 space-y-3">
                @foreach ($modulos as [$nombre, $url, $esActivo, $items])
                    <div>
                        <div class="c-eyebrow px-2 mb-1" style="color:var(--c-text-dim)">{{ $nombre }}</div>
                        @foreach ($items as [$txt, $href, $on])
                            <a href="{{ $href }}" wire:navigate class="block px-2 py-2 text-sm kairo-navlink {{ $on ? 'kairo-navlink-active' : '' }}">{{ $txt }}</a>
                        @endforeach
                    </div>
                @endforeach
                <div class="pt-2" style="border-top:1px solid var(--c-border)">
                    <a href="{{ route('profile') }}" wire:navigate class="block px-2 py-2 text-sm kairo-navlink">Perfil</a>
                    @if ($esMaster)
                        <a href="{{ route('acerca-de') }}" wire:navigate class="block px-2 py-2 text-sm kairo-navlink">Acerca de</a>
                    @endif
                    <button wire:click="logout" class="block w-full text-left px-2 py-2 text-sm kairo-navlink">Cerrar sesión</button>
                </div>
            </div>
        </div>
    </header>

    @if (count($subnav) > 1)
        <div class="c-subbar hidden md:block">
            <nav class="c-container c-subnav">
                @foreach ($subnav as [$txt, $href, $on])
                    <a href="{{ $href }}" wire:navigate class="{{ $on ? 'is-active' : '' }}">{{ $txt }}</a>
                @endforeach
            </nav>
        </div>
    @endif

    @if ($encabezado)
        <div class="c-container c-pagehead">
            <div class="c-eyebrow">{{ $encabezado[0] }}</div>
            <h1 class="c-title mt-1">{{ $encabezado[1] }}</h1>
        </div>
    @endif
</div>
