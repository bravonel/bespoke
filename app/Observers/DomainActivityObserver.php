<?php

namespace App\Observers;

use App\Models\Brand;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectWorkload;
use App\Models\Subtask;
use App\Models\Task;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class DomainActivityObserver
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function created(Model $model): void
    {
        if (! $this->ready()) {
            return;
        }

        $this->audit->record($this->createdEvent($model), $model, metadata: [
            'fields_set' => collect(array_keys($model->getAttributes()))
                ->reject(fn (string $field) => in_array($field, ['created_at', 'updated_at'], true))
                ->values()
                ->all(),
        ], channel: $this->channel());
    }

    public function updated(Model $model): void
    {
        if (! $this->ready()) {
            return;
        }

        $after = collect($model->getChanges())->except('updated_at')->all();

        if ($after === []) {
            return;
        }

        $before = collect(array_keys($after))
            ->mapWithKeys(fn (string $field) => [$field => $model->getOriginal($field)])
            ->all();

        $this->audit->recordChange(
            $this->updatedEvent($model, array_keys($after)),
            $model,
            $before,
            $after,
            metadata: ['source' => $this->source()],
            channel: $this->channel(),
        );
    }

    public function deleted(Model $model): void
    {
        if (! $this->ready()) {
            return;
        }

        $this->audit->record($this->deletedEvent($model), $model, metadata: [
            'label' => $model->getAttribute('name')
                ?? $model->getAttribute('title')
                ?? $model->getAttribute('code'),
        ], channel: $this->channel());
    }

    private function createdEvent(Model $model): string
    {
        return match (true) {
            $model instanceof ProjectMember => 'project.member_added',
            $model instanceof ProjectWorkload => 'project.workload_added',
            default => $this->prefix($model).'.created',
        };
    }

    private function updatedEvent(Model $model, array $fields): string
    {
        return match (true) {
            $model instanceof Task && in_array('status', $fields, true) => 'task.status_changed',
            $model instanceof Task && in_array('assigned_to', $fields, true) => 'task.assigned',
            $model instanceof Task && array_intersect(['planned_for', 'due_at'], $fields) !== [] => 'task.schedule_changed',
            $model instanceof Task && in_array('sort_order', $fields, true) => 'task.reordered',
            $model instanceof Subtask && in_array('is_done', $fields, true) => $model->is_done
                ? 'subtask.completed'
                : 'subtask.reopened',
            $model instanceof Project && in_array('status', $fields, true) => 'project.status_changed',
            $model instanceof Project && in_array('current_stage', $fields, true) => 'project.stage_changed',
            $model instanceof ProjectMember && in_array('project_role', $fields, true) => 'project.member_role_changed',
            $model instanceof ProjectMember && in_array('status', $fields, true) => 'project.member_status_changed',
            $model instanceof ProjectWorkload => 'project.workload_changed',
            $model instanceof Client && in_array('status', $fields, true) => 'client.status_changed',
            $model instanceof Brand && in_array('status', $fields, true) => 'brand.status_changed',
            default => $this->prefix($model).'.updated',
        };
    }

    private function deletedEvent(Model $model): string
    {
        return match (true) {
            $model instanceof ProjectMember => 'project.member_removed',
            $model instanceof ProjectWorkload => 'project.workload_removed',
            default => $this->prefix($model).'.deleted',
        };
    }

    private function prefix(Model $model): string
    {
        return match (true) {
            $model instanceof Client => 'client',
            $model instanceof Brand => 'brand',
            $model instanceof Project => 'project',
            $model instanceof Task => 'task',
            $model instanceof Subtask => 'subtask',
            default => 'record',
        };
    }

    private function source(): string
    {
        return request()?->route()?->getName() ?? (app()->runningInConsole() ? 'console' : 'unknown');
    }

    private function channel(): string
    {
        return app()->runningInConsole() ? 'system' : 'web';
    }

    private function ready(): bool
    {
        return Schema::hasTable('activity_events');
    }
}
