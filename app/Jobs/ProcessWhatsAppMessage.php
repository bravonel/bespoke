<?php

namespace App\Jobs;

use App\Models\AiAssistantMessage;
use App\Models\WhatsAppMessage;
use App\Services\AI\AiAssistant;
use App\Services\Audit\AuditLogger;
use App\Services\WhatsApp\WhatsAppCloudApi;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 75;

    public function __construct(public readonly int $messageId) {}

    public function handle(AiAssistant $assistant, WhatsAppCloudApi $whatsapp, AuditLogger $audit): void
    {
        $message = WhatsAppMessage::query()->with('user')->findOrFail($this->messageId);

        if ($message->processed_at) {
            return;
        }

        $reply = null;
        $aiMessageId = null;

        try {
            if (! $message->user) {
                $reply = 'Este número todavía no está autorizado en Bespoke OS. Pide a un administrador que active tu acceso de WhatsApp.';
            } elseif ($message->message_type !== 'text' || blank($message->body)) {
                $reply = 'Por ahora puedo atender mensajes de texto. Escríbeme tu pregunta o pega aquí el feedback que quieres organizar.';
            } else {
                $result = $assistant->answer(
                    $message->user,
                    trim($message->body),
                    channel: 'whatsapp',
                    conversation: $this->recentConversation($message),
                );
                $reply = $result['answer'];
                $aiMessageId = $result['message_id'];

                AiAssistantMessage::query()->whereKey($aiMessageId)->update([
                    'diagnostics->whatsapp_message_id' => $message->id,
                ]);
            }

            $outbound = $whatsapp->sendText(
                $message->from_phone,
                $reply,
                $message->user,
                $aiMessageId,
            );

            $message->update([
                'ai_assistant_message_id' => $aiMessageId,
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            $audit->record('whatsapp.message.replied', $outbound, $message->user, [
                'inbound_message_id' => $message->id,
                'used_ai' => $aiMessageId !== null,
            ]);
        } catch (Throwable $exception) {
            $message->update([
                'status' => 'failed',
                'metadata' => [
                    ...($message->metadata ?? []),
                    'failure' => $exception::class,
                ],
            ]);

            throw $exception;
        }
    }

    private function recentConversation(WhatsAppMessage $current): array
    {
        return WhatsAppMessage::query()
            ->where('user_id', $current->user_id)
            ->where('id', '<', $current->id)
            ->whereNotNull('body')
            ->whereIn('direction', ['inbound', 'outbound'])
            ->latest('id')
            ->limit(6)
            ->get(['direction', 'body'])
            ->reverse()
            ->values()
            ->map(fn (WhatsAppMessage $message) => [
                'rol' => $message->direction === 'inbound' ? 'usuario' : 'asistente',
                'contenido' => $message->body,
            ])
            ->all();
    }
}
