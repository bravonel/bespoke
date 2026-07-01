<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'primary_contact_name',
        'primary_contact_email',
        'primary_contact_phone',
        'notes',
    ];

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public static function statusOptions(): array
    {
        return ['active', 'paused', 'archived'];
    }
}
