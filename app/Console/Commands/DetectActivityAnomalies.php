<?php

namespace App\Console\Commands;

use App\Models\ActivityAlert;
use App\Models\ActivityEvent;
use Illuminate\Console\Command;

class DetectActivityAnomalies extends Command
{
    protected $signature = 'activity:detect-anomalies';

    protected $description = 'Detecta patrones básicos de riesgo en accesos y exportaciones';

    public function handle(): int
    {
        $created = 0;
        $bucket = now()->format('YmdH');

        $failedIps = ActivityEvent::query()
            ->whereIn('event_type', ['auth.login_failed', 'auth.locked_out'])
            ->where('created_at', '>=', now()->subMinutes(15))
            ->whereNotNull('ip_hash')
            ->selectRaw('ip_hash, COUNT(*) as total')
            ->groupBy('ip_hash')
            ->havingRaw('COUNT(*) >= 5')
            ->get();

        foreach ($failedIps as $row) {
            $alert = ActivityAlert::query()->firstOrCreate(
                ['fingerprint' => hash('sha256', "failed-login-{$row->ip_hash}-{$bucket}")],
                [
                    'alert_type' => 'repeated_login_failures',
                    'severity' => 'high',
                    'title' => 'Intentos repetidos de acceso',
                    'description' => "Se detectaron {$row->total} intentos fallidos en 15 minutos.",
                    'metadata' => ['ip_hash' => $row->ip_hash, 'count' => $row->total],
                    'detected_at' => now(),
                ],
            );
            $created += (int) $alert->wasRecentlyCreated;
        }

        $heavyExports = ActivityEvent::query()
            ->where('event_type', 'report.exported')
            ->where('created_at', '>=', now()->subHour())
            ->whereNotNull('actor_id')
            ->selectRaw('actor_id, COUNT(*) as total')
            ->groupBy('actor_id')
            ->havingRaw('COUNT(*) >= 10')
            ->get();

        foreach ($heavyExports as $row) {
            $alert = ActivityAlert::query()->firstOrCreate(
                ['fingerprint' => hash('sha256', "heavy-export-{$row->actor_id}-{$bucket}")],
                [
                    'alert_type' => 'unusual_exports',
                    'severity' => 'medium',
                    'user_id' => $row->actor_id,
                    'title' => 'Volumen inusual de exportaciones',
                    'description' => "Se detectaron {$row->total} exportaciones en una hora.",
                    'metadata' => ['count' => $row->total],
                    'detected_at' => now(),
                ],
            );
            $created += (int) $alert->wasRecentlyCreated;
        }

        $this->info("Alertas nuevas: {$created}");

        return self::SUCCESS;
    }
}
