<?php

namespace App\Services\AI;

use App\Models\AiAssistantMessage;
use App\Models\Project;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Throwable;

class AiAssistant
{
    public function __construct(
        private readonly AiContextBuilder $contextBuilder,
        private readonly AiProvider $provider,
        private readonly AuditLogger $audit,
    ) {}

    public function answer(
        User $user,
        string $question,
        ?string $contextType = null,
        ?int $contextId = null,
        string $channel = 'web',
        array $conversation = [],
    ): array {
        $built = $this->contextBuilder->build($user, $contextType, $contextId, $question);
        $contextClass = $built['diagnostics']['context_type'] === Project::class ? Project::class : null;
        $contextKey = $contextClass ? $built['diagnostics']['context_id'] : null;

        $message = AiAssistantMessage::create([
            'user_id' => $user->id,
            'context_type' => $contextClass,
            'context_id' => $contextKey,
            'question' => $question,
            'sources' => $built['sources'],
            'diagnostics' => [...$built['diagnostics'], 'channel' => $channel],
            'status' => 'pending',
        ]);
        $this->audit->record('ai.question_asked', $message, $user, [
            'context_type' => $contextType,
            'context_id' => $contextId,
        ], $channel);

        try {
            $answer = $this->provider->respond(
                $this->instructions($channel),
                $this->input($question, $built['context'], $conversation)
            );

            $message->update([
                'answer' => $answer,
                'status' => 'completed',
            ]);
            $this->audit->record('ai.answer_completed', $message, $user, [
                'source_count' => count($built['sources']),
            ], $channel);

            return [
                'answer' => $answer,
                'sources' => $built['sources'],
                'message_id' => $message->id,
            ];
        } catch (Throwable $exception) {
            $message->update([
                'status' => 'failed',
                'diagnostics' => [
                    ...($message->diagnostics ?? []),
                    'error' => [
                        'class' => $exception::class,
                        'message' => $exception->getMessage(),
                    ],
                ],
            ]);
            $this->audit->record('ai.answer_failed', $message, $user, [
                'exception_class' => $exception::class,
            ], $channel, 'failed');

            throw $exception;
        }
    }

    private function instructions(string $channel): string
    {
        $base = <<<'PROMPT'
Eres el asistente operativo de Bespoke OS para una agencia médico-creativa.
Responde siempre en español claro y ejecutivo.
Usa únicamente el contexto JSON recibido. No inventes datos, fechas, nombres, horas ni estados.
Si la información no alcanza, dilo y sugiere qué dato falta en Bespoke OS.
Prioriza riesgos operativos: vencimientos, bloqueos, falta de responsable, falta de horas y sobrecarga.
Cuando recomiendes acciones, sepáralas de los hechos y no afirmes que ya ejecutaste cambios.
Incluye referencias breves a las fuentes disponibles por nombre de proyecto, tarea o dashboard.
PROMPT;

        if ($channel !== 'whatsapp') {
            return $base;
        }

        return $base.<<<'PROMPT'

Estás respondiendo dentro de WhatsApp. Sé conciso y fácil de escanear en móvil.
Cuando el usuario comparta feedback, actúa como un Key Account senior: separa lo pedido, implicaciones, pendientes, responsable sugerido y siguiente paso.
Aclara siempre qué parte es hecho registrado y qué parte es recomendación. No afirmes que modificaste tareas ni aprobaciones.
PROMPT;
    }

    private function input(string $question, array $context, array $conversation = []): string
    {
        return json_encode([
            'pregunta_usuario' => $question,
            'conversacion_reciente' => $conversation,
            'contexto_bespoke_os' => $context,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
