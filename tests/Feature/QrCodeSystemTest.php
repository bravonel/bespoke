<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_dynamic_qr_code(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create(['name' => 'Cliente QR', 'status' => 'active']);

        $response = $this->actingAs($user)->post(route('qr-codes.store'), [
            'name' => 'Congreso 2026',
            'destination_url' => 'https://example.com/primera-version',
            'client_id' => $client->id,
            'status' => 'active',
            'foreground' => '#123456',
            'background' => '#FFFFFF',
            'dots' => 'rounded',
            'corners' => 'extra-rounded',
            'frame' => 'ticket',
            'cta' => 'CONOCE MÁS',
        ]);

        $qrCode = QrCode::query()->firstOrFail();

        $response->assertRedirect(route('qr-codes.show', $qrCode));
        $this->assertSame('Congreso 2026', $qrCode->name);
        $this->assertSame('#123456', $qrCode->design['foreground']);
        $this->assertSame('ticket', $qrCode->design['frame']);
        $this->assertSame($user->id, $qrCode->created_by);
    }

    public function test_public_redirect_records_an_anonymized_scan(): void
    {
        $qrCode = $this->qrCode();

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.18'])
            ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1')
            ->get(route('qr.redirect', $qrCode->slug));

        $response->assertRedirect('https://example.com/destino');
        $this->assertDatabaseHas('qr_scans', [
            'qr_code_id' => $qrCode->id,
            'device' => 'mobile',
            'browser' => 'Safari',
        ]);
        $this->assertDatabaseMissing('qr_scans', ['ip_hash' => '203.0.113.18']);
        $this->assertSame(1, $qrCode->fresh()->scans_count);
        $this->assertNotNull($qrCode->fresh()->last_scanned_at);
    }

    public function test_destination_can_change_without_changing_the_public_qr_url(): void
    {
        $user = User::factory()->create();
        $qrCode = $this->qrCode();
        $slug = $qrCode->slug;

        $this->actingAs($user)->patch(route('qr-codes.update', $qrCode), [
            'name' => $qrCode->name,
            'destination_url' => 'https://example.com/nuevo-destino',
            'status' => 'active',
            'foreground' => '#161616',
            'background' => '#FFFFFF',
            'dots' => 'square',
            'corners' => 'square',
            'frame' => 'none',
            'cta' => 'ESCANEA',
        ])->assertRedirect(route('qr-codes.show', $qrCode));

        $this->assertSame($slug, $qrCode->fresh()->slug);
        $this->get(route('qr.redirect', $slug))->assertRedirect('https://example.com/nuevo-destino');
    }

    public function test_paused_qr_does_not_redirect_or_record_a_scan(): void
    {
        $qrCode = $this->qrCode(['status' => 'paused']);

        $this->get(route('qr.redirect', $qrCode->slug))
            ->assertStatus(410)
            ->assertSee('Esta señal está en pausa');

        $this->assertDatabaseCount('qr_scans', 0);
    }

    public function test_analytics_page_summarizes_scans(): void
    {
        $user = User::factory()->create();
        $qrCode = $this->qrCode();
        $qrCode->scans()->create([
            'ip_hash' => hash('sha256', 'visitor'),
            'device' => 'mobile',
            'browser' => 'Safari',
            'city' => 'Ciudad de México',
            'country' => 'MX',
            'created_at' => now(),
        ]);
        $qrCode->update(['scans_count' => 1, 'last_scanned_at' => now()]);

        $this->actingAs($user)
            ->get(route('qr-codes.show', $qrCode))
            ->assertOk()
            ->assertSee('Ciudad de México')
            ->assertSee('Móvil')
            ->assertSee('Personas aprox.');
    }

    private function qrCode(array $overrides = []): QrCode
    {
        return QrCode::query()->create(array_merge([
            'name' => 'QR de prueba',
            'slug' => 'prueba123',
            'destination_url' => 'https://example.com/destino',
            'status' => 'active',
            'design' => [],
        ], $overrides));
    }
}
