<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class ActivityEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logger_sanitizes_sensitive_metadata(): void
    {
        $user = User::factory()->create();

        $event = app(AuditLogger::class)->record('user.tested', $user, $user, [
            'safe' => 'visible',
            'password' => 'never-store-this',
            'nested' => [
                'api_key' => 'secret-key',
                'value' => 'allowed',
            ],
        ]);

        $this->assertSame('visible', $event->metadata['safe']);
        $this->assertArrayNotHasKey('password', $event->metadata);
        $this->assertArrayNotHasKey('api_key', $event->metadata['nested']);
        $this->assertSame('allowed', $event->metadata['nested']['value']);
    }

    public function test_activity_events_cannot_be_updated_or_deleted_through_model(): void
    {
        $user = User::factory()->create();
        $event = app(AuditLogger::class)->record('user.tested', $user, $user);

        try {
            $event->update(['event_type' => 'changed']);
            $this->fail('El evento permitió una actualización.');
        } catch (LogicException) {
            $this->assertSame('user.tested', $event->fresh()->event_type);
        }

        $this->expectException(LogicException::class);
        $event->delete();
    }
}
