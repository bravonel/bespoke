<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_can_be_created_with_subtasks(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProject($user);

        $response = $this->actingAs($user)->post(route('projects.tasks.store', $project), [
            'title' => 'Preparar material',
            'description' => 'Primera ronda del diptico.',
            'assigned_to' => $user->id,
            'status' => 'todo',
            'priority' => 'high',
            'due_at' => '2026-07-05',
            'subtasks' => "Validar brief\nArmar layout\nMandar a medico",
        ]);

        $response->assertRedirect(route('projects.show', $project));

        $task = Task::query()->where('title', 'Preparar material')->firstOrFail();

        $this->assertSame(3, $task->subtasks()->count());
        $this->assertDatabaseHas('subtasks', [
            'task_id' => $task->id,
            'title' => 'Armar layout',
        ]);
    }

    public function test_tasks_can_be_moved_between_board_columns(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProject($user);

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Esperando assets',
            'status' => 'todo',
            'priority' => 'normal',
            'sort_order' => 0,
        ]);

        $existingInProgress = Task::create([
            'project_id' => $project->id,
            'title' => 'Diseno activo',
            'status' => 'in_progress',
            'priority' => 'normal',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)->patchJson(route('tasks.move', $task), [
            'status' => 'in_progress',
            'ordered_ids' => [$existingInProgress->id, $task->id],
            'source_status' => 'todo',
            'source_ordered_ids' => [],
        ]);

        $response
            ->assertOk()
            ->assertJson(['message' => 'Tablero actualizado.']);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $existingInProgress->id,
            'status' => 'in_progress',
            'sort_order' => 0,
        ]);
    }

    public function test_subtasks_can_be_marked_as_done(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProject($user);

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Revisar claims',
            'status' => 'in_progress',
            'priority' => 'high',
            'sort_order' => 0,
        ]);

        $subtask = Subtask::create([
            'task_id' => $task->id,
            'title' => 'Confirmar referencia clinica',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)->patch(route('subtasks.update', $subtask), [
            'is_done' => 1,
        ]);

        $response->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('subtasks', [
            'id' => $subtask->id,
            'is_done' => true,
        ]);
    }

    public function test_task_detail_page_is_displayed(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProject($user);

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Redactar cierre',
            'description' => 'Version final para cliente.',
            'status' => 'blocked',
            'priority' => 'high',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('tasks.show', $task));

        $response
            ->assertOk()
            ->assertSee('Redactar cierre')
            ->assertSee('Checklist y seguimiento')
            ->assertSee($project->name);
    }

    private function makeProject(User $owner): Project
    {
        $client = Client::create([
            'name' => 'Prometis Pharma',
            'status' => 'active',
        ]);

        return Project::create([
            'client_id' => $client->id,
            'owner_id' => $owner->id,
            'name' => 'REBAGIT Diptico',
            'code' => 'BSP-BOARD',
            'project_type' => 'material',
            'priority' => 'normal',
            'status' => 'active',
            'current_stage' => 'design',
        ]);
    }
}
