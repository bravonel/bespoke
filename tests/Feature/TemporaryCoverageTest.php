<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\TemporaryCoverage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemporaryCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_collaborator_can_program_and_cancel_their_own_coverage(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_DESIGN]);
        $delegate = User::factory()->create(['role' => User::ROLE_MEDICAL]);

        $this->actingAs($owner)->post(route('coverages.store'), [
            'delegate_user_id' => $delegate->id,
            'starts_on' => today()->format('Y-m-d'),
            'ends_on' => today()->addWeek()->format('Y-m-d'),
            'note' => 'Dar seguimiento a los pendientes urgentes.',
            'scope_mode' => 'all',
        ])->assertRedirect();

        $coverage = TemporaryCoverage::query()->firstOrFail();
        $this->assertSame($owner->id, $coverage->owner_user_id);
        $this->assertSame($delegate->id, $coverage->delegate_user_id);
        $this->assertSame('coverage.assigned', $delegate->notifications()->firstOrFail()->data['kind']);

        $this->actingAs($delegate)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Trabajo que estás cubriendo')
            ->assertSee($owner->name);

        $this->actingAs($delegate)
            ->delete(route('coverages.destroy', $coverage))
            ->assertForbidden();

        $this->actingAs($owner)
            ->delete(route('coverages.destroy', $coverage))
            ->assertRedirect();

        $this->assertNotNull($coverage->fresh()->revoked_at);
        $this->assertTrue($delegate->notifications()->get()->pluck('data.kind')->contains('coverage.revoked'));
    }

    public function test_coverage_rejects_self_inactive_admin_and_overlapping_delegations(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_DESIGN]);
        $delegate = User::factory()->create(['role' => User::ROLE_MEDICAL]);
        $inactive = User::factory()->create(['role' => User::ROLE_MEDICAL, 'is_active' => false]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        foreach ([$owner, $inactive, $admin] as $invalidDelegate) {
            $this->actingAs($owner)->from(route('profile'))->post(route('coverages.store'), [
                'delegate_user_id' => $invalidDelegate->id,
                'starts_on' => today()->format('Y-m-d'),
                'ends_on' => today()->addDay()->format('Y-m-d'),
                'scope_mode' => 'all',
            ])->assertRedirect(route('profile'))->assertSessionHasErrors('delegate_user_id');
        }

        $this->actingAs($owner)->post(route('coverages.store'), [
            'delegate_user_id' => $delegate->id,
            'starts_on' => today()->addDays(2)->format('Y-m-d'),
            'ends_on' => today()->addDays(5)->format('Y-m-d'),
            'scope_mode' => 'all',
        ])->assertRedirect();

        $this->actingAs($owner)->from(route('profile'))->post(route('coverages.store'), [
            'delegate_user_id' => User::factory()->create(['role' => User::ROLE_DESIGN])->id,
            'starts_on' => today()->addDays(4)->format('Y-m-d'),
            'ends_on' => today()->addDays(7)->format('Y-m-d'),
            'scope_mode' => 'all',
        ])->assertRedirect(route('profile'))->assertSessionHasErrors('scope_mode');
    }

    public function test_active_coverage_grants_operational_access_without_management_privileges(): void
    {
        [$owner, $delegate, $project, $task] = $this->fixture();

        $this->actingAs($delegate)->get(route('projects.show', $project))->assertForbidden();
        $this->actingAs($delegate)->get(route('tasks.show', $task))->assertForbidden();

        TemporaryCoverage::query()->create([
            'owner_user_id' => $owner->id,
            'delegate_user_id' => $delegate->id,
            'starts_on' => today(),
            'ends_on' => today()->addWeek(),
        ]);

        $this->actingAs($delegate)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee($project->name);

        $this->actingAs($delegate)
            ->get(route('tasks.mine'))
            ->assertOk()
            ->assertSee('Cubriendo a '.$owner->name)
            ->assertSee($task->title);

        $this->actingAs($delegate)
            ->patch(route('tasks.update-status', $task), ['status' => 'in_progress'])
            ->assertRedirect(route('projects.show', $project));

        $this->assertSame('in_progress', $task->fresh()->status);

        $this->actingAs($delegate)
            ->patch(route('tasks.update', $task), [
                'title' => 'No debe poder editar estructura',
                'priority' => 'normal',
            ])
            ->assertForbidden();

        $this->actingAs($delegate)
            ->delete(route('tasks.destroy', $task))
            ->assertForbidden();
    }

    public function test_owner_can_use_multiple_replacements_for_different_accounts(): void
    {
        [$owner, $firstDelegate, $firstProject, $firstTask] = $this->fixture();
        $secondDelegate = User::factory()->create(['role' => User::ROLE_MEDICAL]);
        $secondClient = Client::query()->create(['name' => 'Segunda cuenta', 'status' => 'active']);
        $secondProject = Project::query()->create([
            'client_id' => $secondClient->id,
            'owner_id' => $owner->id,
            'name' => 'Segundo proyecto cubierto',
            'code' => 'COB-002',
            'status' => 'active',
            'current_stage' => 'medical',
            'priority' => 'normal',
        ]);
        $secondTask = $secondProject->tasks()->create([
            'title' => 'Tarea de la segunda cuenta',
            'status' => 'todo',
            'priority' => 'normal',
            'sort_order' => 1,
        ]);

        $dates = [
            'starts_on' => today()->format('Y-m-d'),
            'ends_on' => today()->addWeek()->format('Y-m-d'),
            'scope_mode' => 'selected',
        ];

        $this->actingAs($owner)->post(route('coverages.store'), [
            ...$dates,
            'delegate_user_id' => $firstDelegate->id,
            'client_ids' => [$firstProject->client_id],
        ])->assertRedirect();

        $this->actingAs($owner)->post(route('coverages.store'), [
            ...$dates,
            'delegate_user_id' => $secondDelegate->id,
            'project_ids' => [$secondProject->id],
        ])->assertRedirect();

        $this->assertSame(2, TemporaryCoverage::query()->count());

        $this->actingAs($firstDelegate)->get(route('tasks.show', $firstTask))->assertOk();
        $this->actingAs($firstDelegate)->get(route('tasks.show', $secondTask))->assertForbidden();
        $this->actingAs($secondDelegate)->get(route('tasks.show', $secondTask))->assertOk();
        $this->actingAs($secondDelegate)->get(route('tasks.show', $firstTask))->assertForbidden();

        $this->actingAs($firstDelegate)
            ->get(route('tasks.mine'))
            ->assertOk()
            ->assertSee('1 tarea activa');

        $manager = User::factory()->create(['role' => User::ROLE_ACCOUNTS]);
        $this->actingAs($manager)->post(route('tasks.comments.store', $firstTask), ['body' => 'Aviso de la primera cuenta.']);
        $this->actingAs($manager)->post(route('tasks.comments.store', $secondTask), ['body' => 'Aviso de la segunda cuenta.']);

        $firstTaskNotices = $firstDelegate->notifications()
            ->get()
            ->where('data.kind', 'task.commented');
        $secondTaskNotices = $secondDelegate->notifications()
            ->get()
            ->where('data.kind', 'task.commented');

        $this->assertSame([$firstTask->id], $firstTaskNotices->pluck('data.task_id')->values()->all());
        $this->assertSame([$secondTask->id], $secondTaskNotices->pluck('data.task_id')->values()->all());

        $thirdDelegate = User::factory()->create(['role' => User::ROLE_DESIGN]);
        $this->actingAs($owner)->from(route('profile'))->post(route('coverages.store'), [
            ...$dates,
            'delegate_user_id' => $thirdDelegate->id,
            'project_ids' => [$firstProject->id],
        ])->assertRedirect(route('profile'))->assertSessionHasErrors('scope_mode');
    }

    public function test_future_expired_revoked_and_chained_coverages_do_not_grant_access(): void
    {
        [$owner, $delegate, $project, $task] = $this->fixture();
        $third = User::factory()->create(['role' => User::ROLE_DESIGN]);

        TemporaryCoverage::query()->create([
            'owner_user_id' => $owner->id,
            'delegate_user_id' => $delegate->id,
            'starts_on' => today()->addDay(),
            'ends_on' => today()->addWeek(),
        ]);

        $this->actingAs($delegate)->get(route('tasks.show', $task))->assertForbidden();

        TemporaryCoverage::query()->update([
            'starts_on' => today()->subWeek(),
            'ends_on' => today()->subDay(),
        ]);
        $this->actingAs($delegate)->get(route('tasks.show', $task))->assertForbidden();

        TemporaryCoverage::query()->update([
            'starts_on' => today(),
            'ends_on' => today()->addWeek(),
            'revoked_at' => now(),
        ]);
        $this->actingAs($delegate)->get(route('tasks.show', $task))->assertForbidden();

        TemporaryCoverage::query()->update(['revoked_at' => null]);
        TemporaryCoverage::query()->create([
            'owner_user_id' => $delegate->id,
            'delegate_user_id' => $third->id,
            'starts_on' => today(),
            'ends_on' => today()->addWeek(),
        ]);

        $this->actingAs($third)->get(route('tasks.show', $task))->assertForbidden();
    }

    public function test_task_notices_are_forwarded_to_the_active_delegate(): void
    {
        [$owner, $delegate, $project, $task] = $this->fixture();
        $manager = User::factory()->create(['role' => User::ROLE_ACCOUNTS]);

        TemporaryCoverage::query()->create([
            'owner_user_id' => $owner->id,
            'delegate_user_id' => $delegate->id,
            'starts_on' => today(),
            'ends_on' => today()->addWeek(),
        ]);

        $this->actingAs($manager)
            ->post(route('tasks.comments.store', $task), ['body' => 'Favor de revisar durante la cobertura.'])
            ->assertRedirect();

        $this->assertSame('task.commented', $delegate->notifications()->firstOrFail()->data['kind']);
    }

    private function fixture(): array
    {
        $owner = User::factory()->create(['role' => User::ROLE_DESIGN]);
        $delegate = User::factory()->create(['role' => User::ROLE_MEDICAL]);
        $client = Client::query()->create(['name' => 'Cliente cobertura', 'status' => 'active']);
        $project = Project::query()->create([
            'client_id' => $client->id,
            'owner_id' => $owner->id,
            'name' => 'Proyecto cubierto',
            'code' => 'COB-001',
            'status' => 'active',
            'current_stage' => 'design',
            'priority' => 'normal',
        ]);
        $task = $project->tasks()->create([
            'title' => 'Tarea durante ausencia',
            'status' => 'todo',
            'priority' => 'normal',
            'sort_order' => 1,
        ]);
        $task->assignments()->create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'role' => 'design',
        ]);

        return [$owner, $delegate, $project, $task];
    }
}
