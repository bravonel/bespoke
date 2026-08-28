<?php

namespace App\Services\Audit;

use App\Models\ActivityEvent;
use Illuminate\Support\Arr;

class ActivityChainVerifier
{
    /** @return array<int, array{event_id: int, kind: string}> */
    public function failures(): array
    {
        $failures = [];
        $previousHash = null;

        foreach (ActivityEvent::query()->orderBy('id')->cursor() as $event) {
            foreach ($this->failureKinds($event, $previousHash) as $kind) {
                $failures[] = ['event_id' => $event->id, 'kind' => $kind];
            }

            $previousHash = $event->event_hash;
        }

        return $failures;
    }

    /** @return array<int, string> */
    public function failureKindsForEvent(ActivityEvent $event): array
    {
        $previousHash = ActivityEvent::query()
            ->where('id', '<', $event->id)
            ->latest('id')
            ->value('event_hash');

        return $this->failureKinds($event, $previousHash);
    }

    public function expectedHash(ActivityEvent $event, ?int $projectId = null, bool $replaceProjectId = false): string
    {
        $payload = Arr::sortRecursive([
            'actor_id' => $event->actor_id,
            'user_session_id' => $event->user_session_id,
            'event_type' => $event->event_type,
            'channel' => $event->channel,
            'status' => $event->status,
            'auditable_type' => $event->auditable_type,
            'auditable_id' => $event->auditable_id,
            'project_id' => $replaceProjectId ? $projectId : $event->project_id,
            'client_id' => $event->client_id,
            'metadata' => $event->metadata ?? [],
            'created_at' => $event->created_at?->format('Y-m-d H:i:s'),
            'previous_hash' => $event->previous_hash,
        ]);

        return hash_hmac(
            'sha256',
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            (string) config('app.key'),
        );
    }

    /** @return array<int, string> */
    private function failureKinds(ActivityEvent $event, ?string $previousHash): array
    {
        $failures = [];

        if ($event->previous_hash !== $previousHash) {
            $failures[] = 'broken_link';
        }

        if (! hash_equals((string) $event->event_hash, $this->expectedHash($event))) {
            $failures[] = 'content_signature';
        }

        return $failures;
    }
}
