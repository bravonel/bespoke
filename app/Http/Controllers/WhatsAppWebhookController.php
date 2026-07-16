<?php

namespace App\Http\Controllers;

use App\Services\WhatsApp\WhatsAppWebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $valid = $request->query('hub_mode') === 'subscribe'
            && filled(config('services.whatsapp.verify_token'))
            && hash_equals(
                (string) config('services.whatsapp.verify_token'),
                (string) $request->query('hub_verify_token')
            );

        if (! $valid) {
            return response('Webhook no autorizado.', 403);
        }

        return response((string) $request->query('hub_challenge'), 200)
            ->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request, WhatsAppWebhookHandler $handler): Response
    {
        $rawPayload = $request->getContent();

        if (! $handler->signatureIsValid($rawPayload, $request->header('X-Hub-Signature-256'))) {
            return response('Firma inválida.', 401);
        }

        $payload = json_decode($rawPayload, true);

        if (! is_array($payload)) {
            return response('Payload inválido.', 400);
        }

        $handler->ingest($payload);

        return response('EVENT_RECEIVED', 200);
    }
}
