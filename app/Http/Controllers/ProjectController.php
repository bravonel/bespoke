<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $query = Project::query()
            ->with(['client', 'brand', 'owner'])
            ->withCount('tasks');

        if ($client = $request->integer('client_id', 0)) {
            $query->where('client_id', $client);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($stage = $request->string('stage')->toString()) {
            $query->where('current_stage', $stage);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($subquery) use ($search) {
                $subquery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('odt_code', 'like', "%{$search}%");
            });
        }

        return view('projects.index', [
            'projects' => $query->latest()->get(),
            'clients' => Client::query()->orderBy('name')->get(),
            'brands' => Brand::query()->with('client')->orderBy('name')->get(),
            'owners' => User::query()->orderBy('name')->get(),
            'statuses' => Project::statusOptions(),
            'priorities' => Project::priorityOptions(),
            'stages' => Project::stageOptions(),
            'filters' => $request->only(['client_id', 'status', 'stage', 'q']),
        ]);
    }

    public function create(): View
    {
        return view('projects.create', [
            'clients' => Client::query()->orderBy('name')->get(),
            'brands' => Brand::query()->with('client')->orderBy('name')->get(),
            'owners' => User::query()->orderBy('name')->get(),
            'statuses' => Project::statusOptions(),
            'priorities' => Project::priorityOptions(),
            'stages' => Project::stageOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'odt_code' => filled($request->input('odt_code')) ? trim((string) $request->input('odt_code')) : null,
        ]);

        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'brand_id' => [
                'nullable',
                Rule::exists('brands', 'id')->where(
                    fn ($query) => $query->where('client_id', $request->integer('client_id'))
                ),
            ],
            'owner_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'odt_code' => ['nullable', 'string', 'max:255', 'unique:projects,odt_code'],
            'project_type' => ['required', 'string', 'max:255'],
            'priority' => ['required', Rule::in(Project::priorityOptions())],
            'status' => ['required', Rule::in(Project::statusOptions())],
            'current_stage' => ['required', Rule::in(Project::stageOptions())],
            'description' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $project = Project::create([
            ...$validated,
            'owner_id' => $validated['owner_id'] ?? $request->user()->id,
            'code' => 'BSP-'.Str::upper(Str::random(6)),
        ]);

        return to_route('projects.show', $project)->with('status', 'Proyecto creado.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return to_route('projects.index')->with('status', 'Proyecto eliminado.');
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $request->merge([
            'odt_code' => filled($request->input('odt_code')) ? trim((string) $request->input('odt_code')) : null,
        ]);

        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'brand_id' => [
                'nullable',
                Rule::exists('brands', 'id')->where(
                    fn ($query) => $query->where('client_id', $request->integer('client_id'))
                ),
            ],
            'owner_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'odt_code' => ['nullable', 'string', 'max:255', Rule::unique('projects', 'odt_code')->ignore($project)],
            'project_type' => ['required', 'string', 'max:255'],
            'priority' => ['required', Rule::in(Project::priorityOptions())],
            'status' => ['required', Rule::in(Project::statusOptions())],
            'current_stage' => ['required', Rule::in(Project::stageOptions())],
            'description' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $project->update($validated);

        return to_route('projects.show', $project)->with('status', 'Proyecto actualizado.');
    }

    public function show(Project $project): View
    {
        $project->load([
            'client',
            'brand',
            'owner',
            'tasks' => fn ($query) => $query
                ->with('assignee')
                ->with([
                    'subtasks' => fn ($subquery) => $subquery
                        ->orderBy('sort_order')
                        ->orderBy('id'),
                ])
                ->withCount('subtasks')
                ->withCount([
                    'subtasks as completed_subtasks_count' => fn ($subquery) => $subquery
                        ->where('is_done', true),
                ])
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);

        $taskGroups = collect(Task::statusMeta())
            ->mapWithKeys(fn (array $meta, string $status) => [
                $status => $project->tasks
                    ->where('status', $status)
                    ->values(),
            ]);

        $doneTasks = $project->tasks->where('status', 'done')->count();
        $openSubtasks = $project->tasks->sum(
            fn (Task $task) => $task->subtasks_count - $task->completed_subtasks_count
        );
        $overdueTasks = $project->tasks
            ->filter(fn (Task $task) => $task->status !== 'done' && $task->due_at?->isPast())
            ->count();
        $plannedMinutes = (int) $project->tasks
            ->whereNotIn('status', ['done'])
            ->sum(fn (Task $task) => $task->estimated_minutes ?? 0);
        $completionRate = $project->tasks->isEmpty()
            ? 0
            : (int) round(($doneTasks / $project->tasks->count()) * 100);

        return view('projects.show', [
            'project' => $project,
            'users' => User::query()->orderBy('name')->get(),
            'clients' => Client::query()->orderBy('name')->get(),
            'brands' => Brand::query()->with('client')->orderBy('name')->get(),
            'taskGroups' => $taskGroups,
            'taskStatuses' => Task::statusOptions(),
            'taskStatusMeta' => Task::statusMeta(),
            'taskPriorities' => Task::priorityOptions(),
            'taskPriorityMeta' => Task::priorityMeta(),
            'projectStatuses' => Project::statusOptions(),
            'projectPriorities' => Project::priorityOptions(),
            'projectStages' => Project::stageOptions(),
            'boardSummary' => [
                'total_tasks' => $project->tasks->count(),
                'done_tasks' => $doneTasks,
                'open_subtasks' => $openSubtasks,
                'overdue_tasks' => $overdueTasks,
                'unassigned_tasks' => $project->tasks->whereNull('assigned_to')->count(),
                'planned_minutes' => $plannedMinutes,
                'missing_estimates' => $project->tasks
                    ->whereNotIn('status', ['done'])
                    ->whereNull('estimated_minutes')
                    ->count(),
                'completion_rate' => $completionRate,
            ],
        ]);
    }
}
