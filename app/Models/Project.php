<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'brand_id',
        'owner_id',
        'name',
        'code',
        'project_type',
        'priority',
        'status',
        'current_stage',
        'description',
        'starts_at',
        'due_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'due_at' => 'date',
            'completed_at' => 'datetime',
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public static function statusOptions(): array
    {
        return ['draft', 'active', 'in_review', 'on_hold', 'done'];
    }

    public static function priorityOptions(): array
    {
        return ['low', 'normal', 'high', 'critical'];
    }

    public static function stageOptions(): array
    {
        return ['brief', 'medical_review', 'design', 'client_review', 'ready_to_submit'];
    }
}
