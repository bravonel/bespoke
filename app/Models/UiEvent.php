<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UiEvent extends Model
{
    protected $fillable = [
        'user_id',
        'user_session_id',
        'event_name',
        'page',
        'target',
        'project_id',
        'entity_type',
        'entity_id',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userSession(): BelongsTo
    {
        return $this->belongsTo(UserSession::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
