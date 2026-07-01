<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
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

        return view('dashboard', [
            'summary' => $summary,
            'projectsDueSoon' => $projectsDueSoon,
            'recentTasks' => $recentTasks,
        ]);
    }
}
