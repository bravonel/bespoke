<?php

namespace App\Http\Controllers;

use App\Services\AI\AiSpeechProvider;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AiSpeechController extends Controller
{
    public function __invoke(Request $request, AiSpeechProvider $speech): Response
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:4096'],
        ]);

        try {
            $audio = $speech->synthesize($validated['text']);

            return response($audio['body'], 200, [
                'Content-Type' => $audio['mime'],
                'Cache-Control' => 'no-store, private',
            ]);
        } catch (RuntimeException $exception) {
            return response($exception->getMessage(), 422, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response('No se pudo generar el audio del asistente.', 500, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }
    }
}
