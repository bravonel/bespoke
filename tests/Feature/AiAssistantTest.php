<?php

namespace Tests\Feature;

use App\Models\AiAssistantMessage;
use App\Models\Brand;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_query_ai_assistant_with_operational_context(): void
    {
        config([
            'services.openai.api_key' => 'test-key',
            'services.openai.base_url' => 'https://api.openai.com/v1',
            'services.openai.model' => 'gpt-test',
        ]);

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output_text' => 'El principal riesgo es la tarea vencida de revisión médica en ODT-15001.',
            ]),
        ]);

        $user = User::factory()->create([
            'name' => 'Marco Torres',
            'area' => 'Dirección',
        ]);
        $client = Client::create([
            'name' => 'Roche',
            'status' => 'active',
        ]);
        $brand = Brand::create([
            'client_id' => $client->id,
            'name' => 'Evrysdi',
            'status' => 'active',
        ]);
        $project = Project::create([
            'client_id' => $client->id,
            'brand_id' => $brand->id,
            'owner_id' => $user->id,
            'name' => 'Video MOA',
            'code' => 'BSP-IA',
            'odt_code' => 'ODT-15001',
            'project_type' => 'video',
            'priority' => 'high',
            'status' => 'active',
            'current_stage' => 'medical_review',
            'due_at' => now()->subDay()->toDateString(),
        ]);

        Task::create([
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'title' => 'Revisión médica',
            'status' => 'blocked',
            'priority' => 'high',
            'planned_for' => today(),
            'due_at' => now()->subDay()->toDateString(),
            'estimated_minutes' => 120,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)->postJson(route('ai.assistant'), [
            'message' => 'Qué riesgo operativo ves en el proyecto ODT-15001?',
            'context_type' => 'project',
            'context_id' => $project->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('answer', 'El principal riesgo es la tarea vencida de revisión médica en ODT-15001.')
            ->assertJsonFragment([
                'label' => 'Proyecto: Video MOA',
                'url' => route('projects.show', $project, false),
            ]);

        $this->assertDatabaseHas('ai_assistant_messages', [
            'user_id' => $user->id,
            'context_type' => Project::class,
            'context_id' => $project->id,
            'question' => 'Qué riesgo operativo ves en el proyecto ODT-15001?',
            'status' => 'completed',
        ]);

        $message = AiAssistantMessage::firstOrFail();

        $this->assertSame('El principal riesgo es la tarea vencida de revisión médica en ODT-15001.', $message->answer);
        $this->assertNotEmpty($message->sources);
        $this->assertSame(1, $message->diagnostics['projects_sent']);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.openai.com/v1/responses'
            && $request['model'] === 'gpt-test'
            && str_contains($request['input'], 'ODT-15001')
            && str_contains($request['input'], 'Revisión médica'));
    }

    public function test_missing_openai_key_returns_clear_error_and_audits_failure(): void
    {
        config([
            'services.openai.api_key' => null,
            'services.openai.base_url' => 'https://api.openai.com/v1',
            'services.openai.model' => 'gpt-test',
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('ai.assistant'), [
            'message' => 'Dame un resumen operativo.',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Falta configurar OPENAI_API_KEY para activar el asistente.',
            ]);

        $this->assertDatabaseHas('ai_assistant_messages', [
            'user_id' => $user->id,
            'question' => 'Dame un resumen operativo.',
            'status' => 'failed',
        ]);
    }

    public function test_authenticated_user_can_generate_openai_speech_for_answer(): void
    {
        config([
            'services.openai.api_key' => 'test-key',
            'services.openai.base_url' => 'https://api.openai.com/v1',
            'services.openai.tts_model' => 'gpt-4o-mini-tts',
            'services.openai.tts_voice' => 'coral',
        ]);

        Http::fake([
            'https://api.openai.com/v1/audio/speech' => Http::response('fake-mp3', 200, [
                'Content-Type' => 'audio/mpeg',
            ]),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('ai.assistant.speech'), [
            'text' => 'Resumen operativo listo.',
        ]);

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg')
            ->assertContent('fake-mp3');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.openai.com/v1/audio/speech'
            && $request['model'] === 'gpt-4o-mini-tts'
            && $request['voice'] === 'coral'
            && $request['input'] === 'Resumen operativo listo.'
            && $request['response_format'] === 'mp3');
    }

    public function test_authenticated_layout_shows_voice_controls(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Micrófono')
            ->assertSee('Audio activo')
            ->assertSee('ai\\/assistant\\/speech', false);
    }

    public function test_missing_openai_key_returns_clear_error_for_speech(): void
    {
        config([
            'services.openai.api_key' => null,
            'services.openai.base_url' => 'https://api.openai.com/v1',
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('ai.assistant.speech'), [
            'text' => 'Resumen operativo listo.',
        ]);

        $response
            ->assertStatus(422)
            ->assertSeeText('Falta configurar OPENAI_API_KEY para activar la voz del asistente.');
    }
}
