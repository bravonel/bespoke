<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Bespoke OS') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon-180.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-['Space_Grotesk'] antialiased">
        <div class="min-h-screen">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="pt-10 pb-8">
                    <div class="shell">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="pb-16 {{ isset($header) ? '' : 'pt-10' }}">
                @if (session('status'))
                    <div class="shell mb-6">
                        <div class="panel border border-emerald-200 bg-emerald-50/90 px-5 py-4 text-sm font-medium text-emerald-800">
                            {{ session('status') }}
                        </div>
                    </div>
                @endif

                {{ $slot }}
            </main>

            <!-- Task Drawer (global) -->
            <div
                x-data="taskDrawer()"
                x-on:open-task-drawer.window="open($event.detail.url)"
                x-on:keydown.escape.window="close()"
                x-show="isOpen"
                class="fixed inset-0 z-50"
                style="display:none"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0"
                    style="background: rgba(15,23,42,0.45); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);"
                    x-on:click="close()"
                    x-show="isOpen"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                ></div>

                <!-- Drawer panel -->
                <div
                    class="task-drawer-panel absolute right-0 top-0 h-full w-full max-w-[42rem] overflow-y-auto border-l border-stone-200 bg-white shadow-2xl"
                    :class="{ 'task-drawer-panel--open': isOpen }"
                >
                    <div class="sticky top-0 z-10 flex items-center justify-between border-b border-stone-200 bg-white/95 px-6 py-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Detalle de tarea</p>
                        <button type="button" x-on:click="close()" class="button-secondary py-1.5 text-xs">Cerrar ✕</button>
                    </div>

                    <div class="p-6 lg:p-8">
                        <div x-show="loading" class="flex items-center justify-center py-16">
                            <div class="h-6 w-6 animate-spin rounded-full border-2 border-stone-300" style="border-top-color:var(--brand-amber)"></div>
                        </div>
                        <div x-show="!loading" x-html="content"></div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
