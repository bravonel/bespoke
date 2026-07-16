<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_can_be_assigned_explicitly_from_console(): void
    {
        $user = User::factory()->create(['role' => null]);

        $this->artisan('bespoke:assign-role', [
            'email' => $user->email,
            'role' => User::ROLE_ADMIN,
        ])->assertSuccessful();

        $this->assertTrue($user->refresh()->isAdmin());
    }

    public function test_last_active_admin_cannot_be_demoted_or_deactivated(): void
    {
        $actor = User::factory()->create(['role' => User::ROLE_DIRECTION]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($actor)->patch(route('collaborators.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'area' => $admin->area,
            'puesto' => $admin->puesto,
            'role' => User::ROLE_DIRECTION,
            'daily_capacity_hours' => '8',
            'password' => '',
            'password_confirmation' => '',
        ])->assertSessionHas('status', 'No puedes cambiar el rol del último administrador activo.');

        $this->assertTrue($admin->refresh()->isAdmin());

        $this->actingAs($actor)
            ->patch(route('collaborators.deactivate', $admin))
            ->assertSessionHas('status', 'No puedes dar de baja al último administrador activo.');

        $this->assertTrue($admin->refresh()->is_active);
    }
}
