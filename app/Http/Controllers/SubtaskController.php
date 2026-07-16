<?php

namespace App\Http\Controllers;

use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubtaskController extends Controller
{
    public function store(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'subtask_title' => ['required', 'string', 'max:255'],
        ]);

        $task->subtasks()->create([
            'title' => $validated['subtask_title'],
            'sort_order' => (int) $task->subtasks()->max('sort_order') + 1,
        ]);

        return to_route('projects.show', $task->project)->with('status', 'Subtarea agregada.');
    }

    public function update(Request $request, Subtask $subtask): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'is_done' => ['required', 'boolean'],
        ]);

        $subtask->update([
            'is_done' => (bool) $validated['is_done'],
            'completed_at' => $validated['is_done'] ? now() : null,
        ]);

        if ($request->expectsJson()) {
            $task = $subtask->task;
            $total = $task->subtasks()->count();
            $completed = $task->subtasks()->where('is_done', true)->count();

            return response()->json([
                'message' => 'Lista actualizada.',
                'subtask' => [
                    'id' => $subtask->id,
                    'is_done' => $subtask->is_done,
                ],
                'progress' => [
                    'completed' => $completed,
                    'total' => $total,
                    'percentage' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
                ],
            ]);
        }

        return to_route('projects.show', $subtask->task->project)->with('status', 'Lista actualizada.');
    }
}
