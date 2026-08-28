<?php

namespace Tests\Feature;

use App\Models\ActivityEvent;
use App\Models\Brand;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class SafeArchivalTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_and_brand_are_deactivated_without_deleting_project_history(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['name' => 'Cliente histórico', 'status' => 'active']);
        $brand = Brand::create(['client_id' => $client->id, 'name' => 'Marca histórica', 'status' => 'active']);
        $project = Project::create([
            'client_id' => $client->id,
            'brand_id' => $brand->id,
            'owner_id' => $user->id,
            'name' => 'Proyecto histórico',
            'code' => 'BSP-HISTORY',
            'odt_code' => 'ODT-HISTORY',
            'project_type' => 'campana',
            'priority' => 'normal',
            'status' => 'active',
            'current_stage' => 'initial',
        ]);

        $this->actingAs($user)->patch(route('brands.deactivate', $brand))->assertRedirect(route('brands.index'));
        $this->actingAs($user)->patch(route('clients.deactivate', $client))->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'status' => 'archived']);
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'status' => 'archived']);
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
        $this->assertDatabaseHas('activity_events', ['event_type' => 'brand.deactivated']);
        $this->assertDatabaseHas('activity_events', ['event_type' => 'client.deactivated']);
        $this->artisan('activity:verify-chain')->assertSuccessful();
    }

    public function test_operational_models_reject_physical_deletion(): void
    {
        $client = Client::create(['name' => 'No borrar', 'status' => 'active']);

        $this->expectException(LogicException::class);
        $client->delete();
    }

    public function test_audit_snapshot_survives_deleted_actor(): void
    {
        $user = User::factory()->create(['name' => 'Actor histórico']);
        $this->actingAs($user);
        $client = Client::create(['name' => 'Contexto histórico', 'status' => 'active']);
        $event = ActivityEvent::query()->where('event_type', 'client.created')->latest('id')->firstOrFail();

        $user->delete();
        $event->refresh()->unsetRelation('actor');

        $this->assertSame('Actor histórico', $event->actorLabel());
        $this->assertSame('Contexto histórico', $event->contextLabel());
        $this->artisan('activity:verify-chain')->assertSuccessful();
    }
}
