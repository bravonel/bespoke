<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityIngestionController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\AiSpeechController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MyTasksController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SubtaskController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserCapacityController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Middleware\TrackUserActivity;
use Illuminate\Support\Facades\Route;

Route::view('/', 'site-home')->name('welcome');

Route::get('webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('webhooks.whatsapp.verify');
Route::post('webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive'])->name('webhooks.whatsapp.receive');

Route::middleware(['auth', TrackUserActivity::class])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('profile', fn () => view('profile'))->name('profile');
    Route::post('ai/assistant', AiAssistantController::class)->name('ai.assistant');
    Route::post('ai/assistant/speech', AiSpeechController::class)->name('ai.assistant.speech');
    Route::post('activity/heartbeat', [ActivityIngestionController::class, 'heartbeat'])->name('activity.heartbeat');
    Route::post('activity/ui-events', [ActivityIngestionController::class, 'uiEvents'])->name('activity.ui-events');
    Route::get('activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::get('activity/export', [ActivityController::class, 'export'])->name('activity.export');
    Route::get('activity/print', [ActivityController::class, 'print'])->name('activity.print');
    Route::patch('activity/alerts/{alert}/resolve', [ActivityController::class, 'resolveAlert'])->name('activity.alerts.resolve');

    Route::get('my-tasks', MyTasksController::class)->name('tasks.mine');

    Route::patch('users/{user}/capacity', [UserCapacityController::class, 'update'])->name('users.capacity.update');

    Route::get('collaborators', [CollaboratorController::class, 'index'])->name('collaborators.index');
    Route::post('collaborators', [CollaboratorController::class, 'store'])->name('collaborators.store');
    Route::patch('collaborators/{collaborator}', [CollaboratorController::class, 'update'])->name('collaborators.update');
    Route::patch('collaborators/{collaborator}/deactivate', [CollaboratorController::class, 'deactivate'])->name('collaborators.deactivate');
    Route::patch('collaborators/{collaborator}/activate', [CollaboratorController::class, 'activate'])->name('collaborators.activate');

    Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
    Route::patch('clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

    Route::get('brands', [BrandController::class, 'index'])->name('brands.index');
    Route::post('brands', [BrandController::class, 'store'])->name('brands.store');
    Route::patch('brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
    Route::delete('brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');

    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('projects-export', [ProjectController::class, 'export'])->name('projects.export');
    Route::get('projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::patch('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('projects/{project}/tasks', [TaskController::class, 'store'])->name('projects.tasks.store');
    Route::patch('tasks/{task}/schedule', [TaskController::class, 'updateSchedule'])->name('tasks.update-schedule');
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.update-status');
    Route::patch('tasks/{task}/move', [TaskController::class, 'move'])->name('tasks.move');
    Route::post('tasks/{task}/subtasks', [SubtaskController::class, 'store'])->name('tasks.subtasks.store');
    Route::patch('subtasks/{subtask}', [SubtaskController::class, 'update'])->name('subtasks.update');
});

require __DIR__.'/auth.php';
