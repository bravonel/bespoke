<?php

namespace App\Services\Audit;

use App\Models\ActivityEvent;
use App\Models\AiAssistantMessage;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectWorkload;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditLogger
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'api_key',
        'secret',
        'authorization',
        'cookie',
        'email',
        'whatsapp_phone',
        'primary_contact_email',
        'primary_contact_phone',
    ];

    private const CONTENT_KEYS = [
        'description',
        'notes',
        'legal_requirements',
        'reference_links',
        'primary_contact_name',
        'primary_contact_email',
        'primary_contact_phone',
        'body',
        'question',
        'answer',
    ];

    public function record(
        string $eventType,
        Model $auditable,
        ?User $actor = null,
        array $metadata = [],
        string $channel = 'web',
        string $status = 'success',
    ): ActivityEvent {
        return $this->write(
            $eventType,
            $actor,
            $auditable,
            $metadata,
            $channel,
            $status,
        );
    }

    public function recordChange(
        string $eventType,
        Model $auditable,
        array $before,
        array $after,
        ?User $actor = null,
        array $metadata = [],
        string $channel = 'web',
    ): ActivityEvent {
        return $this->record($eventType, $auditable, $actor, [
            ...$metadata,
            'changes' => $this->sanitizeChanges($before, $after),
            'fields_changed' => array_values(array_unique([...array_keys($before), ...array_keys($after)])),
        ], $channel);
    }

    public function recordSystem(
        string $eventType,
        ?User $actor = null,
        array $metadata = [],
        string $channel = 'web',
        string $status = 'success',
        ?int $userSessionId = null,
    ): ActivityEvent {
        return $this->write(
            $eventType,
            $actor,
            null,
            $metadata,
            $channel,
            $status,
            $userSessionId,
        );
    }

    public function sanitize(array $metadata): array
    {
        return collect($metadata)
            ->reject(fn ($value, $key) => in_array(Str::lower((string) $key), self::SENSITIVE_KEYS, true)
                && $value !== ['changed' => true])
            ->map(function ($value) {
                if (is_array($value)) {
                    return $this->sanitize($value);
                }

                if (is_string($value)) {
                    return Str::limit($value, 2000, '...');
                }

                return $value;
            })
            ->all();
    }

    private function write(
        string $eventType,
        ?User $actor,
        ?Model $auditable,
        array $metadata,
        string $channel,
        string $status,
        ?int $userSessionId = null,
    ): ActivityEvent {
        $createdAt = now();
        $actorId = $actor?->getKey() ?? auth()->id();
        $sessionId = $userSessionId ?? (request()?->hasSession()
            ? request()->session()->get('activity_session_id')
            : null);
        $sanitized = $this->sanitize($metadata);
        $projectId = $auditable ? $this->projectId($auditable) : null;
        $clientId = $auditable ? $this->clientId($auditable) : null;

        return DB::transaction(function () use (
            $eventType,
            $actorId,
            $sessionId,
            $auditable,
            $projectId,
            $clientId,
            $sanitized,
            $channel,
            $status,
            $createdAt,
        ) {
            $previousHash = ActivityEvent::query()->latest('id')->lockForUpdate()->value('event_hash');
            $payload = Arr::sortRecursive([
                'actor_id' => $actorId,
                'user_session_id' => $sessionId,
                'event_type' => $eventType,
                'channel' => $channel,
                'status' => $status,
                'auditable_type' => $auditable?->getMorphClass(),
                'auditable_id' => $auditable?->getKey(),
                'project_id' => $projectId,
                'client_id' => $clientId,
                'metadata' => $sanitized,
                'created_at' => $createdAt->format('Y-m-d H:i:s'),
                'previous_hash' => $previousHash,
            ]);
            $eventHash = hash_hmac(
                'sha256',
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                (string) config('app.key')
            );

            return ActivityEvent::query()->create([
                ...$payload,
                'ip_hash' => $this->hashedIp(),
                'request_id' => request()?->header('X-Request-ID') ?: (string) Str::uuid(),
                'route_name' => request()?->route()?->getName(),
                'http_method' => request()?->method(),
                'event_hash' => $eventHash,
            ]);
        });
    }

    private function sanitizeChanges(array $before, array $after): array
    {
        $fields = array_values(array_unique([...array_keys($before), ...array_keys($after)]));

        return collect($fields)->mapWithKeys(function (string $field) use ($before, $after) {
            if (in_array(Str::lower($field), self::CONTENT_KEYS, true)) {
                return [$field => ['changed' => true]];
            }

            return [$field => [
                'before' => $this->sanitizeValue($before[$field] ?? null),
                'after' => $this->sanitizeValue($after[$field] ?? null),
            ]];
        })->all();
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return Str::limit($value, 500, '...');
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        return $value;
    }

    private function projectId(Model $model): ?int
    {
        return match (true) {
            $model instanceof Project => $model->getKey(),
            $model instanceof Task,
            $model instanceof ProjectMember,
            $model instanceof ProjectWorkload => $model->project_id,
            $model instanceof Subtask => $model->task?->project_id,
            $model instanceof AiAssistantMessage && $model->context_type === Project::class => $model->context_id,
            default => $model->getAttribute('project_id'),
        };
    }

    private function clientId(Model $model): ?int
    {
        return match (true) {
            $model instanceof Client => $model->getKey(),
            $model instanceof Project => $model->client_id,
            $model instanceof Task,
            $model instanceof ProjectMember,
            $model instanceof ProjectWorkload => $model->project?->client_id,
            $model instanceof Subtask => $model->task?->project?->client_id,
            $model instanceof AiAssistantMessage && $model->context_type === Project::class => Project::query()->whereKey($model->context_id)->value('client_id'),
            default => $model->getAttribute('client_id'),
        };
    }

    private function hashedIp(): ?string
    {
        $ip = request()?->ip();

        if (! $ip) {
            return null;
        }

        return hash_hmac('sha256', $ip, (string) config('app.key', 'bespoke-os'));
    }
}
