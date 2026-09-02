<?php

namespace App\Services\Access;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TemporaryCoverage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class OperationalAccess
{
    private array $effectiveCoverages = [];

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

        return $this->coverageForTask($user, $task) !== null;
    }

    public function projects(User $user): Builder
    {
        $query = Project::query();

        if ($this->hasGlobalAccess($user)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->where(fn (Builder $personal) => $this->applyPersonalProjectAccess($personal, $user->id));

            foreach ($this->effectiveCoverages($user) as $coverage) {
                $query->orWhere(function (Builder $covered) use ($coverage): void {
                    $this->applyPersonalProjectAccess($covered, $coverage->owner_user_id);
                    $this->applyCoverageScope($covered, $coverage);
                });
            }
        });
    }

    public function tasks(User $user): Builder
    {
        $query = Task::query();

        if ($this->hasGlobalAccess($user)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->where(fn (Builder $personal) => $this->applyPersonalTaskAccess($personal, $user->id));

            foreach ($this->effectiveCoverages($user) as $coverage) {
                $query->orWhere(function (Builder $covered) use ($coverage): void {
                    $this->applyPersonalTaskAccess($covered, $coverage->owner_user_id);
                    $covered->whereHas('project', fn (Builder $project) => $this->applyCoverageScope($project, $coverage));
                });
            }
        });
    }

    public function workQueue(User $user): Builder
    {
        return Task::query()->where(function (Builder $query) use ($user): void {
            $query
                ->where('assigned_to', $user->id)
                ->orWhereHas('assignments', fn (Builder $assignment) => $assignment->where('user_id', $user->id));

            foreach ($this->effectiveCoverages($user) as $coverage) {
                $query->orWhere(function (Builder $covered) use ($coverage): void {
                    $covered->where(function (Builder $operational) use ($coverage): void {
                        $operational
                            ->where('assigned_to', $coverage->owner_user_id)
                            ->orWhereHas('assignments', fn (Builder $assignment) => $assignment
                                ->where('user_id', $coverage->owner_user_id))
                            ->orWhereHas('project', fn (Builder $project) => $project
                                ->where('owner_id', $coverage->owner_user_id));
                    });
                    $covered->whereHas('project', fn (Builder $project) => $this->applyCoverageScope($project, $coverage));
                });
            }
        });
    }

    public function delegableProjects(User $user): Builder
    {
        return $this->applyPersonalProjectAccess(Project::query(), $user->id);
    }

    public function coveredUserIds(User $user): array
    {
        return $this->effectiveCoverages($user)
            ->pluck('owner_user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function effectiveCoverages(User $user): Collection
    {
        if (! Schema::hasTable('temporary_coverages')) {
            return collect();
        }

        $query = TemporaryCoverage::query()
            ->effective()
            ->where('delegate_user_id', $user->id);

        if (Schema::hasTable('temporary_coverage_scopes')) {
            $query->with(['owner', 'scopes']);
        } else {
            $query->with('owner');
        }

        return $this->effectiveCoverages[$user->id] ??= $query->get();
    }

    public function coverageForTask(User $user, Task $task): ?TemporaryCoverage
    {
        $task->loadMissing(['project', 'assignments']);

        return $this->effectiveCoverages($user)->first(function (TemporaryCoverage $coverage) use ($task): bool {
            if (! $coverage->coversProject($task->project)) {
                return false;
            }

            return (int) $task->assigned_to === (int) $coverage->owner_user_id
                || (int) $task->project->owner_id === (int) $coverage->owner_user_id
                || $task->assignments->contains('user_id', $coverage->owner_user_id);
        });
    }

    private function applyPersonalProjectAccess(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $query) use ($userId): void {
            $query
                ->where('owner_id', $userId)
                ->orWhereHas('memberships', fn (Builder $membership) => $membership
                    ->where('user_id', $userId)
                    ->where('status', ProjectMember::STATUS_ACTIVE))
                ->orWhereHas('tasks', fn (Builder $task) => $task->where(function (Builder $task) use ($userId): void {
                    $task
                        ->where('assigned_to', $userId)
                        ->orWhereHas('assignments', fn (Builder $assignment) => $assignment->where('user_id', $userId));
                }));
        });
    }

    private function applyPersonalTaskAccess(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $query) use ($userId): void {
            $query
                ->where('assigned_to', $userId)
                ->orWhereHas('assignments', fn (Builder $assignment) => $assignment->where('user_id', $userId))
                ->orWhereHas('project', fn (Builder $project) => $project
                    ->where('owner_id', $userId)
                    ->orWhereHas('memberships', fn (Builder $membership) => $membership
                        ->where('user_id', $userId)
                        ->where('status', ProjectMember::STATUS_ACTIVE)));
        });
    }

    private function applyCoverageScope(Builder $query, TemporaryCoverage $coverage): Builder
    {
        if (! Schema::hasTable('temporary_coverage_scopes')) {
            return $query;
        }

        $coverage->loadMissing('scopes');

        if ($coverage->scopes->isEmpty()) {
            return $query;
        }

        $clientIds = $coverage->scopes->pluck('client_id')->filter()->all();
        $projectIds = $coverage->scopes->pluck('project_id')->filter()->all();

        return $query->where(function (Builder $scope) use ($clientIds, $projectIds): void {
            if ($clientIds !== []) {
                $scope->whereIn('client_id', $clientIds);
            }

            if ($projectIds !== []) {
                $method = $clientIds === [] ? 'whereIn' : 'orWhereIn';
                $scope->{$method}('id', $projectIds);
            }
        });
    }
}
