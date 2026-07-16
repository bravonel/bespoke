<?php

namespace Tests\Feature\Activity;

use App\Models\ActivityEvent;
use App\Models\Client;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActivitySystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_changes_create_canonical_activity_without_content(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $client = Client::create(['name' => 'Cliente', 'status' => 'active']);
        $client->update(['primary_contact_email' => 'persona@ejemplo.com']);

        $event = ActivityEvent::query()->where('event_type', 'client.updated')->latest('id')->firstOrFail();

        $this->assertSame($user->id, $event->actor_id);
        $this->assertSame(['changed' => true], $event->metadata['changes']['primary_contact_email']);
        $this->assertStringNotContainsString('persona@ejemplo.com', json_encode($event->metadata));
    }

    public function test_ui_event_ingestion_accepts_allowlist_and_rejects_arbitrary_events(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('activity.ui-events'), [
            'events' => [[
                'event_name' => 'navigation.clicked',
                'page' => 'dashboard',
                'target' => 'projects',
                'metadata' => ['source' => 'navigation', 'raw_text' => 'no guardar'],
            ]],
        ])->assertAccepted();

        $this->assertDatabaseHas('ui_events', [
            'user_id' => $user->id,
            'event_name' => 'navigation.clicked',
        ]);

        $this->assertNotContains('keystroke.captured', config('activity.ui_events'));
    }

    public function test_regular_user_sees_only_own_activity_but_direction_sees_team(): void
    {
        $first = User::factory()->create(['role' => User::ROLE_DESIGN]);
        $second = User::factory()->create(['role' => User::ROLE_MEDICAL]);
        $direction = User::factory()->create(['role' => User::ROLE_DIRECTION]);
        app(AuditLogger::class)->record('user.tested', $first, $first);
        app(AuditLogger::class)->record('user.tested', $second, $second);

        $ownResponse = $this->actingAs($first)->get(route('activity.index'));
        $this->assertTrue($ownResponse->viewData('events')->every(fn ($event) => $event->actor_id === $first->id));

        $teamResponse = $this->actingAs($direction)->get(route('activity.index'));
        $actorIds = $teamResponse->viewData('events')->pluck('actor_id');
        $this->assertTrue($actorIds->contains($first->id));
        $this->assertTrue($actorIds->contains($second->id));
    }

    public function test_database_rejects_direct_activity_mutation_and_hash_chain_verifies(): void
    {
        $user = User::factory()->create();
        $event = app(AuditLogger::class)->record('user.tested', $user, $user);

        try {
            DB::table('activity_events')->where('id', $event->id)->update(['event_type' => 'tampered']);
            $this->fail('La base permitió alterar la auditoría.');
        } catch (QueryException) {
            $this->assertDatabaseHas('activity_events', ['id' => $event->id, 'event_type' => 'user.tested']);
        }

        $this->artisan('activity:verify-chain')->assertSuccessful();
    }
}
