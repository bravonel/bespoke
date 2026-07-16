<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_users_can_remain_without_role_during_passive_rollout(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->role);
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->hasRole(User::ROLE_ACCOUNTS));
    }

    public function test_user_can_be_assigned_a_business_role(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->hasRole(User::ROLE_ADMIN));
        $this->assertFalse($user->hasRole(User::ROLE_CLIENT_REVIEWER));
    }

    public function test_role_options_expose_the_p0_role_catalog(): void
    {
        $this->assertSame([
            User::ROLE_ADMIN,
            User::ROLE_DIRECTION,
            User::ROLE_ACCOUNTS,
            User::ROLE_TRAFFIC_PM,
            User::ROLE_MEDICAL,
            User::ROLE_DESIGN,
            User::ROLE_LEGAL_REGULATORY,
            User::ROLE_CLIENT_REVIEWER,
        ], array_keys(User::roleOptions()));
    }
}
