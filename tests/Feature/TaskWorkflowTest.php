<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectWorkload;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_returning_in_progress_work_to_todo_requires_an_actionable_reason(): void
    {
        [$manager, $project, $task] = $this->workflowFixture('in_progress');

        $this->actingAs($manager)
            ->patch(route('tasks.update-status', $task), ['status' => 'todo'])
            ->assertSessionHasErrors('blocked_reason');

        $this->actingAs($manager)
            ->patch(route('tasks.update-status', $task), [
                'status' => 'todo',
                'blocked_reason' => 'Falta el logo; Cuentas debe solicitarlo al cliente.',
            ])
            ->assertRedirect(route('projects.show', $project));

        $this->assertSame('todo', $task->refresh()->status);
        $this->assertSame('Falta el logo; Cuentas debe solicitarlo al cliente.', $task->blocked_reason);
    }

    public function test_delivery_requires_the_checklist_and_finalization_requires_delivery(): void
    {
        [$manager, $project, $task] = $this->workflowFixture('in_progress');
        Subtask::create(['task_id' => $task->id, 'title' => 'Validar legal', 'sort_order' => 0]);

        $this->actingAs($manager)
            ->patch(route('tasks.update-status', $task), ['status' => 'done'])
            ->assertSessionHasErrors('status');

        $task->subtasks()->update(['is_done' => true, 'completed_at' => now()]);

        $this->actingAs($manager)
            ->patch(route('tasks.update-status', $task), ['status' => 'done'])
            ->assertRedirect(route('projects.show', $project));

        $this->assertNotNull($task->refresh()->delivered_at);
        $this->assertNull($task->completed_at);

        $this->actingAs($manager)
            ->patch(route('tasks.update-status', $task), ['status' => 'finalized'])
            ->assertRedirect(route('projects.show', $project));

        $this->assertSame('finalized', $task->refresh()->status);
        $this->assertNotNull($task->finalized_at);
        $this->assertNotNull($task->completed_at);
        $this->assertSame('done', $project->refresh()->status);
        $this->assertNotNull($project->completed_at);
    }

    public function test_returning_delivered_work_requires_a_correction_reason(): void
    {
        [$manager, $project, $task] = $this->workflowFixture('done');
        $task->update(['delivered_at' => now()]);

        $this->actingAs($manager)
            ->patch(route('tasks.update-status', $task), ['status' => 'todo'])
            ->assertSessionHasErrors('return_reason');

        $this->actingAs($manager)
            ->patch(route('tasks.update-status', $task), [
                'status' => 'todo',
                'return_reason' => 'Corregir el CTA y volver a entregar.',
            ])
            ->assertRedirect(route('projects.show', $project));

        $this->assertSame('todo', $task->refresh()->status);
        $this->assertSame('Corregir el CTA y volver a entregar.', $task->return_reason);
        $this->assertNull($task->delivered_at);
    }

    public function test_assignee_can_operate_but_cannot_replan_or_finalize_tasks(): void
    {
        [$manager, $project, $task] = $this->workflowFixture();
        $designer = User::factory()->create(['role' => User::ROLE_DESIGN]);
        $otherDesigner = User::factory()->create(['role' => User::ROLE_DESIGN]);
        $task->update(['assigned_to' => $designer->id]);

        $this->actingAs($designer)
            ->patch(route('tasks.update-status', $task), ['status' => 'in_progress'])
            ->assertRedirect(route('projects.show', $project));

        $this->actingAs($designer)
            ->patch(route('tasks.update', $task), [
                'title' => 'Intento de cambio',
                'priority' => 'normal',
            ])
            ->assertForbidden();

        $task->update(['status' => 'done', 'delivered_at' => now()]);

        $this->actingAs($designer)
            ->patch(route('tasks.update-status', $task), ['status' => 'finalized'])
            ->assertForbidden();

        $this->actingAs($otherDesigner)
            ->get(route('projects.show', $project))
            ->assertForbidden();
    }

    public function test_project_participants_can_comment_but_unrelated_users_cannot(): void
    {
        [$manager, $project, $task] = $this->workflowFixture();
        $designer = User::factory()->create(['role' => User::ROLE_DESIGN]);
        $outsider = User::factory()->create(['role' => User::ROLE_DESIGN]);
        $task->update(['assigned_to' => $designer->id]);

        $this->actingAs($designer)
            ->post(route('tasks.comments.store', $task), ['body' => 'Primera propuesta lista en la carpeta.'])
            ->assertRedirect();

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $task->id,
            'user_id' => $designer->id,
            'body' => 'Primera propuesta lista en la carpeta.',
        ]);

        $this->actingAs($outsider)
            ->post(route('tasks.comments.store', $task), ['body' => 'No debo entrar.'])
            ->assertForbidden();
    }

    public function test_every_participant_on_a_shared_task_can_operate_it(): void
    {
        [$manager, $project, $task] = $this->workflowFixture();
        $designer = User::factory()->create(['role' => User::ROLE_DESIGN]);
        $copy = User::factory()->create(['role' => User::ROLE_DESIGN, 'area' => 'Copy']);

        foreach ([[$designer, 'design'], [$copy, 'copy']] as [$participant, $role]) {
            ProjectWorkload::create([
                'project_id' => $project->id,
                'task_id' => $task->id,
                'user_id' => $participant->id,
                'role' => $role,
                'estimated_minutes' => 60,
            ]);
        }

        $this->actingAs($designer)
            ->patch(route('tasks.update-status', $task), ['status' => 'in_progress'])
            ->assertRedirect(route('projects.show', $project));

        $this->actingAs($copy)
            ->patch(route('tasks.update-status', $task), [
                'status' => 'todo',
                'blocked_reason' => 'Falta una aprobación para continuar.',
            ])
            ->assertRedirect(route('projects.show', $project));

        $this->assertSame('todo', $task->refresh()->status);
    }

    private function workflowFixture(string $status = 'todo'): array
    {
        $manager = User::factory()->create(['role' => User::ROLE_ACCOUNTS]);
        $client = Client::create(['name' => fake()->unique()->company(), 'status' => 'active']);
        $project = Project::create([
            'client_id' => $client->id,
            'owner_id' => $manager->id,
            'name' => 'ODT flujo',
            'code' => 'BSP-'.fake()->unique()->numerify('####'),
            'priority' => 'normal',
            'status' => 'active',
            'current_stage' => 'design',
        ]);
        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Preparar material',
            'status' => $status,
            'priority' => 'normal',
            'personal_priority' => 1,
            'sort_order' => 0,
        ]);

        return [$manager, $project, $task];
    }
}
