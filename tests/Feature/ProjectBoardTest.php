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
            'planned_for' => '2026-07-02',
            'estimated_hours' => '2.5',
            'due_at' => '2026-07-05',
            'subtasks' => "Validar brief\nArmar layout\nMandar a medico",
        ]);

        $response->assertRedirect(route('projects.show', $project));

        $task = Task::query()->where('title', 'Preparar material')->firstOrFail();

        $this->assertSame(3, $task->subtasks()->count());
        $this->assertSame('2026-07-02', $task->planned_for->format('Y-m-d'));
        $this->assertSame(150, $task->estimated_minutes);
        $this->assertDatabaseHas('subtasks', [
            'task_id' => $task->id,
            'title' => 'Armar layout',
        ]);
    }

    public function test_daily_load_is_displayed_on_dashboard(): void
    {
        $user = User::factory()->create([
            'name' => 'Persona de Arte',
            'area' => 'Arte',
            'daily_capacity_minutes' => 480,
        ]);
        $project = $this->makeProject($user);

        Task::create([
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'title' => 'Ajustar storyboard',
            'status' => 'todo',
            'priority' => 'normal',
            'planned_for' => today(),
            'estimated_minutes' => 240,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard', [
            'date' => today()->format('Y-m-d'),
        ]));

        $response
            ->assertOk()
            ->assertSee('Carga diaria')
            ->assertSee('Persona de Arte')
            ->assertSee('Ajustar storyboard')
            ->assertSee('4 h / 8 h');
    }

    public function test_projects_can_store_an_odt_code(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'name' => 'Roche',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'client_id' => $client->id,
            'name' => 'Video MOA',
            'odt_code' => 'ODT-13041',
            'project_type' => 'video',
            'priority' => 'normal',
            'status' => 'active',
            'current_stage' => 'brief',
        ]);

        $project = Project::query()->where('odt_code', 'ODT-13041')->firstOrFail();

        $response->assertRedirect(route('projects.show', $project));
        $this->assertSame('ODT ODT-13041', $project->operationalCodeLabel());
    }

    public function test_projects_can_store_client_context_and_workloads(): void
    {
        $user = User::factory()->create();
        $designer = User::factory()->create([
            'name' => 'Luis Cervantes',
            'area' => 'Diseño',
        ]);
        $client = Client::create([
            'name' => 'Roche',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'client_id' => $client->id,
            'name' => 'Flyer Evrysdi',
            'odt_code' => 'ODT-14001',
            'project_type' => 'flyer',
            'delivery_type' => 'digital',
            'target_audience' => 'Médicos especialistas',
            'material_size' => '1080x1080',
            'legal_requirements' => 'Agregar código interno y claims aprobados.',
            'reference_links' => 'https://contoso.sharepoint.com/proyecto',
            'priority' => 'normal',
            'status' => 'active',
            'current_stage' => 'brief',
            'starts_at' => '2026-07-09',
            'due_at' => '2026-07-15',
            'workloads' => [
                'design' => [
                    'user_id' => $designer->id,
                    'work_date' => '2026-07-09',
                    'estimated_hours' => '4',
                    'notes' => 'Diseño de primera propuesta',
                ],
            ],
        ]);

        $project = Project::query()->where('odt_code', 'ODT-14001')->firstOrFail();

        $response->assertRedirect(route('projects.show', $project));
        $this->assertSame('digital', $project->delivery_type);
        $this->assertSame('Médicos especialistas', $project->target_audience);
        $this->assertDatabaseHas('project_workloads', [
            'project_id' => $project->id,
            'user_id' => $designer->id,
            'role' => 'design',
            'work_date' => '2026-07-09 00:00:00',
            'estimated_minutes' => 240,
            'notes' => 'Diseño de primera propuesta',
        ]);
    }

    public function test_daily_load_includes_project_workloads(): void
    {
        $user = User::factory()->create([
            'name' => 'Luis Cervantes',
            'area' => 'Diseño',
            'daily_capacity_minutes' => 480,
        ]);
        $project = $this->makeProject($user);

        ProjectWorkload::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'design',
            'work_date' => today(),
            'estimated_minutes' => 240,
            'notes' => 'Diseño de flyer',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard', [
            'date' => today()->format('Y-m-d'),
        ]));

        $response
            ->assertOk()
            ->assertSee('Carga diaria')
            ->assertSee('Luis Cervantes')
            ->assertSee('Diseño de flyer')
            ->assertSee('Diseño')
            ->assertSee('4 h / 8 h');
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
            ->assertSee('Lista y seguimiento')
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
