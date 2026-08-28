<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectWorkload extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'task_id',
        'user_id',
        'role',
        'work_date',
        'estimated_minutes',
        'personal_priority',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'estimated_minutes' => 'integer',
            'personal_priority' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function roleOptions(): array
    {
        return [
            'accounts' => 'Cuentas',
            'medical' => 'Medical',
            'design' => 'Diseño',
            'copy' => 'Copy',
            'social_media' => 'Social Media',
            'client' => 'Cliente',
        ];
    }
}
