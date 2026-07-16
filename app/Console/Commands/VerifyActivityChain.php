<?php

namespace App\Console\Commands;

use App\Models\ActivityAlert;
use App\Models\ActivityEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class VerifyActivityChain extends Command
{
    protected $signature = 'activity:verify-chain';

    protected $description = 'Verifica la cadena criptográfica del registro de actividad';

    public function handle(): int
    {
        $previousHash = null;

        foreach (ActivityEvent::query()->orderBy('id')->cursor() as $event) {
            $payload = Arr::sortRecursive([
                'actor_id' => $event->actor_id,
                'user_session_id' => $event->user_session_id,
                'event_type' => $event->event_type,
                'channel' => $event->channel,
                'status' => $event->status,
                'auditable_type' => $event->auditable_type,
                'auditable_id' => $event->auditable_id,
                'project_id' => $event->project_id,
                'client_id' => $event->client_id,
                'metadata' => $event->metadata ?? [],
                'created_at' => $event->created_at?->format('Y-m-d H:i:s'),
                'previous_hash' => $event->previous_hash,
            ]);
            $expected = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), (string) config('app.key'));

            if ($event->previous_hash !== $previousHash || ! hash_equals((string) $event->event_hash, $expected)) {
                ActivityAlert::query()->firstOrCreate(
                    ['fingerprint' => 'activity-chain-'.$event->id],
                    [
                        'alert_type' => 'integrity_failure',
                        'severity' => 'critical',
                        'title' => 'Fallo de integridad en auditoría',
                        'description' => "El evento {$event->id} no coincide con la cadena criptográfica.",
                        'metadata' => ['activity_event_id' => $event->id],
                        'detected_at' => now(),
                    ],
                );
                $this->error("Cadena inválida en evento {$event->id}.");

                return self::FAILURE;
            }

            $previousHash = $event->event_hash;
        }

        $this->info('Cadena de actividad válida.');

        return self::SUCCESS;
    }
}
