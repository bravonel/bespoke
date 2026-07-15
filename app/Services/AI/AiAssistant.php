<?php

namespace App\Services\AI;

use App\Models\AiAssistantMessage;
use App\Models\Project;
use App\Models\User;
use Throwable;

class AiAssistant
{
    public function __construct(
        private readonly AiContextBuilder $contextBuilder,
        private readonly AiProvider $provider,
    ) {}

    public function answer(User $user, string $question, ?string $contextType = null, ?int $contextId = null): array
    {
        $built = $this->contextBuilder->build($user, $contextType, $contextId, $question);
        $contextClass = $built['diagnostics']['context_type'] === Project::class ? Project::class : null;
        $contextKey = $contextClass ? $built['diagnostics']['context_id'] : null;

        $message = AiAssistantMessage::create([
            'user_id' => $user->id,
            'context_type' => $contextClass,
            'context_id' => $contextKey,
            'question' => $question,
            'sources' => $built['sources'],
            'diagnostics' => $built['diagnostics'],
            'status' => 'pending',
        ]);

        try {
            $answer = $this->provider->respond(
                $this->instructions(),
                $this->input($question, $built['context'])
            );

            $message->update([
                'answer' => $answer,
                'status' => 'completed',
            ]);

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

            throw $exception;
        }
    }

    private function instructions(): string
    {
        return <<<'PROMPT'
Eres el asistente operativo de Bespoke OS para una agencia médico-creativa.
Responde siempre en español claro y ejecutivo.
Usa únicamente el contexto JSON recibido. No inventes datos, fechas, nombres, horas ni estados.
Si la información no alcanza, dilo y sugiere qué dato falta en Bespoke OS.
Prioriza riesgos operativos: vencimientos, bloqueos, falta de responsable, falta de horas y sobrecarga.
Cuando recomiendes acciones, sepáralas de los hechos y no afirmes que ya ejecutaste cambios.
Incluye referencias breves a las fuentes disponibles por nombre de proyecto, tarea o dashboard.
PROMPT;
    }

    private function input(string $question, array $context): string
    {
        return json_encode([
            'pregunta_usuario' => $question,
            'contexto_bespoke_os' => $context,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
