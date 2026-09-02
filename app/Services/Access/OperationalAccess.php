<?php

namespace App\Services\Access;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TemporaryCoverage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class OperationalAccess
{
    private array $coveredUserIds = [];

    private const GLOBAL_ROLES = [
        User::ROLE_ADMIN,
        User::ROLE_DIRECTION,
        User::ROLE_ACCOUNTS,
        User::ROLE_TRAFFIC_PM,
    ];

    public function hasGlobalAccess(User $user): bool
    {
        // Existing accounts without an assigned role keep their historical access
        // during the passive rollout. Once a role is assigned, least privilege applies.
        return $user->role === null || $user->hasRole(self::GLOBAL_ROLES);
    }

    public function canCreateProjects(User $user): bool
    {
        return $this->hasGlobalAccess($user);
    }

    public function canManageCollaborators(User $user): bool
    {
        return $user->isActiveForAccess() && $user->isAdmin();
    }

    public function canManageCapacity(User $user): bool
    {
        return $user->isActiveForAccess()
            && $user->hasRole([User::ROLE_ADMIN, User::ROLE_ACCOUNTS]);
    }

    public function canViewProject(User $user, Project $project): bool
    {
        return $this->projects($user)->whereKey($project)->exists();
    }

    public function canManageProject(User $user, Project $project): bool
    {
        return $this->hasGlobalAccess($user) || $project->owner_id === $user->id;
    }

    public function canViewTask(User $user, Task $task): bool
    {
        return $this->tasks($user)->whereKey($task)->exists();
    }

    public function canManageTask(User $user, Task $task): bool
    {
        return $this->canManageProject($user, $task->project);
    }

    public function canOperateTask(User $user, Task $task): bool
    {
        if ($this->canManageTask($user, $task)
            || $task->assigned_to === $user->id
            || $task->assignments()->where('user_id', $user->id)->exists()) {
            return true;
        }

        $coveredIds = $this->coveredUserIds($user);

        return $coveredIds !== [] && (
            in_array((int) $task->assigned_to, $coveredIds, true)
            || in_array((int) $task->project->owner_id, $coveredIds, true)
            || $task->assignments()->whereIn('user_id', $coveredIds)->exists()
        );
    }

    public function projects(User $user): Builder
    {
        $query = Project::query();

        if ($this->hasGlobalAccess($user)) {
            return $query;
        }

        $userIds = [$user->id, ...$this->coveredUserIds($user)];

        return $query->where(function (Builder $query) use ($userIds): void {
            $query
                ->whereIn('owner_id', $userIds)
                ->orWhereHas('memberships', fn (Builder $membership) => $membership
                    ->whereIn('user_id', $userIds)
                    ->where('status', ProjectMember::STATUS_ACTIVE))
                ->orWhereHas('tasks', fn (Builder $task) => $task->where(function (Builder $task) use ($userIds): void {
                    $task
                        ->whereIn('assigned_to', $userIds)
                        ->orWhereHas('assignments', fn (Builder $assignment) => $assignment->whereIn('user_id', $userIds));
                }));
        });
    }

    public function tasks(User $user): Builder
    {
        $query = Task::query();

        if ($this->hasGlobalAccess($user)) {
            return $query;
        }

        $userIds = [$user->id, ...$this->coveredUserIds($user)];

        return $query->where(function (Builder $query) use ($userIds): void {
            $query
                ->whereIn('assigned_to', $userIds)
                ->orWhereHas('assignments', fn (Builder $assignment) => $assignment->whereIn('user_id', $userIds))
                ->orWhereHas('project', fn (Builder $project) => $project
                    ->whereIn('owner_id', $userIds)
                    ->orWhereHas('memberships', fn (Builder $membership) => $membership
                        ->whereIn('user_id', $userIds)
                        ->where('status', ProjectMember::STATUS_ACTIVE)));
        });
    }

    public function coveredUserIds(User $user): array
    {
        if (! Schema::hasTable('temporary_coverages')) {
            return [];
        }

        return $this->coveredUserIds[$user->id] ??= TemporaryCoverage::query()
            ->effective()
            ->where('delegate_user_id', $user->id)
            ->pluck('owner_user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
