<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'email_verified_at', 'password', 'role', 'area', 'puesto', 'daily_capacity_minutes', 'is_active', 'last_login_at', 'last_seen_at', 'whatsapp_phone', 'whatsapp_enabled'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_DIRECTION = 'direction';

    public const ROLE_ACCOUNTS = 'accounts';

    public const ROLE_TRAFFIC_PM = 'traffic_pm';

    public const ROLE_MEDICAL = 'medical';

    public const ROLE_DESIGN = 'design';

    public const ROLE_LEGAL_REGULATORY = 'legal_regulatory';

    public const ROLE_CLIENT_REVIEWER = 'client_reviewer';

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
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'whatsapp_enabled' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function statusLabel(): string
    {
        return $this->isActiveForAccess() ? 'Activo' : 'Inactivo';
    }

    public function isActiveForAccess(): bool
    {
        return $this->is_active !== false;
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return $this->role !== null && in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /**
     * @return array<string, string>
     */
    public static function roleOptions(): array
    {
        return [
            self::ROLE_ADMIN => 'Administrador',
            self::ROLE_DIRECTION => 'Dirección',
            self::ROLE_ACCOUNTS => 'Cuentas',
            self::ROLE_TRAFFIC_PM => 'Tráfico / PM',
            self::ROLE_MEDICAL => 'Médico',
            self::ROLE_DESIGN => 'Diseño',
            self::ROLE_LEGAL_REGULATORY => 'Legal / Regulatorio',
            self::ROLE_CLIENT_REVIEWER => 'Revisor de cliente',
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

    public function projectMemberships(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function whatsappMessages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class);
    }

    public function activityEvents(): HasMany
    {
        return $this->hasMany(ActivityEvent::class, 'actor_id');
    }

    public function userSessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function uiEvents(): HasMany
    {
        return $this->hasMany(UiEvent::class);
    }

    public function canViewTeamActivity(): bool
    {
        return $this->hasRole([self::ROLE_ADMIN, self::ROLE_DIRECTION]);
    }

    public function memberProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withPivot(['project_role', 'status', 'added_by'])
            ->withTimestamps();
    }
}
