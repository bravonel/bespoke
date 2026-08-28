<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'assigned_to',
        'title',
        'description',
        'blocked_reason',
        'return_reason',
        'status',
        'priority',
        'personal_priority',
        'sort_order',
        'planned_for',
        'estimated_minutes',
        'due_at',
        'completed_at',
        'started_at',
        'delivered_at',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'date',
            'planned_for' => 'date',
            'estimated_minutes' => 'integer',
            'completed_at' => 'datetime',
            'started_at' => 'datetime',
            'delivered_at' => 'datetime',
            'finalized_at' => 'datetime',
            'sort_order' => 'integer',
            'personal_priority' => 'integer',
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

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectWorkload::class);
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_workloads', 'task_id', 'user_id')
            ->withPivot(['id', 'role', 'work_date', 'estimated_minutes', 'personal_priority', 'notes'])
            ->withTimestamps();
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Subtask::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public static function statusMeta(): array
    {
        return [
            'todo' => [
                'label' => 'Por hacer',
                'description' => 'Lo que sigue y aún no arranca.',
            ],
            'in_progress' => [
                'label' => 'En proceso',
                'description' => 'Lo que hoy está en manos del equipo.',
            ],
            'done' => [
                'label' => 'Entregado',
                'description' => 'Listo para revisión interna o entrega al cliente.',
            ],
            'finalized' => [
                'label' => 'Finalizado',
                'description' => 'Aprobado y entregado al cliente; ya no requiere trabajo.',
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

    public static function closedStatuses(): array
    {
        return ['finalized'];
    }

    public static function inactiveStatuses(): array
    {
        return ['done', 'finalized'];
    }

    public function assignedUserIds(): array
    {
        $ids = $this->assignments
            ->pluck('user_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();

        if ($this->assigned_to) {
            $ids->push((int) $this->assigned_to);
        }

        return $ids->unique()->values()->all();
    }

    public function isClosed(): bool
    {
        return in_array($this->status, static::closedStatuses(), true);
    }

    public static function formatEstimatedMinutes(?int $minutes): string
    {
        if ($minutes === null) {
            return 'Sin horas';
        }

        $hours = $minutes / 60;

        if ($minutes % 60 === 0) {
            return (int) $hours.' h';
        }

        return number_format($hours, 1).' h';
    }
}
