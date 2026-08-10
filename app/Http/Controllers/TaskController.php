<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Access\OperationalAccess;
use App\Services\Activity\ActivityFeed;
use App\Services\Tasks\TaskWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function show(Request $request, Task $task, ActivityFeed $activity, OperationalAccess $access): View
    {
        abort_unless($access->canViewTask($request->user(), $task), 403);
        $task->load([
            'project.client',
            'project.brand',
            'project.owner',
            'assignee',
            'subtasks' => fn ($query) => $query
                ->orderBy('sort_order')
                ->orderBy('id'),
            'comments' => fn ($query) => $query
                ->with('user')
                ->latest()
                ->limit(30),
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
            'users' => User::query()
                ->where('is_active', true)
                ->when(
                    $task->assigned_to,
                    fn ($query) => $query->orWhere('id', $task->assigned_to)
                )
                ->orderBy('name')
                ->get(),
            'recentActivity' => $activity->forTask($task),
            'canManageTask' => $access->canManageTask($request->user(), $task),
            'canOperateTask' => $access->canOperateTask($request->user(), $task),
            'canCommentTask' => true,
        ];

        if ($request->hasHeader('X-Drawer')) {
            return view('tasks._drawer', $viewData);
        }

        return view('tasks.show', $viewData);
    }

    public function update(Request $request, Task $task, OperationalAccess $access): RedirectResponse
    {
        abort_unless($access->canManageTask($request->user(), $task), 403);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'blocked_reason' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'planned_for' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'due_at' => ['nullable', 'date'],
            'priority' => ['required', Rule::in(Task::priorityOptions())],
            'personal_priority' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        $task->update($this->taskAttributes($validated));

        return to_route('projects.show', $task->project)->with('status', 'Tarea actualizada.');
    }

    public function destroy(Request $request, Task $task, OperationalAccess $access): RedirectResponse
    {
        abort_unless($access->canManageTask($request->user(), $task), 403);
        $project = $task->project;
        $task->delete();

        return to_route('projects.show', $project)->with('status', 'Tarea eliminada.');
    }

    public function store(Request $request, Project $project, OperationalAccess $access): RedirectResponse
    {
        abort_unless($access->canManageProject($request->user(), $project), 403);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'blocked_reason' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'status' => ['required', Rule::in(['todo', 'in_progress', 'blocked'])],
            'priority' => ['required', Rule::in(Task::priorityOptions())],
            'personal_priority' => ['nullable', 'integer', 'min:1', 'max:999'],
            'planned_for' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'due_at' => ['nullable', 'date'],
            'subtasks' => ['nullable', 'string'],
        ]);

        $initialAttributes = collect($this->taskAttributes($validated))
            ->except(['subtasks', 'blocked_reason', 'status'])
            ->all();

        $task = $project->tasks()->create([
            ...$initialAttributes,
            'status' => 'todo',
            'sort_order' => (int) $project->tasks()
                ->where('status', $validated['status'])
                ->max('sort_order') + 1,
        ]);

        collect(preg_split('/\r\n|\r|\n/', $validated['subtasks'] ?? ''))
            ->map(fn (?string $line) => trim((string) $line))
            ->filter()
            ->values()
            ->each(fn (string $title, int $index) => $task->subtasks()->create([
                'title' => $title,
                'sort_order' => $index,
            ]));

        if ($validated['status'] !== 'todo') {
            $task->update(app(TaskWorkflow::class)->transition($task, $validated['status'], $validated));
        }

        return to_route('projects.show', $project)->with('status', 'Tarea agregada.');
    }

    public function updateSchedule(Request $request, Task $task, OperationalAccess $access): RedirectResponse
    {
        abort_unless($access->canManageTask($request->user(), $task), 403);
        $validated = $request->validate([
            'planned_for' => ['required', 'date'],
        ]);

        $task->update($validated);

        return back()->with('status', 'Día de carga actualizado.');
    }

    public function updateStatus(Request $request, Task $task, TaskWorkflow $workflow, OperationalAccess $access): RedirectResponse
    {
        abort_unless($access->canOperateTask($request->user(), $task), 403);
        $validated = $request->validate([
            'status' => ['required', Rule::in(Task::statusOptions())],
            'blocked_reason' => ['nullable', 'string', 'max:2000'],
            'return_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $nextStatus = $validated['status'];
        abort_if($nextStatus === 'finalized' && ! $access->canManageTask($request->user(), $task), 403);
        $attributes = $workflow->transition($task, $nextStatus, $validated);

        if ($task->status !== $nextStatus) {
            $attributes['sort_order'] = (int) Task::query()
                ->where('project_id', $task->project_id)
                ->where('status', $nextStatus)
                ->max('sort_order') + 1;
        }

        $task->update($attributes);
        $workflow->syncProjectCompletion($task);

        return to_route('projects.show', $task->project)->with('status', 'Estado de tarea actualizado.');
    }

    public function move(Request $request, Task $task, TaskWorkflow $workflow, OperationalAccess $access): JsonResponse
    {
        abort_unless($access->canOperateTask($request->user(), $task), 403);
        $validated = $request->validate([
            'status' => ['required', Rule::in(Task::statusOptions())],
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['required', 'integer'],
            'source_status' => ['nullable', Rule::in(Task::statusOptions())],
            'source_ordered_ids' => ['nullable', 'array'],
            'source_ordered_ids.*' => ['required', 'integer'],
            'blocked_reason' => ['nullable', 'string', 'max:2000'],
            'return_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $projectId = $task->project_id;
        abort_if($validated['status'] === 'finalized' && ! $access->canManageTask($request->user(), $task), 403);

        $transition = $workflow->transition($task, $validated['status'], $validated);

        if (! $access->canManageTask($request->user(), $task)) {
            $targetIndex = array_search($task->id, array_map('intval', $validated['ordered_ids']), true);
            $task->update([
                ...$transition,
                'sort_order' => $targetIndex === false ? $task->sort_order : $targetIndex,
            ]);
            $workflow->syncProjectCompletion($task);

            return response()->json(['message' => 'Tablero actualizado.']);
        }

        $this->syncColumn(
            $projectId,
            $validated['status'],
            $validated['ordered_ids'],
            $task->id,
            $transition,
        );
        $workflow->syncProjectCompletion($task->refresh());

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

    private function syncColumn(int $projectId, string $status, array $taskIds, ?int $transitioningTaskId = null, array $transition = []): void
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

            $attributes = [
                'status' => $status,
                'sort_order' => $index,
            ];

            if ($task->id === $transitioningTaskId) {
                $attributes = [...$attributes, ...$transition];
            }

            $task->update($attributes);
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
