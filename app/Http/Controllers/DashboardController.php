<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Client;
use App\Models\ProjectWorkload;
use App\Models\Task;
use App\Models\User;
use App\Services\Access\OperationalAccess;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, OperationalAccess $access): View
    {
        $user = $request->user();
        $selectedDate = $this->selectedDate($request);
        $areaFilter = $request->string('area')->toString();
        $userFilter = $request->integer('user_id') ?: null;

        $summary = [
            'clients' => Client::query()->where('status', '!=', 'archived')->count(),
            'brands' => Brand::query()->where('status', '!=', 'archived')->count(),
            'projects' => (clone $access->projects($user))->where('status', '!=', 'archived')->count(),
            'active_projects' => (clone $access->projects($user))->whereIn('status', ['active', 'in_review'])->count(),
            'open_tasks' => (clone $access->tasks($user))
                ->whereHas('project', fn ($query) => $query->where('status', '!=', 'archived'))
                ->whereIn('status', ['todo', 'in_progress'])
                ->count(),
            'my_tasks' => Task::query()
                ->whereHas('project', fn ($query) => $query->where('status', '!=', 'archived'))
                ->where(fn ($query) => $query
                    ->where('assigned_to', auth()->id())
                    ->orWhereHas('assignments', fn ($assignment) => $assignment->where('user_id', auth()->id())))
                ->whereIn('status', ['todo', 'in_progress'])
                ->count(),
        ];

        $projectsDueSoon = $access->projects($user)
            ->with(['client', 'brand', 'owner'])
            ->where('status', '!=', 'archived')
            ->whereNotIn('status', Task::closedStatuses())
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->limit(6)
            ->get();

        $recentTasks = $access->tasks($user)
            ->with(['project', 'assignee', 'assignments.user'])
            ->whereHas('project', fn ($query) => $query->where('status', '!=', 'archived'))
            ->latest()
            ->limit(8)
            ->get();

        $dailyTasksQuery = $access->tasks($user)
            ->with(['assignee', 'assignments.user', 'project.client', 'project.brand'])
            ->whereHas('project', fn ($query) => $query->where('status', '!=', 'archived'))
            ->where(function ($query) use ($selectedDate): void {
                $query->whereDate('planned_for', $selectedDate->toDateString())
                    ->orWhereHas('assignments', fn ($assignment) => $assignment
                        ->whereDate('work_date', $selectedDate->toDateString()));
            })
            ->orderByRaw("CASE WHEN status = 'todo' AND blocked_reason IS NOT NULL THEN 0 WHEN status = 'in_progress' THEN 1 WHEN status = 'todo' THEN 2 ELSE 3 END")
            ->orderBy('due_at')
            ->orderBy('id');

        if ($areaFilter !== '') {
            $dailyTasksQuery->where(function ($query) use ($areaFilter): void {
                $query->whereHas('assignee', fn ($user) => $user->where('area', $areaFilter))
                    ->orWhereHas('assignments.user', fn ($user) => $user->where('area', $areaFilter));
            });
        }

        if ($userFilter) {
            $dailyTasksQuery->where(fn ($query) => $query
                ->where('assigned_to', $userFilter)
                ->orWhereHas('assignments', fn ($assignment) => $assignment->where('user_id', $userFilter)));
        }

        $dailyTasks = $dailyTasksQuery->get();
        $workloadRoles = ProjectWorkload::roleOptions();

        $dailyWorkloadsQuery = ProjectWorkload::query()
            ->with(['user', 'project.client', 'project.brand'])
            ->whereIn('project_id', $access->projects($user)->where('status', '!=', 'archived')->select('projects.id'))
            ->whereNull('task_id')
            ->whereDate('work_date', $selectedDate->toDateString())
            ->orderBy('role')
            ->orderBy('id');

        if ($areaFilter !== '') {
            $dailyWorkloadsQuery->whereHas('user', fn ($query) => $query->where('area', $areaFilter));
        }

        if ($userFilter) {
            $dailyWorkloadsQuery->where('user_id', $userFilter);
        }

        $dailyWorkloads = $dailyWorkloadsQuery->get();

        $dailyTaskActivities = $dailyTasks->flatMap(function (Task $task) use ($selectedDate, $areaFilter, $userFilter) {
            $assignments = $task->assignments
                ->filter(fn (ProjectWorkload $assignment) => $assignment->work_date?->isSameDay($selectedDate) ?? false)
                ->filter(fn (ProjectWorkload $assignment) => $areaFilter === '' || $assignment->user?->area === $areaFilter)
                ->filter(fn (ProjectWorkload $assignment) => ! $userFilter || $assignment->user_id === $userFilter);

            if ($assignments->isNotEmpty()) {
                return $assignments->map(fn (ProjectWorkload $assignment) => [
                    'type' => 'task',
                    'label' => 'Tarea',
                    'title' => $task->title,
                    'project' => $task->project,
                    'assignee' => $assignment->user,
                    'user_id' => $assignment->user_id,
                    'role' => ProjectWorkload::roleOptions()[$assignment->role] ?? $assignment->role,
                    'status' => $task->status,
                    'estimated_minutes' => $assignment->estimated_minutes,
                    'activity_date' => $assignment->work_date,
                    'due_at' => $task->due_at,
                    'is_blocked' => $task->status === 'todo' && filled($task->blocked_reason),
                    'is_overdue' => $this->isOverdueForSelectedDate($task->due_at, $task->status, $selectedDate),
                    'missing_estimate' => $assignment->estimated_minutes === null,
                    'task' => $task,
                ]);
            }

            if (! $task->planned_for?->isSameDay($selectedDate)) {
                return collect();
            }

            return collect([[
                'type' => 'task',
                'label' => 'Tarea',
                'title' => $task->title,
                'project' => $task->project,
                'assignee' => $task->assignee,
                'user_id' => $task->assigned_to,
                'role' => null,
                'status' => $task->status,
                'estimated_minutes' => $task->estimated_minutes,
                'activity_date' => $task->planned_for,
                'due_at' => $task->due_at,
                'is_blocked' => $task->status === 'todo' && filled($task->blocked_reason),
                'is_overdue' => $this->isOverdueForSelectedDate($task->due_at, $task->status, $selectedDate),
                'missing_estimate' => $task->estimated_minutes === null,
                'task' => $task,
            ]]);
        });

        $dailyActivities = $dailyTaskActivities
            ->concat($dailyWorkloads->map(fn (ProjectWorkload $workload) => [
                'type' => 'workload',
                'label' => 'Carga',
                'title' => $workload->notes ?: ($workloadRoles[$workload->role] ?? 'Carga asignada'),
                'project' => $workload->project,
                'assignee' => $workload->user,
                'user_id' => $workload->user_id,
                'role' => $workloadRoles[$workload->role] ?? $workload->role,
                'status' => null,
                'estimated_minutes' => $workload->estimated_minutes,
                'activity_date' => $workload->work_date,
                'due_at' => $workload->project?->due_at,
                'is_blocked' => false,
                'is_overdue' => $this->isOverdueForSelectedDate($workload->project?->due_at, $workload->project?->status, $selectedDate),
                'missing_estimate' => $workload->estimated_minutes === null,
                'workload' => $workload,
            ]));

        $dailyLoadRows = $dailyActivities
            ->groupBy(fn (array $activity) => $activity['user_id'] ?: 'unassigned')
            ->map(function ($activities) {
                $assignee = $activities->first()['assignee'];
                $capacity = $assignee?->daily_capacity_minutes ?? 480;
                $estimated = (int) $activities->sum(fn (array $activity) => $activity['estimated_minutes'] ?? 0);

                return [
                    'assignee' => $assignee,
                    'activities' => $activities,
                    'task_count' => $activities->count(),
                    'estimated_minutes' => $estimated,
                    'capacity_minutes' => $capacity,
                    'capacity_hours' => $capacity / 60,
                    'capacity_percent' => $capacity > 0 ? min(160, (int) round(($estimated / $capacity) * 100)) : 0,
                    'blocked_count' => $activities->where('is_blocked', true)->count(),
                    'overdue_count' => $activities->where('is_overdue', true)->count(),
                    'missing_estimate_count' => $activities->where('missing_estimate', true)->count(),
                ];
            })
            ->sortBy([
                ['overdue_count', 'desc'],
                ['blocked_count', 'desc'],
                ['estimated_minutes', 'desc'],
            ])
            ->values();

        $dailySummary = [
            'tasks' => $dailyActivities->count(),
            'estimated_minutes' => (int) $dailyActivities->sum(fn (array $activity) => $activity['estimated_minutes'] ?? 0),
            'blocked' => $dailyActivities->where('is_blocked', true)->count(),
            'overdue' => $dailyActivities->where('is_overdue', true)->count(),
            'missing_estimates' => $dailyActivities->where('missing_estimate', true)->count(),
            'over_capacity_users' => $dailyLoadRows
                ->filter(fn (array $row) => $row['estimated_minutes'] > $row['capacity_minutes'])
                ->count(),
        ];

        return view('dashboard', [
            'summary' => $summary,
            'projectsDueSoon' => $projectsDueSoon,
            'recentTasks' => $recentTasks,
            'selectedDate' => $selectedDate,
            'areas' => User::query()->active()->whereNotNull('area')->distinct()->orderBy('area')->pluck('area'),
            'users' => User::query()->active()->orderBy('name')->get(),
            'dailyFilters' => [
                'area' => $areaFilter,
                'user_id' => $userFilter,
            ],
            'dailyLoadRows' => $dailyLoadRows,
            'dailySummary' => $dailySummary,
            'canManageCapacity' => $access->canManageCapacity($user),
        ]);
    }

    private function selectedDate(Request $request): CarbonImmutable
    {
        $date = $request->string('date')->toString();

        try {
            return $date === ''
                ? CarbonImmutable::today()
                : CarbonImmutable::parse($date)->startOfDay();
        } catch (\Throwable) {
            return CarbonImmutable::today();
        }
    }

    private function isOverdueForSelectedDate(?CarbonInterface $dueAt, ?string $status, CarbonImmutable $selectedDate): bool
    {
        if (! $dueAt || in_array($status, Task::inactiveStatuses(), true)) {
            return false;
        }

        return CarbonImmutable::instance($dueAt)->startOfDay()->lt($selectedDate->startOfDay());
    }
}
