<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemporaryCoverage extends Model
{
    protected $fillable = [
        'owner_user_id',
        'delegate_user_id',
        'starts_on',
        'ends_on',
        'note',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'revoked_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_user_id');
    }

    public function scopeEffective(Builder $query, mixed $date = null): Builder
    {
        $date ??= today();

        return $query
            ->whereNull('revoked_at')
            ->whereDate('starts_on', '<=', $date)
            ->whereDate('ends_on', '>=', $date);
    }

    public function isEffective(): bool
    {
        return $this->revoked_at === null
            && $this->starts_on->startOfDay()->lte(today())
            && $this->ends_on->endOfDay()->gte(today());
    }

    public function statusLabel(): string
    {
        if ($this->revoked_at) {
            return 'Revocada';
        }

        if ($this->ends_on->isBefore(today())) {
            return 'Finalizada';
        }

        if ($this->starts_on->isAfter(today())) {
            return 'Programada';
        }

        return 'Activa';
    }
}
