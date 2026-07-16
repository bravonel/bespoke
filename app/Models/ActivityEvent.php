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
}
