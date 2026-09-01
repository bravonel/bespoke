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

<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-white/70 bg-white/85 backdrop-blur">
    <!-- Primary Navigation Menu -->
    <div class="shell">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        <x-application-logo />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden xl:-my-px xl:ms-6 xl:flex xl:space-x-4 2xl:ms-8 2xl:space-x-6">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Resumen') }}
                    </x-nav-link>

                    <x-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')" wire:navigate>
                        {{ __('Clientes') }}
                    </x-nav-link>

                    <x-nav-link :href="route('brands.index')" :active="request()->routeIs('brands.*')" wire:navigate>
                        {{ __('Marcas') }}
                    </x-nav-link>

                    <x-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*')" wire:navigate>
                        {{ __('Proyectos') }}
                    </x-nav-link>

                    <x-nav-link :href="route('qr-codes.index')" :active="request()->routeIs('qr-codes.*')" wire:navigate>
                        {{ __('QR') }}
                    </x-nav-link>

                    <x-nav-link :href="route('collaborators.index')" :active="request()->routeIs('collaborators.*')" wire:navigate>
                        {{ __('Colaboradores') }}
                    </x-nav-link>

                    <x-nav-link :href="route('tasks.mine')" :active="request()->routeIs('tasks.mine')" wire:navigate>
                        {{ __('Tareas') }}
                    </x-nav-link>

                    <x-nav-link :href="route('activity.index')" :active="request()->routeIs('activity.*')" wire:navigate>
                        {{ auth()->user()->canViewTeamActivity() ? __('Actividad') : __('Mi actividad') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden xl:flex xl:items-center xl:ms-4">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center rounded-2xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm font-medium leading-4 text-slate-600 transition hover:border-stone-300 hover:text-slate-800 focus:outline-none">
                            <div class="max-w-32 truncate whitespace-nowrap" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Perfil') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Cerrar sesión') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center xl:hidden">
                <button
                    type="button"
                    @click="open = ! open"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-stone-200 bg-white text-slate-500 transition hover:bg-stone-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300"
                    :aria-label="open ? 'Cerrar menú' : 'Abrir menú'"
                    :data-tooltip="open ? 'Cerrar menú' : 'Abrir menú'"
                >
                    <x-heroicon-o-bars-3 x-show="!open" class="h-5 w-5" aria-hidden="true" />
                    <x-heroicon-o-x-mark x-show="open" class="h-5 w-5" style="display:none" aria-hidden="true" />
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-stone-200/80 xl:hidden">
        <div class="grid gap-1 px-3 pb-3 pt-2 sm:grid-cols-2 lg:grid-cols-4">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                {{ __('Resumen') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')" wire:navigate>
                {{ __('Clientes') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('brands.index')" :active="request()->routeIs('brands.*')" wire:navigate>
                {{ __('Marcas') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*')" wire:navigate>
                {{ __('Proyectos') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('qr-codes.index')" :active="request()->routeIs('qr-codes.*')" wire:navigate>
                {{ __('QR Studio') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('collaborators.index')" :active="request()->routeIs('collaborators.*')" wire:navigate>
                {{ __('Colaboradores') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('tasks.mine')" :active="request()->routeIs('tasks.mine')" wire:navigate>
                {{ __('Mis tareas') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('activity.index')" :active="request()->routeIs('activity.*')" wire:navigate>
                {{ auth()->user()->canViewTeamActivity() ? __('Actividad') : __('Mi actividad') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Perfil') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('Cerrar sesión') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
