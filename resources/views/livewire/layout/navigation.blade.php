<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="kairo-navbar">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-8">
                <!-- Logo -->
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 shrink-0">
                    <img src="{{ asset('kairo.png') }}" alt="Kairo" class="w-9 h-9 kairo-avatar-ring">
                    <span class="font-bold text-sm tracking-wide" style="color: var(--kairo-text)">KAIRO PQR</span>
                </a>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 sm:flex">
                    <a href="{{ route('dashboard') }}" wire:navigate
                       class="inline-flex items-center px-1 pt-1 text-sm kairo-navlink {{ request()->routeIs('dashboard') ? 'kairo-navlink-active' : '' }}">
                        {{ __('Analizar') }}
                    </a>

                    <a href="{{ route('historial') }}" wire:navigate
                       class="inline-flex items-center px-1 pt-1 text-sm kairo-navlink {{ request()->routeIs('historial') ? 'kairo-navlink-active' : '' }}">
                        {{ __('Historial') }}
                    </a>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="kairo-badge-online hidden sm:flex">
                    <div class="kairo-dot"></div>
                    <span>Kairo activo</span>
                </div>

                <!-- Settings Dropdown -->
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
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <button wire:click="logout" class="w-full text-start">
                                <x-dropdown-link>
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </button>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            <!-- Hamburger -->
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

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden" style="border-top: 1px solid var(--kairo-border)">
        <div class="pt-2 pb-3 space-y-1">
            <a href="{{ route('dashboard') }}" wire:navigate class="block px-4 py-2 text-sm kairo-navlink {{ request()->routeIs('dashboard') ? 'kairo-navlink-active' : '' }}">
                {{ __('Analizar') }}
            </a>
            <a href="{{ route('historial') }}" wire:navigate class="block px-4 py-2 text-sm kairo-navlink {{ request()->routeIs('historial') ? 'kairo-navlink-active' : '' }}">
                {{ __('Historial') }}
            </a>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1" style="border-top: 1px solid var(--kairo-border)">
            <div class="px-4">
                <div class="font-medium text-base" style="color: var(--kairo-text)" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm" style="color: var(--kairo-text-dim)">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
