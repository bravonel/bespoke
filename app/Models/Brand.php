<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'status',
        'status_before_archive',
        'therapeutic_area',
        'notes',
    ];

    protected static function booted(): void
    {
        static::deleting(fn () => throw new LogicException('Las marcas deben desactivarse; el borrado físico está deshabilitado.'));
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    public static function statusOptions(): array
    {
        return ['active', 'paused', 'archived'];
    }
}
