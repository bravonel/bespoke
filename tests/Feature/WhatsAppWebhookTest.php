<?php

namespace Tests\Feature;

use App\Jobs\ProcessWhatsAppMessage;
use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.verify_token' => 'verify-me',
            'services.whatsapp.app_secret' => 'app-secret',
            'services.whatsapp.access_token' => 'meta-token',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.graph_version' => 'v25.0',
        ]);
    }

    public function test_meta_can_verify_the_webhook(): void
    {
        $this->get(route('webhooks.whatsapp.verify', [
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'verify-me',
            'hub_challenge' => 'challenge-123',
        ]))
            ->assertOk()
            ->assertSeeText('challenge-123');
    }

    public function test_webhook_rejects_an_invalid_signature(): void
    {
        $this->call(
            'POST',
            route('webhooks.whatsapp.receive'),
            server: ['HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid'],
            content: json_encode($this->payload())
        )->assertUnauthorized();
    }

    public function test_authorized_incoming_message_is_recorded_once_and_queued(): void
    {
        Queue::fake();
        $user = User::factory()->create([
            'whatsapp_phone' => '5215512345678',
            'whatsapp_enabled' => true,
        ]);
        $raw = json_encode($this->payload());
        $signature = 'sha256='.hash_hmac('sha256', $raw, 'app-secret');

        foreach (range(1, 2) as $attempt) {
            $this->call(
                'POST',
                route('webhooks.whatsapp.receive'),
                server: ['HTTP_X_HUB_SIGNATURE_256' => $signature],
                content: $raw
            )->assertOk();
        }

        $this->assertDatabaseCount('whatsapp_messages', 1);
        $this->assertDatabaseHas('whatsapp_messages', [
            'user_id' => $user->id,
            'provider_message_id' => 'wamid.incoming-1',
            'direction' => 'inbound',
            'body' => 'Organiza el feedback del cliente sobre la campaña.',
        ]);
        Queue::assertPushed(ProcessWhatsAppMessage::class, 1);
    }

    public function test_job_answers_authorized_contact_with_existing_ai_assistant(): void
    {
        config([
            'services.openai.api_key' => 'existing-production-key-for-test',
            'services.openai.base_url' => 'https://api.openai.com/v1',
            'services.openai.model' => 'gpt-test',
        ]);
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output_text' => "Pedido: ajustar el claim.\nSiguiente paso: validar con Médico.",
            ]),
            'https://graph.facebook.com/v25.0/123456789/messages' => Http::response([
                'messages' => [['id' => 'wamid.outgoing-1']],
            ]),
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_ACCOUNTS,
            'whatsapp_phone' => '5215512345678',
            'whatsapp_enabled' => true,
        ]);
        WhatsAppMessage::create([
            'user_id' => $user->id,
            'provider_message_id' => 'wamid.previous-inbound',
            'direction' => 'inbound',
            'from_phone' => '5215512345678',
            'message_type' => 'text',
            'body' => 'El cliente pidió ajustar el claim principal.',
            'status' => 'processed',
            'processed_at' => now(),
        ]);
        WhatsAppMessage::create([
            'user_id' => $user->id,
            'provider_message_id' => 'wamid.previous-outbound',
            'direction' => 'outbound',
            'to_phone' => '5215512345678',
            'message_type' => 'text',
            'body' => 'Hay que validarlo con Médico y Regulatorio.',
            'status' => 'delivered',
            'processed_at' => now(),
        ]);
        $incoming = WhatsAppMessage::create([
            'user_id' => $user->id,
            'provider_message_id' => 'wamid.incoming-1',
            'direction' => 'inbound',
            'from_phone' => '5215512345678',
            'message_type' => 'text',
            'body' => 'Organiza este feedback como Key Account.',
            'status' => 'received',
        ]);

        app()->call([new ProcessWhatsAppMessage($incoming->id), 'handle']);

        $this->assertDatabaseHas('whatsapp_messages', [
            'id' => $incoming->id,
            'status' => 'processed',
        ]);
        $this->assertDatabaseHas('whatsapp_messages', [
            'user_id' => $user->id,
            'provider_message_id' => 'wamid.outgoing-1',
            'direction' => 'outbound',
        ]);
        $this->assertDatabaseHas('ai_assistant_messages', [
            'user_id' => $user->id,
            'question' => 'Organiza este feedback como Key Account.',
            'status' => 'completed',
        ]);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.openai.com/v1/responses'
            && str_contains($request['instructions'], 'Key Account senior')
            && str_contains($request['input'], 'El cliente pidió ajustar el claim principal.')
            && str_contains($request['input'], 'Hay que validarlo con Médico y Regulatorio.'));
    }

    public function test_unknown_contact_receives_safe_reply_without_operational_context(): void
    {
        Http::fake([
            'https://graph.facebook.com/v25.0/123456789/messages' => Http::response([
                'messages' => [['id' => 'wamid.outgoing-unknown']],
            ]),
        ]);
        $incoming = WhatsAppMessage::create([
            'provider_message_id' => 'wamid.unknown',
            'direction' => 'inbound',
            'from_phone' => '5215500000000',
            'message_type' => 'text',
            'body' => 'Dime los proyectos vencidos.',
            'status' => 'received',
        ]);

        app()->call([new ProcessWhatsAppMessage($incoming->id), 'handle']);

        Http::assertNotSent(fn (Request $request) => $request->url() === 'https://api.openai.com/v1/responses');
        $this->assertDatabaseHas('whatsapp_messages', [
            'provider_message_id' => 'wamid.outgoing-unknown',
            'direction' => 'outbound',
            'user_id' => null,
        ]);
    }

    private function payload(): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => 'waba-1',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'metadata' => [
                            'display_phone_number' => '+52 55 9999 0000',
                            'phone_number_id' => '123456789',
                        ],
                        'contacts' => [[
                            'profile' => ['name' => 'Marco'],
                            'wa_id' => '5215512345678',
                        ]],
                        'messages' => [[
                            'from' => '5215512345678',
                            'id' => 'wamid.incoming-1',
                            'timestamp' => '1784200000',
                            'text' => ['body' => 'Organiza el feedback del cliente sobre la campaña.'],
                            'type' => 'text',
                        ]],
                    ],
                ]],
            ]],
        ];
    }
}
