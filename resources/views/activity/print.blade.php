<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Actividad Bespoke OS</title>
    <style>
        body { font-family: Arial, sans-serif; color: #172033; margin: 32px; }
        h1 { margin-bottom: 4px; } p { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; font-size: 12px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #e7e5e4; vertical-align: top; }
        th { font-size: 10px; text-transform: uppercase; color: #64748b; }
        @media print { button { display: none; } body { margin: 12mm; } }
    </style>
</head>
<body>
    <button onclick="window.print()">Imprimir / Guardar como PDF</button>
    <h1>Actividad Bespoke OS</h1>
    <p>Periodo {{ $filters['from'] }} a {{ $filters['to'] }} · Generado {{ now()->format('d/m/Y H:i') }}</p>
    <table>
        <thead><tr><th>Fecha</th><th>Actor</th><th>Evento</th><th>Proyecto</th><th>Canal</th><th>Cambios</th></tr></thead>
        <tbody>
            @foreach ($events as $event)
                <tr>
                    <td>{{ $event->created_at?->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $event->actor?->name ?: 'Sistema' }}</td>
                    <td>{{ $labels::get($event->event_type) }}</td>
                    <td>{{ $event->project?->name ?: $event->client?->name }}</td>
                    <td>{{ $event->channel }}</td>
                    <td>{{ collect($event->metadata['fields_changed'] ?? [])->join(', ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
