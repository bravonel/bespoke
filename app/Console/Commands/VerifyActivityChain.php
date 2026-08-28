<?php

namespace App\Console\Commands;

use App\Models\ActivityAlert;
use App\Services\Audit\ActivityChainVerifier;
use Illuminate\Console\Command;

class VerifyActivityChain extends Command
{
    protected $signature = 'activity:verify-chain';

    protected $description = 'Verifica la cadena criptográfica del registro de actividad';

    public function handle(ActivityChainVerifier $verifier): int
    {
        if (blank(config('app.key'))) {
            $this->error('APP_KEY no está configurada; no es posible verificar firmas.');

            return self::FAILURE;
        }

        $failures = $verifier->failures();
        $activeFingerprints = [];

        foreach ($failures as $failure) {
            $fingerprint = "activity-chain:{$failure['kind']}:{$failure['event_id']}";
            $activeFingerprints[] = $fingerprint;
            $kindLabel = $failure['kind'] === 'broken_link' ? 'enlace roto' : 'contenido alterado';

            ActivityAlert::query()->updateOrCreate(
                ['fingerprint' => $fingerprint],
                [
                    'alert_type' => 'integrity_failure',
                    'severity' => 'critical',
                    'title' => 'Fallo de integridad en auditoría',
                    'description' => "El evento {$failure['event_id']} presenta {$kindLabel}.",
                    'metadata' => [
                        'activity_event_id' => $failure['event_id'],
                        'failure_kind' => $failure['kind'],
                    ],
                    'detected_at' => now(),
                    'resolved_at' => null,
                ],
            );

            $this->error("Evento {$failure['event_id']}: {$kindLabel}.");
        }

        ActivityAlert::query()
            ->where('alert_type', 'integrity_failure')
            ->whereNull('resolved_at')
            ->get()
            ->reject(fn (ActivityAlert $alert) => in_array($alert->fingerprint, $activeFingerprints, true))
            ->each->update(['resolved_at' => now()]);

        if ($failures !== []) {
            $this->error('Cadena inválida: '.count($failures).' fallo(s) detectado(s).');

            return self::FAILURE;
        }

        $this->info('Cadena de actividad válida.');

        return self::SUCCESS;
    }
}
