@php
    $design = array_merge([
        'foreground' => '#161616',
        'background' => '#FFFFFF',
        'dots' => 'rounded',
        'corners' => 'extra-rounded',
        'frame' => 'soft',
        'cta' => 'ESCANEA AQUÍ',
    ], $qrCode->design ?: []);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Imprimir {{ $qrCode->name }} · Bespoke OS</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: #e7e5e4; color: #171717; font-family: 'Space Grotesk', sans-serif; }
        .print-toolbar { position: sticky; top: 0; z-index: 10; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px 24px; background: rgba(23, 23, 23, .96); color: white; box-shadow: 0 8px 30px rgba(0,0,0,.16); }
        .print-toolbar__meta { min-width: 0; }
        .print-toolbar__meta strong { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 14px; }
        .print-toolbar__meta span { display: block; margin-top: 2px; color: rgba(255,255,255,.5); font-size: 11px; }
        .print-actions { display: flex; flex-shrink: 0; gap: 8px; }
        .print-button { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid rgba(255,255,255,.16); border-radius: 14px; background: transparent; padding: 10px 15px; color: white; font: inherit; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; }
        .print-button--primary { border-color: #e91e8c; background: #e91e8c; }
        .print-stage { display: grid; min-height: calc(100vh - 70px); place-items: center; padding: 48px 24px; }
        .print-sheet { width: min(100%, 210mm); min-height: 270mm; display: grid; place-items: center; border-radius: 12px; background: white; padding: 24mm; box-shadow: 0 30px 80px -36px rgba(15,23,42,.45); }
        .print-piece { width: 108mm; max-width: 100%; text-align: center; }
        .print-piece__eyebrow { margin: 0 0 12px; color: #e91e8c; font-size: 9px; font-weight: 700; letter-spacing: .22em; text-transform: uppercase; }
        .print-piece__title { margin: 0 0 7px; font-size: 20px; line-height: 1.15; letter-spacing: -.025em; }
        .print-piece__owner { margin: 0 0 20px; color: #78716c; font-size: 11px; }
        .print-frame { width: 100%; padding: 14px; background: white; }
        .print-frame[data-frame="soft"] { border: 1px solid #e7e5e4; border-radius: 26px; box-shadow: 0 20px 45px -28px rgba(15,23,42,.35); }
        .print-frame[data-frame="ticket"] { border: 1px solid #e7e5e4; border-radius: 18px; clip-path: polygon(0 0,100% 0,100% 44%,96% 50%,100% 56%,100% 100%,0 100%,0 56%,4% 50%,0 44%); }
        .print-frame[data-frame="none"] { padding: 0; }
        .print-qr { width: 100%; }
        .print-qr svg, .print-qr canvas { display: block; width: 100%; height: auto; }
        .print-cta { display: inline-flex; max-width: 100%; margin-top: 10px; border-radius: 999px; background: #171717; padding: 9px 22px; color: white; font-size: 10px; font-weight: 700; letter-spacing: .18em; }
        .print-url { margin: 16px 0 0; color: #a8a29e; font-size: 9px; word-break: break-all; }
        @page { size: auto; margin: 12mm; }
        @media print {
            body { min-height: auto; background: white; }
            .no-print { display: none !important; }
            .print-stage { min-height: 0; padding: 0; }
            .print-sheet { width: 100%; min-height: auto; border-radius: 0; padding: 0; box-shadow: none; }
            .print-piece { width: 108mm; }
            .print-frame { break-inside: avoid; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
        @media (max-width: 640px) {
            .print-toolbar { align-items: flex-start; padding: 12px; }
            .print-toolbar__meta span { display: none; }
            .print-actions .print-button:first-child { display: none; }
            .print-stage { padding: 24px 12px; }
            .print-sheet { min-height: auto; padding: 28px 18px; }
        }
    </style>
</head>
<body>
    <header class="print-toolbar no-print">
        <div class="print-toolbar__meta">
            <strong>{{ $qrCode->name }}</strong>
            <span>Vista previa de impresión · el vínculo dinámico seguirá registrando escaneos</span>
        </div>
        <div class="print-actions">
            <a href="{{ route('qr-codes.show', $qrCode) }}" class="print-button">Volver</a>
            <button type="button" class="print-button print-button--primary" onclick="window.print()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6z"/></svg>
                Imprimir QR
            </button>
        </div>
    </header>

    <main class="print-stage">
        <article class="print-sheet">
            <div class="print-piece">
                <p class="print-piece__eyebrow">Bespoke Signal</p>
                <h1 class="print-piece__title">{{ $qrCode->name }}</h1>
                @if ($qrCode->client || $qrCode->brand)
                    <p class="print-piece__owner">{{ $qrCode->client?->name }}{{ $qrCode->brand ? ' · '.$qrCode->brand->name : '' }}</p>
                @endif
                <div class="print-frame" data-frame="{{ $design['frame'] }}">
                    <div id="print-qr" class="print-qr"></div>
                    @if ($design['frame'] !== 'none')
                        <div class="print-cta">{{ $design['cta'] }}</div>
                    @endif
                </div>
                <p class="print-url">{{ $qrCode->shortUrl() }}</p>
            </div>
        </article>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.renderPrintableQr(document.getElementById('print-qr'), @js([
                'url' => $qrCode->shortUrl(),
                'logo' => $qrCode->logoUrl(),
                'design' => $design,
            ]));
        });
    </script>
</body>
</html>
