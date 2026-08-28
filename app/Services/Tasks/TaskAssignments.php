<?php

namespace App\Services\Tasks;

use App\Models\ProjectWorkload;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TaskAssignments
{
    public function sync(Task $task, array $participants, ?string $fixedRole = null, ?string $notes = null): void
    {
        $rows = collect($participants)
            ->map(fn (array $row) => $this->normalizeRow($row, $fixedRole, $notes))
            ->filter(fn (array $row) => $row['user_id'] !== null)
            ->values();

        $duplicateUser = $rows->pluck('user_id')->duplicates()->first();

        if ($duplicateUser !== null) {
            throw ValidationException::withMessages([
                'assignments' => 'Cada persona puede aparecer una sola vez en la tarjeta compartida.',
            ]);
        }

        $task->assignments()->delete();

        foreach ($rows as $row) {
            $task->assignments()->create([
                ...$row,
                'project_id' => $task->project_id,
            ]);
        }

        $this->syncLegacySummary($task, $rows);
    }

    public function participants(Task $task): Collection
    {
        $task->loadMissing('assignments.user');

        if ($task->assignments->isNotEmpty()) {
            return $task->assignments->map(fn (ProjectWorkload $assignment) => [
                'id' => $assignment->id,
                'user_id' => $assignment->user_id,
                'role' => $assignment->role,
                'work_date' => $assignment->work_date?->format('Y-m-d'),
                'estimated_hours' => $assignment->estimated_minutes === null
                    ? null
                    : $assignment->estimated_minutes / 60,
                'personal_priority' => $assignment->personal_priority,
            ])->values();
        }

        if (! $task->assigned_to) {
            return collect();
        }

        return collect([[
            'user_id' => $task->assigned_to,
            'role' => $this->roleFor($task->assignee),
            'work_date' => $task->planned_for?->format('Y-m-d'),
            'estimated_hours' => $task->estimated_minutes === null ? null : $task->estimated_minutes / 60,
            'personal_priority' => $task->personal_priority,
        ]]);
    }

    private function normalizeRow(array $row, ?string $fixedRole, ?string $notes): array
    {
        $userId = filled($row['user_id'] ?? null) ? (int) $row['user_id'] : null;
        $user = $userId ? User::query()->find($userId) : null;

        return [
            'user_id' => $userId,
            'role' => $fixedRole ?: ($row['role'] ?? $this->roleFor($user)),
            'work_date' => filled($row['work_date'] ?? null) ? $row['work_date'] : null,
            'estimated_minutes' => $this->estimatedMinutes($row['estimated_hours'] ?? null),
            'personal_priority' => filled($row['personal_priority'] ?? null)
                ? (int) $row['personal_priority']
                : null,
            'notes' => $notes,
        ];
    }

    private function syncLegacySummary(Task $task, Collection $rows): void
    {
        $first = $rows->first();
        $minutes = $rows->contains(fn (array $row) => $row['estimated_minutes'] !== null)
            ? (int) $rows->sum(fn (array $row) => $row['estimated_minutes'] ?? 0)
            : null;

        $task->forceFill([
            'assigned_to' => $first['user_id'] ?? null,
            'planned_for' => $first['work_date'] ?? null,
            'estimated_minutes' => $minutes,
            'personal_priority' => $rows->pluck('personal_priority')->filter()->min(),
        ])->save();
    }

    private function roleFor(?User $user): string
    {
        return match ($user?->area) {
            'Cuentas' => 'accounts',
            'Medical', 'Médico' => 'medical',
            'Copy', 'Redacción' => 'copy',
            'Social Media', 'Redes sociales' => 'social_media',
            'Cliente' => 'client',
            default => 'design',
        };
    }

    private function estimatedMinutes(null|int|float|string $hours): ?int
    {
        if ($hours === null || $hours === '') {
            return null;
        }

        return (int) round(((float) $hours) * 60);
    }
}
