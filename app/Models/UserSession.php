<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_key_hash',
        'channel',
        'ip_hash',
        'device_type',
        'browser',
        'platform',
        'last_page',
        'active_seconds',
        'idle_seconds',
        'started_at',
        'last_activity_at',
        'last_heartbeat_at',
        'ended_at',
        'end_reason',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
            'ended_at' => 'datetime',
            'active_seconds' => 'integer',
            'idle_seconds' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activityEvents(): HasMany
    {
        return $this->hasMany(ActivityEvent::class);
    }

    public function uiEvents(): HasMany
    {
        return $this->hasMany(UiEvent::class);
    }
}
