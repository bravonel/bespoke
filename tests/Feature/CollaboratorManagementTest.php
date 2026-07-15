<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Tests\TestCase;

class CollaboratorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_collaborators_can_be_created_and_listed(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('collaborators.store'), [
            'name' => 'Nueva Colaboradora',
            'email' => 'nueva@bespokeadvertising.com.mx',
            'area' => 'Diseño',
            'puesto' => 'Diseñador Sr.',
            'daily_capacity_hours' => '7.5',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('collaborators.index'));

        $collaborator = User::query()->where('email', 'nueva@bespokeadvertising.com.mx')->firstOrFail();

        $this->assertTrue($collaborator->is_active);
        $this->assertSame(450, $collaborator->daily_capacity_minutes);
        $this->assertTrue(Hash::check('Password123!', $collaborator->password));

        $this->actingAs($admin)
            ->get(route('collaborators.index', ['q' => 'Nueva']))
            ->assertOk()
            ->assertSee('Nueva Colaboradora')
            ->assertSee('Diseño');
    }

    public function test_collaborators_can_be_updated_without_changing_password(): void
    {
        $admin = User::factory()->create();
        $collaborator = User::factory()->create([
            'password' => Hash::make('Original123!'),
            'daily_capacity_minutes' => 480,
        ]);

        $this->actingAs($admin)
            ->patch(route('collaborators.update', $collaborator), [
                'name' => 'Nombre Editado',
                'email' => $collaborator->email,
                'area' => 'Médico',
                'puesto' => 'Redactor médico',
                'daily_capacity_hours' => '6',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('collaborators.index'));

        $collaborator->refresh();

        $this->assertSame('Nombre Editado', $collaborator->name);
        $this->assertSame('Médico', $collaborator->area);
        $this->assertSame(360, $collaborator->daily_capacity_minutes);
        $this->assertTrue(Hash::check('Original123!', $collaborator->password));
    }

    public function test_collaborators_can_be_deactivated_and_reactivated(): void
    {
        $admin = User::factory()->create();
        $collaborator = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('collaborators.deactivate', $collaborator))
            ->assertRedirect();

        $this->assertFalse($collaborator->refresh()->is_active);

        $this->actingAs($admin)
            ->get(route('collaborators.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee($collaborator->name);

        $this->actingAs($admin)
            ->patch(route('collaborators.activate', $collaborator))
            ->assertRedirect();

        $this->assertTrue($collaborator->refresh()->is_active);
    }

    public function test_user_cannot_deactivate_their_own_account(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('collaborators.deactivate', $admin))
            ->assertRedirect();

        $this->assertTrue($admin->refresh()->is_active);
    }

    public function test_inactive_users_cannot_login(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
        ]);

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasErrors()
            ->assertNoRedirect();

        $this->assertGuest();
    }
}
