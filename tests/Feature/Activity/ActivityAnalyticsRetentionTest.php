<?php

namespace Tests\Feature\Activity;

use App\Models\ActivityDailyMetric;
use App\Models\UiEvent;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ActivityAnalyticsRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_daily_analytics_are_idempotent_and_preserve_useful_dimensions(): void
    {
        Carbon::setTestNow('2026-09-01 12:00:00');
        $user = User::factory()->create();
        $session = UserSession::query()->create([
            'user_id' => $user->id,
            'session_key_hash' => hash('sha256', 'analytics-session'),
            'channel' => 'web',
            'device_type' => 'desktop',
            'browser' => 'Chrome',
            'platform' => 'macOS',
            'active_seconds' => 900,
            'idle_seconds' => 120,
            'started_at' => '2026-08-31 10:00:00',
            'ended_at' => '2026-08-31 10:17:00',
        ]);

        foreach (['projects', 'projects'] as $target) {
            UiEvent::query()->create([
                'user_id' => $user->id,
                'user_session_id' => $session->id,
                'event_name' => 'navigation.clicked',
                'page' => 'dashboard',
                'target' => $target,
                'occurred_at' => '2026-08-31 10:05:00',
            ]);
        }

        $this->artisan('activity:aggregate-analytics', [
            '--from' => '2026-08-31',
            '--to' => '2026-08-31',
        ])->assertSuccessful();

        $uiMetric = ActivityDailyMetric::query()->where('metric_type', 'ui_event')->firstOrFail();
        $sessionMetric = ActivityDailyMetric::query()->where('metric_type', 'session')->firstOrFail();

        $this->assertSame(2, $uiMetric->event_count);
        $this->assertSame(1, $uiMetric->session_count);
        $this->assertSame('navigation.clicked', $uiMetric->dimension_key);
        $this->assertSame('projects', $uiMetric->dimensions['target']);
        $this->assertSame(1, $sessionMetric->session_count);
        $this->assertSame(900, $sessionMetric->active_seconds);
        $this->assertSame(120, $sessionMetric->idle_seconds);

        $this->artisan('activity:aggregate-analytics', ['--from' => '2026-08-31'])
            ->assertSuccessful();

        $this->assertSame(2, ActivityDailyMetric::query()->count());
        $this->assertSame(2, ActivityDailyMetric::query()->where('metric_type', 'ui_event')->value('event_count'));
    }

    public function test_retention_command_never_deletes_raw_telemetry(): void
    {
        Carbon::setTestNow('2026-09-01 12:00:00');
        config([
            'activity.ui_retention_days' => 90,
            'activity.session_retention_days' => 365,
        ]);
        $user = User::factory()->create();
        $session = UserSession::query()->create([
            'user_id' => $user->id,
            'session_key_hash' => hash('sha256', 'old-session'),
            'channel' => 'web',
            'started_at' => now()->subDays(500),
            'ended_at' => now()->subDays(499),
        ]);
        $uiEvent = UiEvent::query()->create([
            'user_id' => $user->id,
            'user_session_id' => $session->id,
            'event_name' => 'dashboard.viewed',
            'page' => 'dashboard',
            'occurred_at' => now()->subDays(120),
        ]);

        $this->artisan('activity:prune')
            ->expectsOutput('Modo seguro: no se eliminó ningún registro.')
            ->assertSuccessful();

        $this->assertDatabaseHas('user_sessions', ['id' => $session->id]);
        $this->assertDatabaseHas('ui_events', ['id' => $uiEvent->id]);
    }
}
