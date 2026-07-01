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
    <body class="font-['Space_Grotesk'] text-slate-900 antialiased">

        <div class="flex min-h-screen">

            {{-- Left panel — branding --}}
            <div class="hidden lg:flex lg:w-[46%] xl:w-[42%] flex-col items-center justify-center px-12 xl:px-16" style="background: radial-gradient(ellipse at top left, rgba(245,166,35,0.12) 0%, transparent 60%), radial-gradient(ellipse at bottom right, rgba(233,30,140,0.07) 0%, transparent 55%), #f8f7f5">
                <div class="w-full max-w-sm">
                    <img src="{{ asset('assets/logo.png') }}" alt="Bespoke" class="w-full">

                    <p class="mt-10 text-sm leading-7 text-slate-500">
                        El sistema operativo de Bespoke. Proyectos, tareas y checklist en un solo lugar — para que el equipo siempre sepa qué sigue.
                    </p>

                    <div class="mt-10 flex items-center gap-3">
                        <div class="h-px flex-1 bg-stone-300"></div>
                        <img src="{{ asset('assets/logo-b.png') }}" alt="" class="h-8 w-auto opacity-25">
                        <div class="h-px flex-1 bg-stone-300"></div>
                    </div>
                </div>
            </div>

            {{-- Right panel — form --}}
            <div class="flex flex-1 flex-col items-center justify-center px-6 py-12 sm:px-10">

                {{-- Mobile logo --}}
                <div class="mb-10 lg:hidden">
                    <img src="{{ asset('assets/logo.png') }}" alt="Bespoke" class="h-12 w-auto">
                </div>

                <div class="w-full max-w-sm">
                    <div class="panel px-7 py-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>

        </div>

    </body>
</html>
