<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Bespoke OS') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-['Space_Grotesk'] antialiased">
        <div class="min-h-screen">
            <div class="shell py-8">
                <header class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <x-application-logo />

                    @if (Route::has('login'))
                        <livewire:welcome.navigation />
                    @endif
                </header>

                <main class="mt-10 grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
                    <section class="panel overflow-hidden p-8 lg:p-10">
                        <p class="page-kicker">Sistema modular para agencia</p>
                        <h1 class="mt-4 text-4xl font-semibold tracking-tight text-slate-950 sm:text-5xl">
                            Orden operativo y revisión previa en una sola plataforma.
                        </h1>
                        <p class="mt-6 max-w-2xl text-base leading-7 text-slate-600">
                            Bespoke OS nace para que cuentas, médico, diseño y cliente sepan qué sigue, quién trae el pendiente y qué material está listo antes de pasar por COFEPRIS.
                        </p>

                        <div class="mt-8 flex flex-wrap gap-3">
                            @auth
                                <a href="{{ route('dashboard') }}" class="button-primary">Entrar al sistema</a>
                            @else
                                <a href="{{ route('register') }}" class="button-primary">Crear acceso</a>
                                <a href="{{ route('login') }}" class="button-secondary">Iniciar sesión</a>
                            @endauth
                        </div>

                        <div class="mt-10 grid gap-4 sm:grid-cols-3">
                            <div class="rounded-2xl bg-stone-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Módulo 1</div>
                                <div class="mt-2 font-semibold text-slate-900">Proyectos</div>
                                <p class="mt-2 text-sm text-slate-600">Pendientes, responsables, aprobaciones y seguimiento.</p>
                            </div>
                            <div class="rounded-2xl bg-stone-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Módulo 2</div>
                                <div class="mt-2 font-semibold text-slate-900">Claims</div>
                                <p class="mt-2 text-sm text-slate-600">Revisión asistida de PDFs, referencias y redacción.</p>
                            </div>
                            <div class="rounded-2xl bg-stone-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Más adelante</div>
                                <div class="mt-2 font-semibold text-slate-900">Bot operativo</div>
                                <p class="mt-2 text-sm text-slate-600">Resumir, recordar y empujar el avance del equipo.</p>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <div class="panel p-6">
                            <h2 class="text-lg font-semibold text-slate-950">Lo que ya quedó prendido</h2>
                            <ul class="mt-4 space-y-3 text-sm text-slate-600">
                                <li>Auth con Laravel Breeze y Livewire</li>
                                <li>Tablero base de operación</li>
                                <li>Catálogos iniciales de clientes y marcas</li>
                                <li>Proyectos con tareas simples</li>
                            </ul>
                        </div>

                        <div class="panel p-6">
                            <h2 class="text-lg font-semibold text-slate-950">Siguiente tramo</h2>
                            <ul class="mt-4 space-y-3 text-sm text-slate-600">
                                <li>Plantillas por tipo de proyecto</li>
                                <li>Permisos por rol</li>
                                <li>Recordatorios por correo y WhatsApp</li>
                                <li>Expediente científico y semáforo COFEPRIS</li>
                            </ul>
                        </div>
                    </section>
                </main>
            </div>
        </div>
    </body>
</html>
