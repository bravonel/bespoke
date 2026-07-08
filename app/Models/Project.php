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
        'odt_code',
        'project_type',
        'delivery_type',
        'target_audience',
        'material_size',
        'priority',
        'status',
        'current_stage',
        'description',
        'legal_requirements',
        'reference_links',
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

    public function workloads(): HasMany
    {
        return $this->hasMany(ProjectWorkload::class);
    }

    public function operationalCode(): string
    {
        return $this->odt_code ?: $this->code;
    }

    public function operationalCodeLabel(): string
    {
        return $this->odt_code ? 'ODT '.$this->odt_code : $this->code;
    }

    public static function statusOptions(): array
    {
        return ['draft', 'active', 'in_review', 'on_hold', 'done'];
    }

    public static function priorityOptions(): array
    {
        return ['low', 'normal', 'high', 'critical'];
    }

    public static function deliveryTypeOptions(): array
    {
        return [
            'digital' => 'Digital',
            'printed' => 'Impreso',
            'both' => 'Digital e impreso',
        ];
    }

    public static function stageOptions(): array
    {
        return ['brief', 'medical_review', 'design', 'client_review', 'ready_to_submit'];
    }
}
