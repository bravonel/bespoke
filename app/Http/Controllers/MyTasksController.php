<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Contracts\View\View;

class MyTasksController extends Controller
{
    public function __invoke(): View
    {
        $userId = auth()->id();
        $today = today();

        $tasks = Task::query()
            ->whereHas('project', fn ($query) => $query->where('status', '!=', 'archived'))
            ->where(fn ($query) => $query
                ->where('assigned_to', $userId)
                ->orWhereHas('assignments', fn ($assignment) => $assignment->where('user_id', $userId)))
            ->with([
                'project.client',
                'project.brand',
                'assignments' => fn ($query) => $query->where('user_id', $userId),
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
            ->each(function (Task $task): void {
                $assignment = $task->assignments->first();

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

        return view('tasks.mine', [
            'tasks' => $tasks,
            'sections' => $sections,
            'overdue' => $overdue,
            'todayEstimatedMinutes' => (int) $sections['today']->sum(fn (Task $task) => $task->estimated_minutes ?? 0),
            'taskStatusMeta' => Task::statusMeta(),
            'taskPriorityMeta' => Task::priorityMeta(),
        ]);
    }
}
