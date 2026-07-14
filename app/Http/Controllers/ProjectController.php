<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectWorkload;
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
            'materialTypes' => Project::materialTypeOptions(),
            'deliveryTypes' => Project::deliveryTypeOptions(),
            'workloadRoles' => ProjectWorkload::roleOptions(),
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
            'materialTypes' => Project::materialTypeOptions(),
            'deliveryTypes' => Project::deliveryTypeOptions(),
            'workloadRoles' => ProjectWorkload::roleOptions(),
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
            'delivery_type' => ['nullable', Rule::in(array_keys(Project::deliveryTypeOptions()))],
            'target_audience' => ['nullable', 'string', 'max:255'],
            'material_size' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', Rule::in(Project::priorityOptions())],
            'status' => ['required', Rule::in(Project::statusOptions())],
            'current_stage' => ['required', Rule::in(Project::stageOptions())],
            'description' => ['nullable', 'string'],
            'legal_requirements' => ['nullable', 'string'],
            'reference_links' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'workloads' => ['nullable', 'array'],
            'workloads.*.user_id' => ['nullable', 'exists:users,id'],
            'workloads.*.work_date' => ['nullable', 'date'],
            'workloads.*.estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'workloads.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $project = Project::create([
            ...collect($validated)->except('workloads')->all(),
            'owner_id' => $validated['owner_id'] ?? $request->user()->id,
            'code' => 'BSP-'.Str::upper(Str::random(6)),
        ]);

        $this->syncWorkloads($project, $validated['workloads'] ?? []);

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
            'delivery_type' => ['nullable', Rule::in(array_keys(Project::deliveryTypeOptions()))],
            'target_audience' => ['nullable', 'string', 'max:255'],
            'material_size' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', Rule::in(Project::priorityOptions())],
            'status' => ['required', Rule::in(Project::statusOptions())],
            'current_stage' => ['required', Rule::in(Project::stageOptions())],
            'description' => ['nullable', 'string'],
            'legal_requirements' => ['nullable', 'string'],
            'reference_links' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'workloads' => ['nullable', 'array'],
            'workloads.*.user_id' => ['nullable', 'exists:users,id'],
            'workloads.*.work_date' => ['nullable', 'date'],
            'workloads.*.estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'workloads.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $project->update(collect($validated)->except('workloads')->all());
        $this->syncWorkloads($project, $validated['workloads'] ?? []);

        return to_route('projects.show', $project)->with('status', 'Proyecto actualizado.');
    }

    public function show(Project $project): View
    {
        $project->load([
            'client',
            'brand',
            'owner',
            'workloads.user',
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
        $workloadRoles = ProjectWorkload::roleOptions();
        $collaboratorLoadRows = $this->collaboratorLoadRows($project, $workloadRoles);

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
            'materialTypes' => Project::materialTypeOptions(),
            'deliveryTypes' => Project::deliveryTypeOptions(),
            'workloadRoles' => $workloadRoles,
            'collaboratorLoadRows' => $collaboratorLoadRows,
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

    private function collaboratorLoadRows(Project $project, array $workloadRoles): \Illuminate\Support\Collection
    {
        $taskRows = $project->tasks
            ->where('status', '!=', 'done')
            ->map(fn (Task $task) => [
                'kind' => 'task',
                'user_id' => $task->assigned_to,
                'user' => $task->assignee,
                'minutes' => $task->estimated_minutes,
                'role' => null,
                'missing_estimate' => $task->estimated_minutes === null,
            ]);

        $workloadRows = $project->workloads
            ->map(fn (ProjectWorkload $workload) => [
                'kind' => 'workload',
                'user_id' => $workload->user_id,
                'user' => $workload->user,
                'minutes' => $workload->estimated_minutes,
                'role' => $workloadRoles[$workload->role] ?? $workload->role,
                'missing_estimate' => $workload->estimated_minutes === null,
            ]);

        return $taskRows
            ->concat($workloadRows)
            ->groupBy(fn (array $row) => $row['user_id'] ?: 'unassigned')
            ->map(function ($rows) {
                $firstAssignedRow = $rows->first(fn (array $row) => $row['user'] !== null);
                $user = $firstAssignedRow['user'] ?? null;
                $taskRows = $rows->where('kind', 'task');
                $workloadRows = $rows->where('kind', 'workload');
                $taskMinutes = (int) $taskRows->sum(fn (array $row) => $row['minutes'] ?? 0);
                $workloadMinutes = (int) $workloadRows->sum(fn (array $row) => $row['minutes'] ?? 0);

                return [
                    'user' => $user,
                    'task_count' => $taskRows->count(),
                    'workload_count' => $workloadRows->count(),
                    'task_minutes' => $taskMinutes,
                    'workload_minutes' => $workloadMinutes,
                    'total_minutes' => $taskMinutes + $workloadMinutes,
                    'missing_estimates' => $rows->where('missing_estimate', true)->count(),
                    'roles' => $workloadRows
                        ->pluck('role')
                        ->filter()
                        ->unique()
                        ->values(),
                ];
            })
            ->sortBy([
                ['total_minutes', 'desc'],
                ['missing_estimates', 'desc'],
            ])
            ->values();
    }

    private function syncWorkloads(Project $project, array $workloads): void
    {
        $project->workloads()->delete();

        foreach (ProjectWorkload::roleOptions() as $role => $label) {
            $row = $workloads[$role] ?? [];
            $userId = filled($row['user_id'] ?? null) ? (int) $row['user_id'] : null;
            $workDate = filled($row['work_date'] ?? null) ? $row['work_date'] : null;
            $estimatedMinutes = $this->estimatedMinutes($row['estimated_hours'] ?? null);
            $notes = filled($row['notes'] ?? null) ? trim((string) $row['notes']) : null;

            if ($userId === null && $workDate === null && $estimatedMinutes === null && $notes === null) {
                continue;
            }

            $project->workloads()->create([
                'user_id' => $userId,
                'role' => $role,
                'work_date' => $workDate,
                'estimated_minutes' => $estimatedMinutes,
                'notes' => $notes,
            ]);
        }
    }

    private function estimatedMinutes(null|int|float|string $hours): ?int
    {
        if ($hours === null || $hours === '') {
            return null;
        }

        return (int) round(((float) $hours) * 60);
    }
}
