<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('bespoke.super_admin_emails', [
            'sony@bespokeadvertising.com.mx',
            'marco@bespokeadvertising.com.mx',
        ]);
    }

    public function test_only_configured_super_admins_can_open_sensitive_modules(): void
    {
        $sony = User::factory()->create([
            'email' => 'sony@bespokeadvertising.com.mx',
            'role' => User::ROLE_ADMIN,
        ]);
        $outsideAdmin = User::factory()->create([
            'email' => 'otra-persona@bespokeadvertising.com.mx',
            'role' => User::ROLE_ADMIN,
        ]);
        $collaborator = User::factory()->create(['role' => User::ROLE_DESIGN]);

        foreach ([$outsideAdmin, $collaborator] as $restrictedUser) {
            $this->actingAs($restrictedUser)->get(route('collaborators.index'))->assertForbidden();
            $this->actingAs($restrictedUser)->get(route('activity.index'))->assertForbidden();
            $this->actingAs($restrictedUser)->get(route('activity.export'))->assertForbidden();
            $this->actingAs($restrictedUser)->get(route('activity.print'))->assertForbidden();
        }

        $this->actingAs($sony)->get(route('collaborators.index'))->assertOk();
        $this->actingAs($sony)->get(route('activity.index'))->assertOk();
    }

    public function test_sensitive_navigation_is_hidden_from_collaborators(): void
    {
        $collaborator = User::factory()->create(['role' => User::ROLE_DESIGN]);

        $this->actingAs($collaborator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('href="'.route('collaborators.index').'"', false)
            ->assertDontSee('href="'.route('activity.index').'"', false);
    }

    public function test_super_admin_role_cannot_be_assigned_to_an_unapproved_email(): void
    {
        $sony = User::factory()->create([
            'email' => 'sony@bespokeadvertising.com.mx',
            'role' => User::ROLE_ADMIN,
        ]);
        $collaborator = User::factory()->create([
            'email' => 'persona@bespokeadvertising.com.mx',
            'role' => User::ROLE_DESIGN,
        ]);

        $this->actingAs($sony)
            ->patch(route('collaborators.update', $collaborator), [
                'name' => $collaborator->name,
                'email' => $collaborator->email,
                'area' => $collaborator->area,
                'puesto' => $collaborator->puesto,
                'role' => User::ROLE_ADMIN,
                'daily_capacity_hours' => 8,
            ])
            ->assertSessionHas('status', 'El acceso de superadministrador está reservado para Sony y Marco.');

        $this->assertSame(User::ROLE_DESIGN, $collaborator->refresh()->role);
    }
}
