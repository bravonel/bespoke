<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'status_before_archive',
        'primary_contact_name',
        'primary_contact_email',
        'primary_contact_phone',
        'notes',
    ];

    protected static function booted(): void
    {
        static::deleting(fn () => throw new LogicException('Los clientes deben desactivarse; el borrado físico está deshabilitado.'));
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
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
