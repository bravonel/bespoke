<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectWorkload;
use App\Models\Task;
use App\Models\User;
use App\Services\Activity\ActivityFeed;
use App\Support\OperationalLabels;
use App\Support\SimpleXlsxWriter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $query = Project::query()
            ->with(['client', 'brand', 'owner'])
            ->withCount('tasks');

        $filters = $this->projectFilters($request);
        $this->applyProjectFilters($query, $filters);

        return view('projects.index', [
            'projects' => $query
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'clients' => Client::query()->orderBy('name')->get(),
            'brands' => Brand::query()->with('client')->orderBy('name')->get(),
            'owners' => User::query()->active()->orderBy('name')->get(),
            'statuses' => Project::statusOptions(),
            'priorities' => Project::priorityOptions(),
            'stages' => Project::stageOptions(),
            'materialTypes' => Project::materialTypeOptions(),
            'deliveryTypes' => Project::deliveryTypeOptions(),
            'workloadRoles' => ProjectWorkload::roleOptions(),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $query = Project::query()->with(['client', 'brand', 'owner'])->withCount('tasks');
        $this->applyProjectFilters($query, $this->projectFilters($request));

        $rows = [[
            'ODT', 'Cliente', 'Marca', 'Tipo de material', 'Etapa', 'Estatus',
            'Prioridad', 'Responsable', 'Inicio', 'Entrega', 'Tareas',
        ]];

        foreach ($query->latest()->get() as $project) {
            $rows[] = [
                $project->odt_code ?: $project->code,
                $project->client?->name,
                $project->brand?->name ?: 'Sin marca',
                Project::materialTypeLabel($project->project_type),
                OperationalLabels::get($project->current_stage),
                OperationalLabels::get($project->status),
                OperationalLabels::get($project->priority),
                $project->owner?->name ?: 'Sin asignar',
                $project->starts_at?->format('d/m/Y'),
                $project->due_at?->format('d/m/Y'),
                $project->tasks_count,
            ];
        }

        $path = tempnam(sys_get_temp_dir(), 'bespoke-projects-');
        SimpleXlsxWriter::write($rows, $path);

        return response()
            ->download($path, 'proyectos-'.now()->format('Y-m-d').'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    public function create(): View
    {
        return view('projects.create', [
            'clients' => Client::query()->orderBy('name')->get(),
            'brands' => Brand::query()->with('client')->orderBy('name')->get(),
            'owners' => User::query()->active()->orderBy('name')->get(),
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
        $this->normalizeProjectInput($request);

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
            'odt_code' => ['required', 'string', 'max:255', 'unique:projects,odt_code'],
            'project_type' => ['required', 'string', 'max:255'],
            'project_type_other' => ['nullable', 'string', 'max:255'],
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
            'workloads.*.status' => ['nullable', Rule::in(Task::statusOptions())],
        ]);

        $project = Project::create([
            ...collect($validated)->except(['workloads', 'project_type_other'])->all(),
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
        $this->normalizeProjectInput($request);

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
            'odt_code' => ['required', 'string', 'max:255', Rule::unique('projects', 'odt_code')->ignore($project)],
            'project_type' => ['required', 'string', 'max:255'],
            'project_type_other' => ['nullable', 'string', 'max:255'],
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
            'workloads.*.status' => ['nullable', Rule::in(Task::statusOptions())],
        ]);

        $project->update(collect($validated)->except(['workloads', 'project_type_other'])->all());
        $this->syncWorkloads($project, $validated['workloads'] ?? []);

        return to_route('projects.show', $project)->with('status', 'Proyecto actualizado.');
    }

    public function show(Project $project, ActivityFeed $activity): View
    {
        $project->load([
            'client',
            'brand',
            'owner',
            'workloads.user',
            'workloads.task',
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
            'users' => $this->usersAvailableForProject($project),
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
            'recentActivity' => $activity->forProject($project),
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

    private function collaboratorLoadRows(Project $project, array $workloadRoles): Collection
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
            ->whereNull('task_id')
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

    private function usersAvailableForProject(Project $project): Collection
    {
        $relatedUserIds = collect([$project->owner_id])
            ->concat($project->tasks->pluck('assigned_to'))
            ->concat($project->workloads->pluck('user_id'))
            ->filter()
            ->unique()
            ->values();

        return User::query()
            ->where('is_active', true)
            ->when(
                $relatedUserIds->isNotEmpty(),
                fn ($query) => $query->orWhereIn('id', $relatedUserIds)
            )
            ->orderBy('name')
            ->get();
    }

    private function syncWorkloads(Project $project, array $workloads): void
    {
        $existingByRole = $project->workloads()->with('task')->get()->keyBy('role');
        $nextSortOrder = ((int) $project->tasks()->max('sort_order')) + 1;

        foreach (ProjectWorkload::roleOptions() as $role => $label) {
            $row = $workloads[$role] ?? [];
            $userId = filled($row['user_id'] ?? null) ? (int) $row['user_id'] : null;
            $workDate = filled($row['work_date'] ?? null) ? $row['work_date'] : null;
            $estimatedMinutes = $this->estimatedMinutes($row['estimated_hours'] ?? null);
            $notes = filled($row['notes'] ?? null) ? trim((string) $row['notes']) : null;
            $taskStatus = $row['status'] ?? 'todo';
            $existing = $existingByRole->get($role);

            if ($userId === null && $workDate === null && $estimatedMinutes === null && $notes === null) {
                $existing?->task?->delete();
                $existing?->delete();

                continue;
            }

            $workload = $project->workloads()->updateOrCreate(['role' => $role], [
                'user_id' => $userId,
                'work_date' => $workDate,
                'estimated_minutes' => $estimatedMinutes,
                'notes' => $notes,
            ]);

            if ($notes === null) {
                $workload->task?->delete();
                $workload->update(['task_id' => null]);

                continue;
            }

            $task = $workload->task ?: new Task([
                'project_id' => $project->id,
                'priority' => 'normal',
                'sort_order' => $nextSortOrder++,
            ]);
            $task->fill([
                'assigned_to' => $userId,
                'title' => $notes,
                'status' => $taskStatus,
                'planned_for' => $workDate,
                'estimated_minutes' => $estimatedMinutes,
                'due_at' => $project->due_at,
                'completed_at' => $taskStatus === 'done' ? ($task->completed_at ?? now()) : null,
            ])->save();
            $workload->update(['task_id' => $task->id]);
        }
    }

    private function normalizeProjectInput(Request $request): void
    {
        $odt = trim((string) $request->input('odt_code'));
        $materialType = trim((string) $request->input('project_type'));

        if ($materialType === 'otro') {
            $materialType = trim((string) $request->input('project_type_other'));
            if ($materialType === '') {
                throw ValidationException::withMessages([
                    'project_type_other' => 'Escribe el tipo de material.',
                ]);
            }
        }

        $request->merge([
            'name' => $odt,
            'odt_code' => $odt !== '' ? $odt : null,
            'project_type' => $materialType,
        ]);
    }

    /** @return array{client_ids: array<int, int>, brand_ids: array<int, int>, status: string, stage: string, q: string} */
    private function projectFilters(Request $request): array
    {
        $clientIds = collect($request->input('client_ids', []))
            ->push($request->integer('client_id') ?: null)
            ->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $brandIds = collect($request->input('brand_ids', []))
            ->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();

        return [
            'client_ids' => $clientIds,
            'brand_ids' => $brandIds,
            'status' => $request->string('status')->toString(),
            'stage' => $request->string('stage')->toString(),
            'q' => $request->string('q')->trim()->toString(),
        ];
    }

    /** @param array{client_ids: array<int, int>, brand_ids: array<int, int>, status: string, stage: string, q: string} $filters */
    private function applyProjectFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['client_ids'], fn (Builder $query, array $ids) => $query->whereIn('client_id', $ids))
            ->when($filters['brand_ids'], fn (Builder $query, array $ids) => $query->whereIn('brand_id', $ids))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['stage'], fn (Builder $query, string $stage) => $query->where('current_stage', $stage))
            ->when($filters['q'], function (Builder $query, string $search) {
                $query->where(fn (Builder $subquery) => $subquery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('odt_code', 'like', "%{$search}%"));
            });
    }

    private function estimatedMinutes(null|int|float|string $hours): ?int
    {
        if ($hours === null || $hours === '') {
            return null;
        }

        return (int) round(((float) $hours) * 60);
    }
}
