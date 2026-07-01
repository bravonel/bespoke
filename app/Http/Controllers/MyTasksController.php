<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Contracts\View\View;

class MyTasksController extends Controller
{
    public function __invoke(): View
    {
        $userId = auth()->id();

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
            ->orderBy('due_at')
            ->orderBy('id')
            ->get();

        $grouped = [
            'blocked'     => $tasks->where('status', 'blocked')->values(),
            'in_progress' => $tasks->where('status', 'in_progress')->values(),
            'todo'        => $tasks->where('status', 'todo')->values(),
            'done'        => $tasks->where('status', 'done')->values(),
        ];

        $overdue = $tasks
            ->whereNotIn('status', ['done'])
            ->filter(fn (Task $t) => $t->due_at?->isPast())
            ->count();

        return view('tasks.mine', [
            'tasks'          => $tasks,
            'grouped'        => $grouped,
            'overdue'        => $overdue,
            'taskStatusMeta' => Task::statusMeta(),
            'taskPriorityMeta' => Task::priorityMeta(),
        ]);
    }
}
