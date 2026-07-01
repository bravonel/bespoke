<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'assigned_to',
        'title',
        'description',
        'status',
        'priority',
        'sort_order',
        'due_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'date',
            'completed_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Subtask::class);
    }

    public static function statusMeta(): array
    {
        return [
            'todo' => [
                'label' => 'Por hacer',
                'description' => 'Lo que sigue y aun no arranca.',
            ],
            'in_progress' => [
                'label' => 'En curso',
                'description' => 'Lo que hoy esta en manos del equipo.',
            ],
            'blocked' => [
                'label' => 'Bloqueadas',
                'description' => 'Lo que necesita destrabe o respuesta.',
            ],
            'done' => [
                'label' => 'Listas',
                'description' => 'Lo ya resuelto o entregado.',
            ],
        ];
    }

    public static function statusOptions(): array
    {
        return array_keys(static::statusMeta());
    }

    public static function priorityMeta(): array
    {
        return [
            'low' => ['label' => 'Baja'],
            'normal' => ['label' => 'Normal'],
            'high' => ['label' => 'Alta'],
        ];
    }

    public static function priorityOptions(): array
    {
        return array_keys(static::priorityMeta());
    }
}
