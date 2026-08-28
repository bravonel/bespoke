<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class ActivityEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_id',
        'user_session_id',
        'event_type',
        'channel',
        'status',
        'auditable_type',
        'auditable_id',
        'project_id',
        'client_id',
        'metadata',
        'ip_hash',
        'request_id',
        'route_name',
        'http_method',
        'previous_hash',
        'event_hash',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Los eventos de auditoría son inmutables.'));
        static::deleting(fn () => throw new LogicException('Los eventos de auditoría son append-only.'));
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function userSession(): BelongsTo
    {
        return $this->belongsTo(UserSession::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function actorLabel(): string
    {
        return $this->actor?->name
            ?? data_get($this->metadata, 'context.actor_label')
            ?? 'Sistema';
    }

    public function contextLabel(): string
    {
        return $this->project?->name
            ?? $this->client?->name
            ?? data_get($this->metadata, 'context.project_name')
            ?? data_get($this->metadata, 'context.client_name')
            ?? 'General';
    }

    public function entityLabel(): ?string
    {
        return $this->auditable?->title
            ?? $this->auditable?->name
            ?? $this->auditable?->code
            ?? data_get($this->metadata, 'context.entity_label');
    }
}
