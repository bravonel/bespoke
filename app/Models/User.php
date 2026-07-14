<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'area', 'puesto', 'daily_capacity_minutes', 'last_login_at', 'last_seen_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'daily_capacity_minutes' => 'integer',
            'last_login_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function dailyCapacityHours(): float
    {
        return ($this->daily_capacity_minutes ?? 0) / 60;
    }

    public function dailyCapacityHoursForInput(): string
    {
        $hours = $this->dailyCapacityHours();

        return number_format($hours, $hours === floor($hours) ? 0 : 2, '.', '');
    }

    public function isActiveNow(): bool
    {
        return $this->last_seen_at?->greaterThanOrEqualTo(now()->subMinutes(5)) ?? false;
    }

    public function lastSeenLabel(): string
    {
        if (! $this->last_seen_at) {
            return 'Sin actividad';
        }

        if ($this->isActiveNow()) {
            return 'Activo ahora';
        }

        return $this->last_seen_at->diffForHumans();
    }

    public function lastLoginLabel(): string
    {
        return $this->last_login_at?->diffForHumans() ?? 'Sin inicio registrado';
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function projectWorkloads(): HasMany
    {
        return $this->hasMany(ProjectWorkload::class);
    }
}
