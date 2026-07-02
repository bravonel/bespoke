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
            ->where('assigned_to', $userId)
            ->with([
                'project.client',
                'project.brand',
                'subtasks' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
            ])
            ->withCount('subtasks')
            ->withCount([
                'subtasks as completed_subtasks_count' => fn ($q) => $q->where('is_done', true),
            ])
            ->orderByRaw("CASE status WHEN 'blocked' THEN 0 WHEN 'in_progress' THEN 1 WHEN 'todo' THEN 2 ELSE 3 END")
            ->orderByRaw('planned_for is null')
            ->orderBy('planned_for')
            ->orderBy('due_at')
            ->orderBy('id')
            ->get();

        $openTasks = $tasks->whereNotIn('status', ['done']);

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
            'done' => $tasks->where('status', 'done')->values(),
        ];

        $overdue = $tasks
            ->whereNotIn('status', ['done'])
            ->filter(fn (Task $t) => $t->due_at?->isPast())
            ->count();

        return view('tasks.mine', [
            'tasks'          => $tasks,
            'sections'       => $sections,
            'overdue'        => $overdue,
            'todayEstimatedMinutes' => (int) $sections['today']->sum(fn (Task $task) => $task->estimated_minutes ?? 0),
            'taskStatusMeta' => Task::statusMeta(),
            'taskPriorityMeta' => Task::priorityMeta(),
        ]);
    }
}
