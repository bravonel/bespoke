<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QrCodeSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_dynamic_qr_code(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create(['name' => 'Cliente QR', 'status' => 'active']);

        $this->actingAs($user)
            ->get(route('qr-codes.create'))
            ->assertOk()
            ->assertSee('Parámetros UTM')
            ->assertSee('Vista previa de URL');

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
            'utm_enabled' => '1',
            'utm_source' => 'congreso',
            'utm_medium' => 'qr',
            'utm_campaign' => 'cardiologia-2026',
            'custom_parameters' => [
                ['key' => 'stand', 'value' => 'a-12'],
            ],
        ]);

        $qrCode = QrCode::query()->firstOrFail();

        $response->assertRedirect(route('qr-codes.show', $qrCode));
        $this->assertSame('Congreso 2026', $qrCode->name);
        $this->assertSame('#123456', $qrCode->design['foreground']);
        $this->assertSame('ticket', $qrCode->design['frame']);
        $this->assertTrue($qrCode->tracking_parameters['enabled']);
        $this->assertSame('cardiologia-2026', $qrCode->tracking_parameters['utm_campaign']);
        $this->assertSame([['key' => 'stand', 'value' => 'a-12']], $qrCode->tracking_parameters['custom']);
        $this->assertSame($user->id, $qrCode->created_by);
    }

    public function test_dynamic_redirect_adds_utm_and_custom_parameters_to_the_destination(): void
    {
        $qrCode = $this->qrCode([
            'destination_url' => 'https://example.com/landing?lang=es&utm_source=anterior#registro',
            'tracking_parameters' => [
                'enabled' => true,
                'utm_source' => 'folleto',
                'utm_medium' => 'qr',
                'utm_campaign' => 'lanzamiento 2026',
                'utm_term' => '',
                'utm_content' => 'portada',
                'custom' => [
                    ['key' => 'stand', 'value' => 'A 12'],
                ],
            ],
        ]);

        $this->get(route('qr.redirect', $qrCode->slug))
            ->assertRedirect('https://example.com/landing?lang=es&utm_source=folleto&utm_medium=qr&utm_campaign=lanzamiento%202026&utm_content=portada&stand=A%2012#registro');
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

    public function test_authenticated_user_can_load_a_qr_logo_without_a_public_storage_link(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('qr-logos/cliente.png', 'logo-content');

        $user = User::factory()->create();
        $qrCode = $this->qrCode(['logo_path' => 'qr-logos/cliente.png']);

        $this->actingAs($user)
            ->get(route('qr-codes.logo', $qrCode))
            ->assertOk()
            ->assertHeader('cache-control', 'max-age=3600, private')
            ->assertStreamedContent('logo-content');
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

    public function test_authenticated_user_can_open_the_print_ready_qr_page(): void
    {
        $user = User::factory()->create();
        $qrCode = $this->qrCode([
            'name' => 'QR listo para imprimir',
            'design' => ['frame' => 'ticket', 'cta' => 'CONOCE MÁS'],
        ]);

        $this->actingAs($user)
            ->get(route('qr-codes.print', $qrCode))
            ->assertOk()
            ->assertSee('QR listo para imprimir')
            ->assertSee('CONOCE MÁS')
            ->assertSee('Imprimir QR');
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
