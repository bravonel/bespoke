<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityDailyMetric extends Model
{
    protected $fillable = [
        'metric_date',
        'metric_type',
        'user_id',
        'project_id',
        'dimension_key',
        'page',
        'dimensions',
        'event_count',
        'session_count',
        'active_seconds',
        'idle_seconds',
        'fingerprint',
    ];

    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
            'dimensions' => 'array',
        ];
    }
}
