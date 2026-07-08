<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function show(Request $request, Task $task): View
    {
        $task->load([
            'project.client',
            'project.brand',
            'project.owner',
            'assignee',
            'subtasks' => fn ($query) => $query
                ->orderBy('sort_order')
                ->orderBy('id'),
        ])->loadCount('subtasks')
            ->loadCount([
                'subtasks as completed_subtasks_count' => fn ($query) => $query
                    ->where('is_done', true),
            ]);

        $viewData = [
            'task' => $task,
            'taskStatuses' => Task::statusOptions(),
            'taskStatusMeta' => Task::statusMeta(),
            'taskPriorities' => Task::priorityOptions(),
            'taskPriorityMeta' => Task::priorityMeta(),
            'users' => \App\Models\User::query()->orderBy('name')->get(),
        ];

        if ($request->hasHeader('X-Drawer')) {
            return view('tasks._drawer', $viewData);
        }

        return view('tasks.show', $viewData);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'planned_for' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'due_at' => ['nullable', 'date'],
            'priority' => ['required', Rule::in(Task::priorityOptions())],
        ]);

        $task->update($this->taskAttributes($validated));

        return to_route('projects.show', $task->project)->with('status', 'Tarea actualizada.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $project = $task->project;
        $task->delete();

        return to_route('projects.show', $project)->with('status', 'Tarea eliminada.');
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'status' => ['required', Rule::in(Task::statusOptions())],
            'priority' => ['required', Rule::in(Task::priorityOptions())],
            'planned_for' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'due_at' => ['nullable', 'date'],
            'subtasks' => ['nullable', 'string'],
        ]);

        $task = $project->tasks()->create([
            ...collect($this->taskAttributes($validated))->except('subtasks')->all(),
            'sort_order' => (int) $project->tasks()
                ->where('status', $validated['status'])
                ->max('sort_order') + 1,
            'completed_at' => $validated['status'] === 'done' ? now() : null,
        ]);

        collect(preg_split('/\r\n|\r|\n/', $validated['subtasks'] ?? ''))
            ->map(fn (?string $line) => trim((string) $line))
            ->filter()
            ->values()
            ->each(fn (string $title, int $index) => $task->subtasks()->create([
                'title' => $title,
                'sort_order' => $index,
            ]));

        return to_route('projects.show', $project)->with('status', 'Tarea agregada.');
    }

    public function updateSchedule(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'planned_for' => ['required', 'date'],
        ]);

        $task->update($validated);

        return back()->with('status', 'Día de carga actualizado.');
    }

    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Task::statusOptions())],
        ]);

        $nextStatus = $validated['status'];
        $attributes = [
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'done' ? now() : null,
        ];

        if ($task->status !== $nextStatus) {
            $attributes['sort_order'] = (int) Task::query()
                ->where('project_id', $task->project_id)
                ->where('status', $nextStatus)
                ->max('sort_order') + 1;
        }

        $task->update($attributes);

        return to_route('projects.show', $task->project)->with('status', 'Estado de tarea actualizado.');
    }

    public function move(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Task::statusOptions())],
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['required', 'integer'],
            'source_status' => ['nullable', Rule::in(Task::statusOptions())],
            'source_ordered_ids' => ['nullable', 'array'],
            'source_ordered_ids.*' => ['required', 'integer'],
        ]);

        $projectId = $task->project_id;

        $this->syncColumn(
            $projectId,
            $validated['status'],
            $validated['ordered_ids'],
        );

        if (
            filled($validated['source_status'] ?? null)
            && $validated['source_status'] !== $validated['status']
        ) {
            $this->syncColumn(
                $projectId,
                $validated['source_status'],
                $validated['source_ordered_ids'] ?? [],
            );
        }

        return response()->json([
            'message' => 'Tablero actualizado.',
        ]);
    }

    private function syncColumn(int $projectId, string $status, array $taskIds): void
    {
        $tasks = Task::query()
            ->where('project_id', $projectId)
            ->whereIn('id', $taskIds)
            ->get()
            ->keyBy('id');

        foreach (array_values($taskIds) as $index => $taskId) {
            $task = $tasks->get($taskId);

            if (! $task) {
                continue;
            }

            $task->update([
                'status' => $status,
                'sort_order' => $index,
                'completed_at' => $status === 'done'
                    ? ($task->completed_at ?? now())
                    : null,
            ]);
        }
    }

    private function taskAttributes(array $validated): array
    {
        $attributes = collect($validated)->except('estimated_hours')->all();
        $attributes['estimated_minutes'] = $this->estimatedMinutes($validated['estimated_hours'] ?? null);

        return $attributes;
    }

    private function estimatedMinutes(null|int|float|string $hours): ?int
    {
        if ($hours === null || $hours === '') {
            return null;
        }

        return (int) round(((float) $hours) * 60);
    }
}
