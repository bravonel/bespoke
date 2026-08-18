<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'tracking_parameters',
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
            'tracking_parameters' => 'array',
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
        return $this->logo_path ? route('qr-codes.logo', $this) : null;
    }

    public function trackedDestinationUrl(): string
    {
        $tracking = $this->tracking_parameters ?? [];

        if (! ($tracking['enabled'] ?? false)) {
            return $this->destination_url;
        }

        $parameters = collect([
            'utm_source' => $tracking['utm_source'] ?? null,
            'utm_medium' => $tracking['utm_medium'] ?? null,
            'utm_campaign' => $tracking['utm_campaign'] ?? null,
            'utm_term' => $tracking['utm_term'] ?? null,
            'utm_content' => $tracking['utm_content'] ?? null,
        ])->filter(fn ($value) => filled($value))->all();

        foreach ($tracking['custom'] ?? [] as $parameter) {
            $key = $parameter['key'] ?? null;
            $value = $parameter['value'] ?? null;

            if (filled($key) && filled($value)) {
                $parameters[$key] = $value;
            }
        }

        if ($parameters === []) {
            return $this->destination_url;
        }

        $fragment = '';
        $baseUrl = $this->destination_url;

        if (str_contains($baseUrl, '#')) {
            [$baseUrl, $fragment] = explode('#', $baseUrl, 2);
        }

        [$urlWithoutQuery, $existingQuery] = array_pad(explode('?', $baseUrl, 2), 2, '');
        parse_str($existingQuery, $existingParameters);
        $mergedParameters = array_replace($existingParameters, $parameters);

        return $urlWithoutQuery.'?'.http_build_query($mergedParameters, '', '&', PHP_QUERY_RFC3986)
            .($fragment !== '' ? '#'.$fragment : '');
    }

    public static function statusOptions(): array
    {
        return ['active', 'paused', 'archived'];
    }
}
