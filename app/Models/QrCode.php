<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class QrCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'brand_id',
        'created_by',
        'name',
        'slug',
        'destination_url',
        'status',
        'design',
        'logo_path',
        'scans_count',
        'last_scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'design' => 'array',
            'scans_count' => 'integer',
            'last_scanned_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scans(): HasMany
    {
        return $this->hasMany(QrScan::class);
    }

    public function shortUrl(): string
    {
        return route('qr.redirect', $this->slug);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public static function statusOptions(): array
    {
        return ['active', 'paused', 'archived'];
    }
}
