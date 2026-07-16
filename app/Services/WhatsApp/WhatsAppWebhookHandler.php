<?php

namespace App\Services\WhatsApp;

use App\Jobs\ProcessWhatsAppMessage;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Arr;

class WhatsAppWebhookHandler
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function signatureIsValid(string $rawPayload, ?string $signature): bool
    {
        $secret = (string) config('services.whatsapp.app_secret');

        if ($secret === '' || ! is_string($signature) || ! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $rawPayload, $secret);

        return hash_equals($expected, $signature);
    }

    public function ingest(array $payload): void
    {
        foreach (Arr::get($payload, 'entry', []) as $entry) {
            foreach (Arr::get($entry, 'changes', []) as $change) {
                $value = Arr::get($change, 'value', []);
                $this->recordStatuses(Arr::get($value, 'statuses', []));

                foreach (Arr::get($value, 'messages', []) as $incoming) {
                    $this->recordIncoming($incoming, $value);
                }
            }
        }
    }

    private function recordIncoming(array $incoming, array $value): void
    {
        $providerId = Arr::get($incoming, 'id');

        if (! is_string($providerId) || $providerId === '') {
            return;
        }

        $from = PhoneNumber::normalize(Arr::get($incoming, 'from'));
        $user = $from ? User::query()
            ->active()
            ->where('whatsapp_enabled', true)
            ->where('whatsapp_phone', $from)
            ->first() : null;

        $message = WhatsAppMessage::query()->firstOrCreate(
            ['provider_message_id' => $providerId],
            [
                'user_id' => $user?->id,
                'direction' => 'inbound',
                'from_phone' => $from,
                'to_phone' => PhoneNumber::normalize(Arr::get($value, 'metadata.display_phone_number')),
                'message_type' => (string) Arr::get($incoming, 'type', 'unknown'),
                'body' => Arr::get($incoming, 'text.body'),
                'status' => 'received',
                'metadata' => [
                    'provider' => 'meta_cloud_api',
                    'provider_timestamp' => Arr::get($incoming, 'timestamp'),
                    'contact_name' => Arr::get($value, 'contacts.0.profile.name'),
                ],
            ]
        );

        if (! $message->wasRecentlyCreated) {
            return;
        }

        $this->audit->record('whatsapp.message.received', $message, $user, [
            'direction' => 'inbound',
            'message_type' => $message->message_type,
            'authorized_contact' => $user !== null,
        ]);

        ProcessWhatsAppMessage::dispatch($message->id)->afterCommit();
    }

    private function recordStatuses(array $statuses): void
    {
        foreach ($statuses as $status) {
            $providerId = Arr::get($status, 'id');

            if (! is_string($providerId)) {
                continue;
            }

            WhatsAppMessage::query()
                ->where('provider_message_id', $providerId)
                ->where('direction', 'outbound')
                ->update(['status' => (string) Arr::get($status, 'status', 'unknown')]);
        }
    }
}
