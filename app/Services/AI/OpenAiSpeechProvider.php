<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class OpenAiSpeechProvider implements AiSpeechProvider
{
    /**
     * @return array{body: string, mime: string}
     */
    public function synthesize(string $text): array
    {
        $apiKey = config('services.openai.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('Falta configurar OPENAI_API_KEY para activar la voz del asistente.');
        }

        $baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
        $model = (string) config('services.openai.tts_model', 'gpt-4o-mini-tts');
        $voice = (string) config('services.openai.tts_voice', 'coral');

        $response = Http::asJson()
            ->accept('audio/mpeg')
            ->withToken($apiKey)
            ->timeout(45)
            ->post($baseUrl.'/audio/speech', [
                'model' => $model,
                'voice' => $voice,
                'input' => Str::limit(trim($text), 4096, ''),
                'instructions' => 'Habla en español mexicano, con tono claro, ejecutivo y natural.',
                'response_format' => 'mp3',
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                $response->json('error.message') ?: 'No se pudo generar el audio de OpenAI.'
            );
        }

        return [
            'body' => $response->body(),
            'mime' => $response->header('Content-Type') ?: 'audio/mpeg',
        ];
    }
}
