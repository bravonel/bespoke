<?php

namespace Tests\Feature;

use App\Models\ActivityEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureFirstAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_assigns_earliest_active_user_when_no_admin_exists(): void
    {
        $first = User::factory()->create(['role' => null]);
        $second = User::factory()->create(['role' => null]);

        $this->artisan('bespoke:ensure-first-admin')->assertSuccessful();

        $this->assertTrue($first->refresh()->isAdmin());
        $this->assertNull($second->refresh()->role);
        $this->assertDatabaseHas('activity_events', [
            'event_type' => 'user.role_changed',
            'auditable_type' => $first->getMorphClass(),
            'auditable_id' => $first->id,
        ]);
    }

    public function test_command_is_idempotent_when_an_active_admin_exists(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create(['role' => null]);

        $this->artisan('bespoke:ensure-first-admin')->assertSuccessful();

        $this->assertTrue($admin->refresh()->isAdmin());
        $this->assertSame(0, ActivityEvent::query()->count());
    }
}
