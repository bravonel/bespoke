<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Bespoke Advertising</title>
        <link rel="icon" href="{{ asset('site-home/bespoke.svg') }}" type="image/svg+xml">
        <link rel="stylesheet" href="{{ asset('site-home/styles.css') }}">
        <style>
            .os-entry {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 20;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 44px;
                padding: 0 18px;
                border: 1px solid rgba(29, 29, 27, 0.14);
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.92);
                color: #1d1d1b;
                font: 700 14px/1 Arial, Helvetica, sans-serif;
                text-decoration: none;
                letter-spacing: 0.01em;
                backdrop-filter: blur(12px);
                box-shadow: 0 18px 40px rgba(29, 29, 27, 0.12);
                transition:
                    transform 0.18s ease,
                    box-shadow 0.18s ease,
                    border-color 0.18s ease,
                    background 0.18s ease;
            }

            .os-entry:hover {
                border-color: rgba(218, 22, 122, 0.38);
                background: rgba(255, 255, 255, 0.98);
                box-shadow: 0 20px 44px rgba(29, 29, 27, 0.16);
                transform: translateY(-1px);
            }

            .os-entry:focus-visible {
                outline: 2px solid #da167a;
                outline-offset: 3px;
            }

            @media (max-width: 640px) {
                .os-entry {
                    top: 14px;
                    right: 14px;
                    min-height: 40px;
                    padding: 0 14px;
                    font-size: 13px;
                }
            }
        </style>
    </head>
    <body>
        @php
            $entryUrl = auth()->check() ? route('dashboard') : route('login');
            $entryLabel = auth()->check() ? 'Abrir Bespoke OS' : 'Entrar a Bespoke OS';
        @endphp

        <main class="builder is-preview" aria-label="Bespoke Rube Goldberg machine">
            <canvas id="motion-engine" aria-label="Machine canvas"></canvas>
        </main>

        <a class="os-entry" href="{{ $entryUrl }}">{{ $entryLabel }}</a>

        <script>
            window.BespokeHome = {
                base: @json(asset('site-home')),
            };
        </script>
        <script src="{{ asset('site-home/lib/matter.min.js') }}" defer></script>
        <script src="{{ asset('site-home/app.js') }}" defer></script>
    </body>
</html>
