<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_membership_is_unique_and_exposed_through_relations(): void
    {
        $client = Client::query()->create(['name' => 'Cliente', 'status' => 'active']);
        $brand = Brand::query()->create(['client_id' => $client->id, 'name' => 'Marca', 'status' => 'active']);
        $owner = User::factory()->create();
        $member = User::factory()->create(['role' => User::ROLE_DESIGN]);
        $project = Project::query()->create([
            'client_id' => $client->id,
            'brand_id' => $brand->id,
            'owner_id' => $owner->id,
            'name' => 'Material de prueba',
            'code' => 'BSP-001',
            'priority' => 'normal',
            'status' => 'active',
            'current_stage' => 'design',
        ]);

        ProjectMember::query()->create([
            'project_id' => $project->id,
            'user_id' => $member->id,
            'project_role' => User::ROLE_DESIGN,
            'added_by' => $owner->id,
        ]);

        $this->assertTrue($project->fresh()->members->contains($member));
        $this->assertTrue($member->fresh()->memberProjects->contains($project));
        $this->assertSame(User::ROLE_DESIGN, $project->memberships->first()->project_role);
    }
}
