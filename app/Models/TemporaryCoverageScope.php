<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemporaryCoverageScope extends Model
{
    protected $fillable = [
        'client_id',
        'project_id',
    ];

    public function coverage(): BelongsTo
    {
        return $this->belongsTo(TemporaryCoverage::class, 'temporary_coverage_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
