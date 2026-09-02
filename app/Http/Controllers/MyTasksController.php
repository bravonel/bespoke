<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\Access\OperationalAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class MyTasksController extends Controller
{
    public function __invoke(OperationalAccess $access): View
    {
        $user = auth()->user();
        $coveredUserIds = $access->coveredUserIds($user);
        $operationalUserIds = [$user->id, ...$coveredUserIds];
        $today = today();

        $tasks = $access->workQueue($user)
            ->whereHas('project', fn ($query) => $query->where('status', '!=', 'archived'))
            ->with([
                'project.client',
                'project.brand',
                'project.owner',
                'assignments' => fn ($query) => $query
                    ->whereIn('user_id', $operationalUserIds)
                    ->orderByRaw('user_id = ? desc', [$user->id])
                    ->with('user'),
                'subtasks' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
            ])
            ->withCount('subtasks')
            ->withCount([
                'subtasks as completed_subtasks_count' => fn ($q) => $q->where('is_done', true),
            ])
            ->orderByRaw("CASE WHEN status = 'todo' AND blocked_reason IS NOT NULL THEN 0 WHEN status = 'in_progress' THEN 1 WHEN status = 'todo' THEN 2 ELSE 3 END")
            ->orderByRaw('personal_priority is null')
            ->orderBy('personal_priority')
            ->orderByRaw('planned_for is null')
            ->orderBy('planned_for')
            ->orderBy('due_at')
            ->orderBy('id')
            ->get()
            ->each(function (Task $task) use ($access, $user): void {
                $assignment = $task->assignments->first();
                $isPersonal = (int) $task->assigned_to === (int) $user->id
                    || $task->assignments->contains('user_id', $user->id);
                $coverage = $isPersonal ? null : $access->coverageForTask($user, $task);
                $task->setAttribute('coverage_name', $coverage?->owner?->name);

                if (! $assignment) {
                    return;
                }

                $task->setAttribute('planned_for', $assignment->work_date);
                $task->setAttribute('estimated_minutes', $assignment->estimated_minutes);
                $task->setAttribute('personal_priority', $assignment->personal_priority);
            });

        $openTasks = $tasks->whereNotIn('status', Task::inactiveStatuses());

        $sections = [
            'today' => $openTasks
                ->filter(fn (Task $task) => $task->planned_for?->isSameDay($today))
                ->values(),
            'upcoming' => $openTasks
                ->filter(fn (Task $task) => $task->planned_for && $task->planned_for->greaterThan($today))
                ->values(),
            'unscheduled' => $openTasks
                ->filter(fn (Task $task) => $task->planned_for === null)
                ->values(),
            'done' => $tasks->whereIn('status', Task::inactiveStatuses())->values(),
        ];

        $overdue = $tasks
            ->whereNotIn('status', Task::inactiveStatuses())
            ->filter(fn (Task $t) => $t->due_at?->isPast())
            ->count();

        $notificationsAvailable = Schema::hasTable('notifications');

        return view('tasks.mine', [
            'tasks' => $tasks,
            'sections' => $sections,
            'overdue' => $overdue,
            'notifications' => $notificationsAvailable ? $user->notifications()->latest()->limit(12)->get() : collect(),
            'unreadCount' => $notificationsAvailable ? $user->unreadNotifications()->count() : 0,
            'todayEstimatedMinutes' => (int) $sections['today']->sum(fn (Task $task) => $task->estimated_minutes ?? 0),
            'taskStatusMeta' => Task::statusMeta(),
            'taskPriorityMeta' => Task::priorityMeta(),
        ]);
    }
}
