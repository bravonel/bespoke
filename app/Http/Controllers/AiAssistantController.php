<?php

namespace App\Http\Controllers;

use App\Services\AI\AiAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class AiAssistantController extends Controller
{
    public function __invoke(Request $request, AiAssistant $assistant): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'context_type' => ['nullable', 'string', Rule::in(['project'])],
            'context_id' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            return response()->json($assistant->answer(
                $request->user(),
                trim($validated['message']),
                $validated['context_type'] ?? null,
                $validated['context_id'] ?? null,
            ));
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'No se pudo generar la respuesta del asistente.',
            ], 500);
        }
    }
}
