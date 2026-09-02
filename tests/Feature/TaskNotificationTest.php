<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_assignment_creates_a_personal_notice_and_navigation_count(): void
    {
        [$manager, $assignee, $project] = $this->fixture();

        $this->actingAs($manager)->post(route('projects.tasks.store', $project), [
            'title' => 'Preparar propuesta visual',
            'status' => 'todo',
            'priority' => 'normal',
            'assignments' => [[
                'user_id' => $assignee->id,
                'role' => 'design',
                'estimated_hours' => 2,
            ]],
        ])->assertRedirect(route('projects.show', $project));

        $notice = $assignee->notifications()->firstOrFail();

        $this->assertSame('task.assigned', $notice->data['kind']);
        $this->assertSame('Nueva tarea asignada', $notice->data['title']);
        $this->assertSame(0, $manager->notifications()->count());

        $this->actingAs($assignee)
            ->get(route('tasks.mine'))
            ->assertOk()
            ->assertSee('Novedades')
            ->assertSee('Preparar propuesta visual')
            ->assertSee('1 novedad sin leer');
    }

    public function test_comment_and_status_changes_notify_other_task_participants(): void
    {
        [$manager, $assignee, $project] = $this->fixture();
        $task = $this->task($project, $assignee);

        $this->actingAs($manager)
            ->post(route('tasks.comments.store', $task), ['body' => 'Hay una actualización para revisar.'])
            ->assertRedirect();

        $this->actingAs($manager)
            ->patch(route('tasks.update-status', $task), ['status' => 'in_progress'])
            ->assertRedirect(route('projects.show', $project));

        $this->assertSame(2, $assignee->notifications()->count());
        $this->assertTrue($assignee->notifications->pluck('data.kind')->contains('task.commented'));
        $this->assertTrue($assignee->notifications->pluck('data.kind')->contains('task.status_changed'));
    }

    public function test_user_can_open_or_clear_only_their_own_notices(): void
    {
        [$manager, $assignee, $project] = $this->fixture();
        $other = User::factory()->create(['role' => User::ROLE_DESIGN]);
        $task = $this->task($project, $assignee);

        $this->actingAs($manager)
            ->post(route('tasks.comments.store', $task), ['body' => 'Favor de revisar.']);

        $notice = $assignee->notifications()->firstOrFail();

        $this->actingAs($other)
            ->post(route('task-notifications.open', $notice))
            ->assertForbidden();

        $this->actingAs($assignee)
            ->post(route('task-notifications.open', $notice))
            ->assertRedirect(route('tasks.show', $task, false));

        $this->assertNotNull($notice->fresh()->read_at);

        $this->actingAs($manager)
            ->post(route('tasks.comments.store', $task), ['body' => 'Segunda revisión.']);

        $this->actingAs($assignee)
            ->patch(route('task-notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $assignee->unreadNotifications()->count());
    }

    private function fixture(): array
    {
        $manager = User::factory()->create(['role' => User::ROLE_ACCOUNTS]);
        $assignee = User::factory()->create(['role' => User::ROLE_DESIGN]);
        $client = Client::query()->create(['name' => 'Cliente avisos', 'status' => 'active']);
        $project = Project::query()->create([
            'client_id' => $client->id,
            'owner_id' => $manager->id,
            'name' => 'Proyecto de avisos',
            'code' => 'AVISOS-001',
            'status' => 'active',
            'current_stage' => 'design',
            'priority' => 'normal',
        ]);

        return [$manager, $assignee, $project];
    }

    private function task(Project $project, User $assignee): Task
    {
        $task = $project->tasks()->create([
            'title' => 'Tarea con novedades',
            'status' => 'todo',
            'priority' => 'normal',
            'sort_order' => 1,
        ]);

        $task->assignments()->create([
            'project_id' => $project->id,
            'user_id' => $assignee->id,
            'role' => 'design',
        ]);

        return $task;
    }
}
