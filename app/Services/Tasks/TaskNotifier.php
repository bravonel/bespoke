<?php

namespace App\Services\Tasks;

use App\Models\Task;
use App\Models\TemporaryCoverage;
use App\Models\User;
use App\Notifications\PersonalAttentionNotification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TaskNotifier
{
    public function assigned(Task $task, User $actor, array $userIds): void
    {
        $this->send(
            $task,
            $actor,
            $userIds,
            'task.assigned',
            'Nueva tarea asignada',
            $task->title,
        );
    }

    public function statusChanged(Task $task, User $actor, string $previousStatus): void
    {
        if ($previousStatus === $task->status) {
            return;
        }

        $label = Task::statusMeta()[$task->status]['label'] ?? $task->status;
        $title = $task->status === 'todo' ? 'Tarea devuelta a Por hacer' : 'Estado de tarea actualizado';

        $this->send(
            $task,
            $actor,
            $this->recipients($task),
            'task.status_changed',
            $title,
            "{$task->title} · {$label}",
        );
    }

    public function commented(Task $task, User $actor): void
    {
        $commenterIds = $task->comments()
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->all();

        $this->send(
            $task,
            $actor,
            [...$this->recipients($task), ...$commenterIds],
            'task.commented',
            'Nuevo comentario',
            "{$actor->name} comentó en {$task->title}",
        );
    }

    private function recipients(Task $task): array
    {
        $task->loadMissing(['assignments', 'project']);

        return collect($task->assignedUserIds())
            ->push($task->project?->owner_id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function send(
        Task $task,
        User $actor,
        array $userIds,
        string $kind,
        string $title,
        string $message,
    ): void {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $userIds = collect($userIds)->filter()->unique();

        if (Schema::hasTable('temporary_coverages')) {
            $coverages = TemporaryCoverage::query()
                ->effective()
                ->whereIn('owner_user_id', $userIds)
                ->when(
                    Schema::hasTable('temporary_coverage_scopes'),
                    fn ($query) => $query->with('scopes')
                )
                ->get();

            $userIds->push(...$coverages
                ->filter(fn (TemporaryCoverage $coverage) => $coverage->coversProject($task->project))
                ->pluck('delegate_user_id'));
        }

        $recipients = User::query()
            ->active()
            ->whereIn('id', $userIds->unique())
            ->whereKeyNot($actor->id)
            ->get();

        $recipients->each(fn (User $user) => $user->notify(new PersonalAttentionNotification([
            'kind' => $kind,
            'title' => $title,
            'message' => Str::limit($message, 240),
            'url' => route('tasks.show', $task, false),
            'task_id' => $task->id,
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
        ])));
    }
}
