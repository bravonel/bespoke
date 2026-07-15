<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiProvider implements AiProvider
{
    public function respond(string $instructions, string $input): string
    {
        $apiKey = config('services.openai.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('Falta configurar OPENAI_API_KEY para activar el asistente.');
        }

        $baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
        $model = (string) config('services.openai.model', 'gpt-5.6');

        $response = Http::asJson()
            ->withToken($apiKey)
            ->timeout(45)
            ->post($baseUrl.'/responses', [
                'model' => $model,
                'reasoning' => ['effort' => 'low'],
                'instructions' => $instructions,
                'input' => $input,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                $response->json('error.message') ?: 'No se pudo obtener respuesta de OpenAI.'
            );
        }

        $payload = $response->json();
        $text = data_get($payload, 'output_text');

        if (! is_string($text) || trim($text) === '') {
            $text = collect(data_get($payload, 'output', []))
                ->flatMap(fn (array $item) => $item['content'] ?? [])
                ->map(fn (array $content) => $content['text'] ?? null)
                ->filter(fn ($piece) => is_string($piece) && trim($piece) !== '')
                ->implode("\n");
        }

        if (trim((string) $text) === '') {
            throw new RuntimeException('La IA no devolvió contenido de texto.');
        }

        return trim((string) $text);
    }
}
