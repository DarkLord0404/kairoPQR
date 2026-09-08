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

<nav x-data="{ open: false }" class="kairo-navbar">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 shrink-0 mr-4">
                    <img src="{{ asset('kairo.png') }}" alt="Kairo" class="w-9 h-9 kairo-avatar-ring">
                    <span class="font-bold text-sm tracking-wide" style="color: var(--kairo-text)">KAIRO</span>
                </a>

                {{-- Desktop nav: dropdown headers --}}
                <div class="hidden sm:flex items-center gap-1">

                    @if (auth()->user()->tieneAccesoPqr())
                        @if (auth()->user()->isMaster())
                        {{-- PQR split-button --}}
                        <div x-data="{ pqr: false }" @click.outside="pqr = false" class="relative">
                            @php $pqrActivo = request()->routeIs('dashboard') || (request()->routeIs('historial*') && !request()->routeIs('ea.*')); @endphp
                            <div class="flex items-stretch rounded-md overflow-hidden"
                                 style="border: 1px solid {{ $pqrActivo ? 'rgba(99,179,255,0.5)' : 'var(--kairo-border)' }}; height:34px;">
                                <a href="{{ route('dashboard') }}" wire:navigate
                                   class="flex items-center px-3 text-sm font-medium transition-colors"
                                   style="color:{{ $pqrActivo ? 'var(--kairo-blue-dim)' : 'var(--kairo-text-dim)' }}; background:{{ $pqrActivo ? 'rgba(59,130,246,0.1)' : 'transparent' }}">
                                    PQR
                                </a>
                                <button @click="pqr = !pqr"
                                    class="flex items-center px-1.5 transition-colors"
                                    style="border-left: 1px solid {{ $pqrActivo ? 'rgba(99,179,255,0.3)' : 'var(--kairo-border)' }}; color:{{ $pqrActivo ? 'var(--kairo-blue-dim)' : 'var(--kairo-text-dim)' }}; background:{{ $pqrActivo ? 'rgba(59,130,246,0.1)' : 'transparent' }}">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                </button>
                                <div x-show="pqr" x-cloak x-transition
                                    class="absolute top-full left-0 mt-1 w-36 rounded-lg shadow-xl z-50 py-1"
                                    style="background: var(--kairo-bg-2); border: 1px solid var(--kairo-border)">
                                    <a href="{{ route('historial') }}" wire:navigate @click="pqr=false"
                                       class="block px-4 py-2 text-sm kairo-navlink {{ request()->routeIs('historial*') && !request()->routeIs('ea.*') ? 'kairo-navlink-active' : '' }}">
                                        Historial PQR
                                    </a>
                                </div>
                            </div>
                        </div>
                        @else
                        <a href="{{ route('dashboard') }}" wire:navigate
                           class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md kairo-navlink {{ request()->routeIs('dashboard') ? 'kairo-navlink-active' : '' }}">
                            PQR
                        </a>
                        @endif
                    @endif

                    @if (auth()->user()->tieneAccesoEa())
                        @if (auth()->user()->isMaster())
                        {{-- EA split-button --}}
                        <div x-data="{ ea: false }" @click.outside="ea = false" class="relative ml-1">
                            @php $eaActivo = request()->routeIs('ea.*'); @endphp
                            <div class="flex items-stretch rounded-md overflow-hidden"
                                 style="border: 1px solid {{ $eaActivo ? 'rgba(99,179,255,0.5)' : 'var(--kairo-border)' }}; height:34px;">
                                <a href="{{ route('ea.analyzer') }}" wire:navigate
                                   class="flex items-center px-3 text-sm font-medium transition-colors"
                                   style="color:{{ $eaActivo ? 'var(--kairo-blue-dim)' : 'var(--kairo-text-dim)' }}; background:{{ $eaActivo ? 'rgba(59,130,246,0.1)' : 'transparent' }}">
                                    EA
                                </a>
                                <button @click="ea = !ea"
                                    class="flex items-center px-1.5 transition-colors"
                                    style="border-left: 1px solid {{ $eaActivo ? 'rgba(99,179,255,0.3)' : 'var(--kairo-border)' }}; color:{{ $eaActivo ? 'var(--kairo-blue-dim)' : 'var(--kairo-text-dim)' }}; background:{{ $eaActivo ? 'rgba(59,130,246,0.1)' : 'transparent' }}">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                </button>
                                <div x-show="ea" x-cloak x-transition
                                    class="absolute top-full left-0 mt-1 w-36 rounded-lg shadow-xl z-50 py-1"
                                    style="background: var(--kairo-bg-2); border: 1px solid var(--kairo-border)">
                                    <a href="{{ route('ea.historial') }}" wire:navigate @click="ea=false"
                                       class="block px-4 py-2 text-sm kairo-navlink {{ request()->routeIs('ea.historial*') ? 'kairo-navlink-active' : '' }}">
                                        Historial EA
                                    </a>
                                </div>
                            </div>
                        </div>
                        @else
                        <a href="{{ route('ea.analyzer') }}" wire:navigate
                           class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md kairo-navlink ml-1 {{ request()->routeIs('ea.*') ? 'kairo-navlink-active' : '' }}">
                            EA
                        </a>
                        @endif
                    @endif

                    @if (auth()->user()->tieneAccesoReuniones())
                        @if (auth()->user()->isMaster())
                        {{-- Meet split-button --}}
                        <div x-data="{ meet: false }" @click.outside="meet = false" class="relative ml-1">
                            @php $meetActivo = request()->routeIs('reuniones*'); @endphp
                            <div class="flex items-stretch rounded-md overflow-hidden"
                                 style="border: 1px solid {{ $meetActivo ? 'rgba(99,179,255,0.5)' : 'var(--kairo-border)' }}; height:34px;">
                                <a href="{{ route('reuniones') }}" wire:navigate
                                   class="flex items-center px-3 text-sm font-medium transition-colors"
                                   style="color:{{ $meetActivo ? 'var(--kairo-blue-dim)' : 'var(--kairo-text-dim)' }}; background:{{ $meetActivo ? 'rgba(59,130,246,0.1)' : 'transparent' }}">
                                    Meet
                                </a>
                                <button @click="meet = !meet"
                                    class="flex items-center px-1.5 transition-colors"
                                    style="border-left: 1px solid {{ $meetActivo ? 'rgba(99,179,255,0.3)' : 'var(--kairo-border)' }}; color:{{ $meetActivo ? 'var(--kairo-blue-dim)' : 'var(--kairo-text-dim)' }}; background:{{ $meetActivo ? 'rgba(59,130,246,0.1)' : 'transparent' }}">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                </button>
                                <div x-show="meet" x-cloak x-transition
                                    class="absolute top-full left-0 mt-1 w-36 rounded-lg shadow-xl z-50 py-1"
                                    style="background: var(--kairo-bg-2); border: 1px solid var(--kairo-border)">
                                    <a href="{{ route('reuniones.cuentas') }}" wire:navigate @click="meet=false"
                                       class="block px-4 py-2 text-sm kairo-navlink {{ request()->routeIs('reuniones.cuentas') ? 'kairo-navlink-active' : '' }}">
                                        Calendarios
                                    </a>
                                    <a href="{{ route('reuniones.conectar') }}" wire:navigate @click="meet=false"
                                       class="block px-4 py-2 text-sm kairo-navlink {{ request()->routeIs('reuniones.conectar') ? 'kairo-navlink-active' : '' }}">
                                        Conectar manualmente
                                    </a>
                                </div>
                            </div>
                        </div>
                        @else
                        <a href="{{ route('reuniones') }}" wire:navigate
                           class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md kairo-navlink ml-1 {{ request()->routeIs('reuniones*') ? 'kairo-navlink-active' : '' }}">
                            Meet
                        </a>
                        @endif
                    @endif

                    @if (auth()->user()->isMaster())
                        {{-- Configuración dropdown (incluye Usuarios) --}}
                        <div x-data="{ cfg: false }" @click.outside="cfg = false" class="relative ml-1">
                            @php $cfgActivo = request()->routeIs('configuracion.*') || request()->routeIs('users'); @endphp
                            <button @click="cfg = !cfg"
                                class="flex items-center gap-1 px-3 text-sm font-medium rounded-md transition-colors"
                                style="height:34px; border: 1px solid {{ $cfgActivo ? 'rgba(99,179,255,0.5)' : 'var(--kairo-border)' }}; color:{{ $cfgActivo ? 'var(--kairo-blue-dim)' : 'var(--kairo-text-dim)' }}; background:{{ $cfgActivo ? 'rgba(59,130,246,0.1)' : 'transparent' }}">
                                Configuración
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                            <div x-show="cfg" x-cloak x-transition
                                class="absolute top-full left-0 mt-1 w-48 rounded-lg shadow-xl z-50 py-1"
                                style="background: var(--kairo-bg-2); border: 1px solid var(--kairo-border)">
                                <a href="{{ route('users') }}" wire:navigate @click="cfg=false"
                                   class="block px-4 py-2 text-sm kairo-navlink {{ request()->routeIs('users') ? 'kairo-navlink-active' : '' }}">
                                    Usuarios
                                </a>
                                <div style="height:1px;background:var(--kairo-border);margin:4px 0"></div>
                                <a href="{{ route('configuracion.prompts') }}" wire:navigate @click="cfg=false"
                                   class="block px-4 py-2 text-sm kairo-navlink {{ request()->routeIs('configuracion.prompts') ? 'kairo-navlink-active' : '' }}">
                                    Prompts del sistema
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3">
                {{-- Health status --}}
                <div class="hidden sm:flex items-center">
                    @livewire('health-status')
                </div>

                <div class="hidden sm:flex sm:items-center">
                    <x-dropdown align="right" width="48" contentClasses="py-1 kairo-dropdown-panel">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md transition ease-in-out duration-150" style="color: var(--kairo-text-dim)">
                                <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile')" wire:navigate>
                                Perfil
                            </x-dropdown-link>

                            @if (auth()->user()->isMaster())
                                <x-dropdown-link :href="route('acerca-de')" wire:navigate>
                                    Acerca de
                                </x-dropdown-link>
                            @endif

                            <button wire:click="logout" class="w-full text-start">
                                <x-dropdown-link>
                                    Cerrar sesión
                                </x-dropdown-link>
                            </button>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md transition duration-150 ease-in-out" style="color: var(--kairo-text-dim)">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden" style="border-top: 1px solid var(--kairo-border)">
        <div class="pt-2 pb-3 space-y-1">
            @if (auth()->user()->tieneAccesoPqr())
                <div class="px-4 pt-1 text-[10px] font-bold uppercase tracking-wider" style="color: var(--kairo-text-dim)">PQR</div>
                <a href="{{ route('dashboard') }}" wire:navigate class="block px-4 py-2 text-sm kairo-navlink {{ request()->routeIs('dashboard') ? 'kairo-navlink-active' : '' }}">
                    Analizar
                </a>
                @if (auth()->user()->isMaster())
                    <a href="{{ route('historial') }}" wire:navigate class="block px-4 py-2 text-sm kairo-navlink {{ request()->routeIs('historial*') && !request()->routeIs('ea.*') ? 'kairo-navlink-active' : '' }}">
                        Historial
                    </a>
                @endif
            @endif

            @if (auth()->user()->tieneAccesoEa())
                <div class="px-4 pt-3 text-[10px] font-bold uppercase tracking-wider" style="color: var(--kairo-text-dim)">EA</div>
                <a href="{{ route('ea.analyzer') }}" wire:navigate class="block px-4 py-2 text-sm kairo-navlink {{ request()->routeIs('ea.analyzer') ? 'kairo-navlink-active' : '' }}">
                    Analizar
                </a>
                @if (auth()->user()->isMaster())
                    <a href="{{ route('ea.historial') }}" wire:navigate class="block px-4 py-2 text-sm kairo-navlink {{ request()->routeIs('ea.historial*') ? 'kairo-navlink-active' : '' }}">
                        Historial
                    </a>
                @endif
            @endif

            @if (auth()->user()->tieneAccesoReuniones())
                <div class="px-4 pt-3 text-[10px] font-bold uppercase tracking-wider" style="color: var(--kairo-blue-dim)">MEET</div>
                <a href="{{ route('reuniones') }}" wire:navigate class="block px-4 py-2 text-sm kairo-navlink {{ request()->routeIs('reuniones') ? 'kairo-navlink-active' : '' }}">
                    Reuniones
                </a>
                @if (auth()->user()->isMaster())
                    <a href="{{ route('reuniones.cuentas') }}" wire:navigate class="block px-4 py-2 text-sm kairo-navlink {{ request()->routeIs('reuniones.cuentas') ? 'kairo-navlink-active' : '' }}">
                        Calendarios
                    </a>
                    <a href="{{ route('reuniones.conectar') }}" wire:navigate class="block px-4 py-2 text-sm kairo-navlink {{ request()->routeIs('reuniones.conectar') ? 'kairo-navlink-active' : '' }}">
                        Conectar manualmente
                    </a>
                @endif
            @endif

            @if (auth()->user()->isMaster())
                <div class="px-4 pt-3 text-[10px] font-bold uppercase tracking-wider" style="color: var(--kairo-text-dim)">Configuración</div>
                <a href="{{ route('users') }}" wire:navigate class="block px-4 py-2 text-sm kairo-navlink {{ request()->routeIs('users') ? 'kairo-navlink-active' : '' }}">
                    Usuarios
                </a>
                <a href="{{ route('configuracion.prompts') }}" wire:navigate class="block px-4 py-2 text-sm kairo-navlink {{ request()->routeIs('configuracion.*') ? 'kairo-navlink-active' : '' }}">
                    Prompts del sistema
                </a>
            @endif
        </div>

        <div class="pt-4 pb-1" style="border-top: 1px solid var(--kairo-border)">
            <div class="px-4">
                <div class="font-medium text-base" style="color: var(--kairo-text)" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm" style="color: var(--kairo-text-dim)">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    Perfil
                </x-responsive-nav-link>

                @if (auth()->user()->isMaster())
                    <x-responsive-nav-link :href="route('acerca-de')" wire:navigate>
                        Acerca de
                    </x-responsive-nav-link>
                @endif

                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        Cerrar sesión
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
