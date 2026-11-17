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
                $esMaster ? ['Conectar manualmente', route('reuniones.conectar'), request()->routeIs('reuniones.conectar')] : null,
                $esMaster ? ['Acerca de KairoMeet', route('reuniones.acerca-de'), request()->routeIs('reuniones.acerca-de')] : null,
            ])];
    }
    if ($esMaster) {
        $modulos['admin'] = ['Administración', route('configuracion.prompts'), request()->routeIs('configuracion.*') || request()->routeIs('acerca-de'),
            array_filter([
                ['Prompts del sistema', route('configuracion.prompts'), request()->routeIs('configuracion.prompts')],
                ['Consumo de OpenClaw', route('configuracion.consumo'), request()->routeIs('configuracion.consumo')],
                Route::has('configuracion.sepsis') ? ['Guías de práctica · Sepsis', route('configuracion.sepsis'), request()->routeIs('configuracion.sepsis')] : null,
                ['Acerca de Clara', route('acerca-de'), request()->routeIs('acerca-de')],
            ])];
    }
    $activo = collect($modulos)->first(fn ($m) => $m[2]);

    // Barra común de los agentes: retratos de las apps a las que el usuario tiene acceso
    $appsDef = [
        'kairo' => ['Kairo', 'https://kairo.koqoi.com/', '#3b9eff'],
        'clara' => ['Clara', 'https://clara.koqoi.com/dashboard', '#ff3fa4'],
        'iris' => ['Iris', 'https://iris.koqoi.com/', '#ff8a2a'],
        'mateo' => ['Mateo', 'https://mateo.koqoi.com/', '#22e08a'],
        'tomas' => ['Tomás', 'https://tomas.koqoi.com/', '#a78bfa'],
        'minerva' => ['Minerva', 'https://kairo.koqoi.com/minerva', '#b06cff'],
        'vera' => ['Vera', 'https://kairo.koqoi.com/vera', '#2ee6d6'],
    ];
    try {
        $misApps = $esMaster ? array_keys($appsDef)
            : \Illuminate\Support\Facades\DB::table('kairo_app_access')->where('user_id', $u->id)->pluck('app')->all();
    } catch (\Throwable $e) {
        $misApps = ['clara'];
    }
    $misApps = array_values(array_intersect(array_keys($appsDef), array_unique([...$misApps, 'clara'])));
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
        'configuracion.sepsis' => ['Guías de práctica clínica', 'Sepsis · conexión con Google Drive'],
    ];
    $encabezado = $titulos[$ruta] ?? null;
@endphp

<div x-data="{ open: false }" @keydown.escape.window="open = false" style="display: contents">
  <div class="c-topwrap">
    <header class="kx-bar">
        <a class="kx-logo" href="{{ route('dashboard') }}" wire:navigate>
            <img src="https://kairo.koqoi.com/img/clara-sm.webp" alt="">CLARA
        </a>
        <nav class="kx-nav">
            @foreach ($modulos as [$nombre, $url, $esActivo])
                <a href="{{ $url }}" wire:navigate class="{{ $esActivo ? 'on' : '' }}">{{ $nombre }}</a>
            @endforeach
        </nav>
        <div class="kx-right">
            @livewire('health-status')
            @if (count($misApps) > 1)
                <div class="kx-apps">
                    @foreach ($misApps as $id)
                        <a href="{{ $appsDef[$id][1] }}" title="{{ $appsDef[$id][0] }}" style="--c: {{ $appsDef[$id][2] }}" class="{{ $id === 'clara' ? 'on' : '' }}">
                            <img src="https://kairo.koqoi.com/img/{{ $id }}-sm.webp" alt="{{ $appsDef[$id][0] }}">
                        </a>
                    @endforeach
                </div>
            @endif
            <span class="kx-user" x-data="{{ json_encode(['name' => $u->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name">{{ $u->name }}</span>
            <a class="kx-out" href="https://kairo.koqoi.com/logout">Salir</a>
        </div>
    </header>

    @if (count($subnav) > 1)
        <div class="c-subbar">
            <nav class="c-container c-subnav">
                @foreach ($subnav as [$txt, $href, $on])
                    <a href="{{ $href }}" wire:navigate class="{{ $on ? 'is-active' : '' }}">{{ $txt }}</a>
                @endforeach
            </nav>
        </div>
    @endif
  </div>

    @if ($encabezado)
        <div class="c-container c-pagehead">
            <div class="c-eyebrow">{{ $encabezado[0] }}</div>
            <h1 class="c-title mt-1">{{ $encabezado[1] }}</h1>
        </div>
    @endif
</div>
