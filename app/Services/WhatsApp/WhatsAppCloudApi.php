<?php

namespace App\Services\WhatsApp;

use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class WhatsAppCloudApi
{
    public function sendText(string $to, string $body, ?User $user = null, ?int $aiMessageId = null): WhatsAppMessage
    {
        $token = config('services.whatsapp.access_token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        if (blank($token) || blank($phoneNumberId)) {
            throw new RuntimeException('Falta configurar WhatsApp Cloud API.');
        }

        $body = Str::limit(trim($body), 4000, '…');
        $version = config('services.whatsapp.graph_version', 'v25.0');
        $response = Http::asJson()
            ->withToken($token)
            ->timeout(20)
            ->post("https://graph.facebook.com/{$version}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => PhoneNumber::normalize($to),
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $body,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                $response->json('error.message') ?: 'WhatsApp no aceptó el mensaje.'
            );
        }

        return WhatsAppMessage::query()->create([
            'user_id' => $user?->id,
            'ai_assistant_message_id' => $aiMessageId,
            'provider_message_id' => $response->json('messages.0.id'),
            'direction' => 'outbound',
            'from_phone' => (string) $phoneNumberId,
            'to_phone' => PhoneNumber::normalize($to),
            'message_type' => 'text',
            'body' => $body,
            'status' => 'accepted',
            'metadata' => ['provider' => 'meta_cloud_api'],
            'processed_at' => now(),
        ]);
    }
}
