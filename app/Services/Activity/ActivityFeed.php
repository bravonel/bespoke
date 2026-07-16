<?php

namespace App\Services\Activity;

use App\Models\ActivityEvent;
use App\Models\Project;
use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Support\Collection;

class ActivityFeed
{
    public function forProject(Project $project, int $limit = 15): Collection
    {
        return ActivityEvent::query()
            ->with('actor')
            ->where('project_id', $project->id)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function forTask(Task $task, int $limit = 10): Collection
    {
        $subtaskIds = $task->subtasks()->pluck('id');

        return ActivityEvent::query()
            ->with('actor')
            ->where(function ($query) use ($task, $subtaskIds): void {
                $query->where(function ($taskQuery) use ($task): void {
                    $taskQuery->where('auditable_type', $task->getMorphClass())
                        ->where('auditable_id', $task->id);
                })->orWhere(function ($subtaskQuery) use ($subtaskIds): void {
                    $subtaskQuery->where('auditable_type', (new Subtask)->getMorphClass())
                        ->whereIn('auditable_id', $subtaskIds);
                });
            })
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
