<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Client;
use App\Models\Project;
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

        $dailyLoadRows = $dailyTasks
            ->groupBy(fn (Task $task) => $task->assigned_to ?: 'unassigned')
            ->map(function ($tasks) {
                $assignee = $tasks->first()->assignee;
                $capacity = $assignee?->daily_capacity_minutes ?? 480;
                $estimated = (int) $tasks->sum(fn (Task $task) => $task->estimated_minutes ?? 0);

                return [
                    'assignee' => $assignee,
                    'tasks' => $tasks,
                    'task_count' => $tasks->count(),
                    'estimated_minutes' => $estimated,
                    'capacity_minutes' => $capacity,
                    'capacity_percent' => $capacity > 0 ? min(160, (int) round(($estimated / $capacity) * 100)) : 0,
                    'blocked_count' => $tasks->where('status', 'blocked')->count(),
                    'overdue_count' => $tasks
                        ->filter(fn (Task $task) => $task->status !== 'done' && $task->due_at?->isPast())
                        ->count(),
                    'missing_estimate_count' => $tasks->whereNull('estimated_minutes')->count(),
                ];
            })
            ->sortBy([
                ['overdue_count', 'desc'],
                ['blocked_count', 'desc'],
                ['estimated_minutes', 'desc'],
            ])
            ->values();

        $dailySummary = [
            'tasks' => $dailyTasks->count(),
            'estimated_minutes' => (int) $dailyTasks->sum(fn (Task $task) => $task->estimated_minutes ?? 0),
            'blocked' => $dailyTasks->where('status', 'blocked')->count(),
            'overdue' => $dailyTasks
                ->filter(fn (Task $task) => $task->status !== 'done' && $task->due_at?->isPast())
                ->count(),
            'missing_estimates' => $dailyTasks->whereNull('estimated_minutes')->count(),
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
