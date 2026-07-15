<?php

namespace App\Services\AI;

use App\Models\Brand;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectWorkload;
use App\Models\Task;
use App\Models\User;
use App\Support\OperationalLabels;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AiContextBuilder
{
    private const OPEN_TASK_STATUSES = ['todo', 'in_progress', 'blocked'];

    public function build(User $user, ?string $contextType = null, ?int $contextId = null, ?string $question = null): array
    {
        $today = today();
        $terms = $this->searchTerms($question);
        $focusedProject = $this->focusedProject($contextType, $contextId);

        $projects = collect()
            ->when($focusedProject, fn (Collection $items) => $items->push($focusedProject))
            ->merge($this->matchedProjects($terms))
            ->merge($this->projectsDueSoon())
            ->unique('id')
            ->take(10)
            ->values();

        $tasks = $this->relevantTasks($projects->pluck('id'), $terms);
        $dailyLoad = $this->dailyLoadRows();
        $sources = $this->sources($projects, $tasks);

        return [
            'context' => [
                'fecha_consulta' => now()->toIso8601String(),
                'usuario' => [
                    'id' => $user->id,
                    'nombre' => $user->name,
                    'area' => $user->area,
                    'puesto' => $user->puesto,
                ],
                'resumen_general' => [
                    'clientes' => Client::count(),
                    'marcas' => Brand::count(),
                    'proyectos_activos' => Project::query()->whereIn('status', ['active', 'in_review'])->count(),
                    'tareas_abiertas' => Task::query()->whereIn('status', self::OPEN_TASK_STATUSES)->count(),
                    'tareas_vencidas' => Task::query()
                        ->whereIn('status', self::OPEN_TASK_STATUSES)
                        ->whereDate('due_at', '<', $today->toDateString())
                        ->count(),
                    'tareas_bloqueadas' => Task::query()->where('status', 'blocked')->count(),
                ],
                'proyectos_relevantes' => $projects->map(fn (Project $project) => $this->projectSnapshot($project))->all(),
                'tareas_relevantes' => $tasks->map(fn (Task $task) => $this->taskSnapshot($task))->all(),
                'carga_del_dia' => $dailyLoad,
            ],
            'sources' => $sources,
            'diagnostics' => [
                'projects_sent' => $projects->count(),
                'tasks_sent' => $tasks->count(),
                'daily_load_rows_sent' => count($dailyLoad),
                'context_type' => $focusedProject ? Project::class : null,
                'context_id' => $focusedProject?->id,
            ],
        ];
    }

    private function focusedProject(?string $contextType, ?int $contextId): ?Project
    {
        if ($contextType !== 'project' || ! $contextId) {
            return null;
        }

        return $this->projectQuery()->find($contextId);
    }

    private function matchedProjects(Collection $terms): Collection
    {
        if ($terms->isEmpty()) {
            return collect();
        }

        return $this->projectQuery()
            ->where(function ($query) use ($terms) {
                $terms->each(function (string $term) use ($query) {
                    $query
                        ->orWhere('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%")
                        ->orWhere('odt_code', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhereHas('client', fn ($subquery) => $subquery->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('brand', fn ($subquery) => $subquery->where('name', 'like', "%{$term}%"));
                });
            })
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->limit(6)
            ->get();
    }

    private function projectsDueSoon(): Collection
    {
        return $this->projectQuery()
            ->whereNotIn('status', ['done'])
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->limit(8)
            ->get();
    }

    private function relevantTasks(Collection $projectIds, Collection $terms): Collection
    {
        return Task::query()
            ->with(['project.client', 'project.brand', 'assignee'])
            ->when($projectIds->isNotEmpty(), fn ($query) => $query->whereIn('project_id', $projectIds))
            ->when($projectIds->isEmpty(), fn ($query) => $query->whereIn('status', self::OPEN_TASK_STATUSES))
            ->when($terms->isNotEmpty(), function ($query) use ($terms) {
                $query->orWhere(function ($subquery) use ($terms) {
                    $terms->each(function (string $term) use ($subquery) {
                        $subquery
                            ->orWhere('title', 'like', "%{$term}%")
                            ->orWhere('description', 'like', "%{$term}%");
                    });
                });
            })
            ->orderByRaw("CASE status WHEN 'blocked' THEN 0 WHEN 'in_progress' THEN 1 WHEN 'todo' THEN 2 ELSE 3 END")
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->limit(18)
            ->get();
    }

    private function dailyLoadRows(): array
    {
        $date = today()->toDateString();
        $tasks = Task::query()
            ->with(['assignee', 'project.client', 'project.brand'])
            ->whereDate('planned_for', $date)
            ->get()
            ->map(fn (Task $task) => [
                'user_id' => $task->assigned_to,
                'assignee' => $task->assignee,
                'minutes' => $task->estimated_minutes,
                'title' => $task->title,
                'project' => $task->project?->name,
                'status' => $task->status,
                'due_at' => $task->due_at,
                'missing_estimate' => $task->estimated_minutes === null,
            ]);

        $workloads = ProjectWorkload::query()
            ->with(['user', 'project.client', 'project.brand'])
            ->whereDate('work_date', $date)
            ->get()
            ->map(fn (ProjectWorkload $workload) => [
                'user_id' => $workload->user_id,
                'assignee' => $workload->user,
                'minutes' => $workload->estimated_minutes,
                'title' => $workload->notes ?: (ProjectWorkload::roleOptions()[$workload->role] ?? 'Carga asignada'),
                'project' => $workload->project?->name,
                'status' => 'carga',
                'due_at' => $workload->project?->due_at,
                'missing_estimate' => $workload->estimated_minutes === null,
            ]);

        return $tasks
            ->concat($workloads)
            ->groupBy(fn (array $row) => $row['user_id'] ?: 'sin_responsable')
            ->map(function (Collection $rows) {
                $assignee = $rows->first()['assignee'] ?? null;
                $capacity = $assignee?->daily_capacity_minutes ?? 480;
                $estimated = (int) $rows->sum(fn (array $row) => $row['minutes'] ?? 0);

                return [
                    'colaborador' => $assignee?->name ?? 'Sin responsable',
                    'area' => $assignee?->area,
                    'puesto' => $assignee?->puesto,
                    'horas_estimadas' => $this->formatMinutes($estimated),
                    'capacidad_dia' => $this->formatMinutes($capacity),
                    'porcentaje_capacidad' => $capacity > 0 ? (int) round(($estimated / $capacity) * 100) : null,
                    'actividades' => $rows->count(),
                    'bloqueadas' => $rows->where('status', 'blocked')->count(),
                    'vencidas' => $rows
                        ->filter(fn (array $row) => $row['due_at'] && $row['status'] !== 'done' && $row['due_at']->isPast())
                        ->count(),
                    'sin_horas' => $rows->where('missing_estimate', true)->count(),
                    'ejemplos' => $rows
                        ->take(4)
                        ->map(fn (array $row) => [
                            'actividad' => $row['title'],
                            'proyecto' => $row['project'],
                            'estatus' => OperationalLabels::get($row['status']),
                            'horas' => $row['minutes'] === null ? 'Sin horas' : $this->formatMinutes((int) $row['minutes']),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->sortByDesc('porcentaje_capacidad')
            ->values()
            ->take(12)
            ->all();
    }

    private function sources(Collection $projects, Collection $tasks): array
    {
        return collect([
            [
                'label' => 'Dashboard operativo',
                'type' => 'dashboard',
                'url' => route('dashboard', [], false),
                'updated_at' => now()->toIso8601String(),
            ],
            [
                'label' => 'Proyectos',
                'type' => 'projects',
                'url' => route('projects.index', [], false),
                'updated_at' => now()->toIso8601String(),
            ],
        ])
            ->merge($projects->map(fn (Project $project) => [
                'label' => 'Proyecto: '.$project->name,
                'type' => 'project',
                'url' => route('projects.show', $project, false),
                'updated_at' => $project->updated_at?->toIso8601String(),
            ]))
            ->merge($tasks->take(8)->map(fn (Task $task) => [
                'label' => 'Tarea: '.$task->title,
                'type' => 'task',
                'url' => route('tasks.show', $task, false),
                'updated_at' => $task->updated_at?->toIso8601String(),
            ]))
            ->unique('url')
            ->values()
            ->all();
    }

    private function projectQuery()
    {
        return Project::query()
            ->with(['client', 'brand', 'owner'])
            ->withCount([
                'tasks',
                'tasks as open_tasks_count' => fn ($query) => $query->whereIn('status', self::OPEN_TASK_STATUSES),
                'tasks as done_tasks_count' => fn ($query) => $query->where('status', 'done'),
                'tasks as overdue_tasks_count' => fn ($query) => $query
                    ->whereIn('status', self::OPEN_TASK_STATUSES)
                    ->whereDate('due_at', '<', today()->toDateString()),
            ]);
    }

    private function projectSnapshot(Project $project): array
    {
        return [
            'id' => $project->id,
            'nombre' => $project->name,
            'odt' => $project->odt_code ?: 'Sin ODT',
            'codigo_interno' => $project->code,
            'cliente' => $project->client?->name,
            'marca' => $project->brand?->name,
            'responsable' => $project->owner?->name ?: 'Sin responsable',
            'estatus' => OperationalLabels::get($project->status),
            'etapa' => OperationalLabels::get($project->current_stage),
            'prioridad' => OperationalLabels::get($project->priority),
            'tipo' => Project::materialTypeLabel($project->project_type),
            'inicio' => $project->starts_at?->toDateString(),
            'entrega' => $project->due_at?->toDateString(),
            'tareas_totales' => $project->tasks_count ?? null,
            'tareas_abiertas' => $project->open_tasks_count ?? null,
            'tareas_listas' => $project->done_tasks_count ?? null,
            'tareas_vencidas' => $project->overdue_tasks_count ?? null,
            'descripcion' => Str::limit(strip_tags((string) $project->description), 500),
            'requisitos_legales' => Str::limit(strip_tags((string) $project->legal_requirements), 400),
            'ligas_referencia' => Str::limit(strip_tags((string) $project->reference_links), 400),
        ];
    }

    private function taskSnapshot(Task $task): array
    {
        return [
            'id' => $task->id,
            'titulo' => $task->title,
            'proyecto' => $task->project?->name,
            'odt' => $task->project?->odt_code ?: $task->project?->code,
            'cliente' => $task->project?->client?->name,
            'marca' => $task->project?->brand?->name,
            'responsable' => $task->assignee?->name ?: 'Sin responsable',
            'estatus' => OperationalLabels::get($task->status),
            'prioridad' => OperationalLabels::get($task->priority),
            'dia_carga' => $task->planned_for?->toDateString(),
            'entrega' => $task->due_at?->toDateString(),
            'horas_estimadas' => $task->estimated_minutes === null ? 'Sin horas' : $this->formatMinutes($task->estimated_minutes),
            'descripcion' => Str::limit(strip_tags((string) $task->description), 400),
        ];
    }

    private function searchTerms(?string $question): Collection
    {
        return Str::of($question ?? '')
            ->lower()
            ->replaceMatches('/[^\pL\pN\s\-]/u', ' ')
            ->explode(' ')
            ->map(fn (string $term) => trim($term))
            ->filter(fn (string $term) => Str::length($term) >= 3)
            ->reject(fn (string $term) => in_array($term, ['que', 'para', 'como', 'con', 'los', 'las', 'del', 'una', 'por'], true))
            ->unique()
            ->take(8)
            ->values();
    }

    private function formatMinutes(int $minutes): string
    {
        return Task::formatEstimatedMinutes($minutes);
    }
}
