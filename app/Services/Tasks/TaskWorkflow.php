<?php

namespace App\Services\Tasks;

use App\Models\Task;
use Illuminate\Validation\ValidationException;

class TaskWorkflow
{
    public function transition(Task $task, string $nextStatus, array $context = []): array
    {
        $currentStatus = $task->status;

        if ($currentStatus === $nextStatus) {
            return [];
        }

        $blockedReason = trim((string) ($context['blocked_reason'] ?? ''));
        $returnReason = trim((string) ($context['return_reason'] ?? ''));

        if ($currentStatus === 'in_progress' && $nextStatus === 'todo' && $blockedReason === '') {
            throw ValidationException::withMessages([
                'blocked_reason' => 'Explica qué impide avanzar antes de regresar la tarea a Por hacer.',
            ]);
        }

        if (in_array($currentStatus, ['done', 'finalized'], true)
            && ! in_array($nextStatus, ['done', 'finalized'], true)
            && $returnReason === '') {
            throw ValidationException::withMessages([
                'return_reason' => 'Indica por qué se devuelve la tarea y qué debe corregirse.',
            ]);
        }

        if ($nextStatus === 'done' && $task->subtasks()->where('is_done', false)->exists()) {
            throw ValidationException::withMessages([
                'status' => 'Completa todos los puntos de la lista antes de entregar.',
            ]);
        }

        if ($nextStatus === 'finalized' && $currentStatus !== 'done') {
            throw ValidationException::withMessages([
                'status' => 'La tarea debe estar entregada antes de poder finalizarla.',
            ]);
        }

        $attributes = [
            'status' => $nextStatus,
            'blocked_reason' => $nextStatus === 'todo'
                ? ($blockedReason !== '' ? $blockedReason : $task->blocked_reason)
                : null,
        ];

        if ($returnReason !== '') {
            $attributes['return_reason'] = $returnReason;
        }

        if ($nextStatus === 'in_progress' && $task->started_at === null) {
            $attributes['started_at'] = now();
        }

        if ($nextStatus === 'done') {
            $attributes['delivered_at'] = now();
            $attributes['finalized_at'] = null;
            $attributes['completed_at'] = null;
        } elseif ($nextStatus === 'finalized') {
            $attributes['finalized_at'] = now();
            $attributes['completed_at'] = now();
        } else {
            $attributes['delivered_at'] = null;
            $attributes['finalized_at'] = null;
            $attributes['completed_at'] = null;
        }

        return $attributes;
    }

    public function syncProjectCompletion(Task $task): void
    {
        $project = $task->project;
        $hasTasks = $project->tasks()->exists();
        $allFinalized = $hasTasks && ! $project->tasks()->where('status', '!=', 'finalized')->exists();

        if ($allFinalized && $project->status !== 'done') {
            $project->update([
                'status' => 'done',
                'completed_at' => now(),
            ]);

            return;
        }

        if (! $allFinalized && $project->status === 'done' && $task->status !== 'finalized') {
            $project->update([
                'status' => 'active',
                'completed_at' => null,
            ]);
        }
    }
}
