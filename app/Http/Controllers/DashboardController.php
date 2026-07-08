<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectWorkload;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $selectedDate = $this->selectedDate($request);
        $areaFilter = $request->string('area')->toString();
        $userFilter = $request->integer('user_id') ?: null;

        $summary = [
            'clients' => Client::count(),
            'brands' => Brand::count(),
            'projects' => Project::count(),
            'active_projects' => Project::whereIn('status', ['active', 'in_review'])->count(),
            'open_tasks' => Task::whereIn('status', ['todo', 'in_progress', 'blocked'])->count(),
            'my_tasks' => Task::where('assigned_to', auth()->id())
                ->whereIn('status', ['todo', 'in_progress', 'blocked'])
                ->count(),
        ];

        $projectsDueSoon = Project::query()
            ->with(['client', 'brand', 'owner'])
            ->whereNotIn('status', ['done'])
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->limit(6)
            ->get();

        $recentTasks = Task::query()
            ->with(['project', 'assignee'])
            ->latest()
            ->limit(8)
            ->get();

        $dailyTasksQuery = Task::query()
            ->with(['assignee', 'project.client', 'project.brand'])
            ->whereDate('planned_for', $selectedDate->toDateString())
            ->orderByRaw("CASE status WHEN 'blocked' THEN 0 WHEN 'in_progress' THEN 1 WHEN 'todo' THEN 2 ELSE 3 END")
            ->orderBy('due_at')
            ->orderBy('id');

        if ($areaFilter !== '') {
            $dailyTasksQuery->whereHas('assignee', fn ($query) => $query->where('area', $areaFilter));
        }

        if ($userFilter) {
            $dailyTasksQuery->where('assigned_to', $userFilter);
        }

        $dailyTasks = $dailyTasksQuery->get();
        $workloadRoles = ProjectWorkload::roleOptions();

        $dailyWorkloadsQuery = ProjectWorkload::query()
            ->with(['user', 'project.client', 'project.brand'])
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

        $dailyActivities = $dailyTasks
            ->map(fn (Task $task) => [
                'type' => 'task',
                'label' => 'Tarea',
                'title' => $task->title,
                'project' => $task->project,
                'assignee' => $task->assignee,
                'user_id' => $task->assigned_to,
                'role' => null,
                'status' => $task->status,
                'estimated_minutes' => $task->estimated_minutes,
                'due_at' => $task->due_at,
                'is_blocked' => $task->status === 'blocked',
                'is_overdue' => $task->status !== 'done' && $task->due_at?->isPast(),
                'missing_estimate' => $task->estimated_minutes === null,
                'task' => $task,
            ])
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
                'due_at' => $workload->project?->due_at,
                'is_blocked' => false,
                'is_overdue' => $workload->project?->status !== 'done' && $workload->project?->due_at?->isPast(),
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
            'areas' => User::query()->whereNotNull('area')->distinct()->orderBy('area')->pluck('area'),
            'users' => User::query()->orderBy('name')->get(),
            'dailyFilters' => [
                'area' => $areaFilter,
                'user_id' => $userFilter,
            ],
            'dailyLoadRows' => $dailyLoadRows,
            'dailySummary' => $dailySummary,
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
}
