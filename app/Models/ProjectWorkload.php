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
        'user_id',
        'role',
        'work_date',
        'estimated_minutes',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'estimated_minutes' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function roleOptions(): array
    {
        return [
            'design' => 'Diseño',
            'copy' => 'Redacción',
            'social_media' => 'Redes sociales',
            'medical' => 'Médico',
            'accounts' => 'Cuentas',
        ];
    }
}
