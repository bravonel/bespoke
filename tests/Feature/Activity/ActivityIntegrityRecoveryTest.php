<?php

namespace Tests\Feature\Activity;

use App\Models\ActivityAlert;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Services\Audit\ActivityEventTriggerManager;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActivityIntegrityRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_verifier_reports_every_failure_and_reopens_alerts(): void
    {
        $user = User::factory()->create();
        $first = app(AuditLogger::class)->record('test.first', $user, $user);
        $second = app(AuditLogger::class)->record('test.second', $user, $user);

        $triggers = app(ActivityEventTriggerManager::class);
        $triggers->dropUpdateProtection();
        DB::table('activity_events')->where('id', $first->id)->update(['event_type' => 'tampered.first']);
        DB::table('activity_events')->where('id', $second->id)->update(['event_type' => 'tampered.second']);
        $triggers->restoreUpdateProtection();

        $this->artisan('activity:verify-chain')->assertFailed();
        $this->assertSame(2, ActivityAlert::query()->whereNull('resolved_at')->count());

        ActivityAlert::query()->update(['resolved_at' => now()]);
        $this->artisan('activity:verify-chain')->assertFailed();
        $this->assertSame(2, ActivityAlert::query()->whereNull('resolved_at')->count());
    }

    public function test_invalid_integrity_alert_cannot_be_resolved_manually(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $event = app(AuditLogger::class)->record('test.event', $admin, $admin);
        $triggers = app(ActivityEventTriggerManager::class);
        $triggers->dropUpdateProtection();
        DB::table('activity_events')->where('id', $event->id)->update(['event_type' => 'tampered']);
        $triggers->restoreUpdateProtection();
        $this->artisan('activity:verify-chain')->assertFailed();
        $alert = ActivityAlert::query()->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('activity.alerts.resolve', $alert))
            ->assertSessionHasErrors('alert');

        $this->assertNull($alert->fresh()->resolved_at);
    }

    public function test_legacy_project_context_is_repaired_only_by_unique_hmac_match(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['name' => 'Cliente', 'status' => 'active']);
        $project = Project::create([
            'client_id' => $client->id,
            'owner_id' => $user->id,
            'name' => 'Proyecto',
            'code' => 'BSP-REPAIR',
            'odt_code' => 'ODT-REPAIR',
            'project_type' => 'campana',
            'priority' => 'normal',
            'status' => 'active',
            'current_stage' => 'initial',
        ]);
        $event = app(AuditLogger::class)->record('project.tested', $project, $user);
        $triggers = app(ActivityEventTriggerManager::class);
        $triggers->dropUpdateProtection();
        DB::table('activity_events')->where('id', $event->id)->update(['project_id' => null]);
        $triggers->restoreUpdateProtection();

        $this->artisan('activity:repair-legacy-context', ['--max-project-id' => 100])
            ->assertSuccessful();
        $this->assertNull($event->fresh()->project_id);

        $this->artisan('activity:repair-legacy-context', [
            '--apply' => true,
            '--backup-confirmed' => true,
            '--max-project-id' => 100,
        ])->assertSuccessful();

        $this->assertSame($project->id, $event->fresh()->project_id);
        $this->assertDatabaseHas('activity_events', ['event_type' => 'activity.legacy_context_repaired']);
        $this->artisan('activity:verify-chain')->assertSuccessful();
    }
}
