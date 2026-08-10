<?php

namespace App\Services\Access;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class OperationalAccess
{
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
        return $this->canManageTask($user, $task) || $task->assigned_to === $user->id;
    }

    public function projects(User $user): Builder
    {
        $query = Project::query();

        if ($this->hasGlobalAccess($user)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query
                ->where('owner_id', $user->id)
                ->orWhereHas('memberships', fn (Builder $membership) => $membership
                    ->where('user_id', $user->id)
                    ->where('status', ProjectMember::STATUS_ACTIVE))
                ->orWhereHas('tasks', fn (Builder $task) => $task->where('assigned_to', $user->id));
        });
    }

    public function tasks(User $user): Builder
    {
        $query = Task::query();

        if ($this->hasGlobalAccess($user)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query
                ->where('assigned_to', $user->id)
                ->orWhereHas('project', fn (Builder $project) => $project
                    ->where('owner_id', $user->id)
                    ->orWhereHas('memberships', fn (Builder $membership) => $membership
                        ->where('user_id', $user->id)
                        ->where('status', ProjectMember::STATUS_ACTIVE)));
        });
    }
}
