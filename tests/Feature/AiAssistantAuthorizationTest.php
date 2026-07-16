<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use App\Services\AI\AiAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAssistantAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_collaborator_ai_context_only_contains_accessible_projects(): void
    {
        config([
            'services.openai.api_key' => 'test-key',
            'services.openai.base_url' => 'https://api.openai.com/v1',
            'services.openai.model' => 'gpt-test',
        ]);
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response(['output_text' => 'Resumen autorizado.']),
        ]);
        $collaborator = User::factory()->create(['role' => User::ROLE_DESIGN]);
        $owner = User::factory()->create(['role' => User::ROLE_ACCOUNTS]);
        $client = Client::create(['name' => 'Cliente', 'status' => 'active']);
        $visible = $this->project($client->id, $owner->id, 'Proyecto visible', 'ODT-VISIBLE');
        $hidden = $this->project($client->id, $owner->id, 'Proyecto secreto', 'ODT-SECRETO');
        ProjectMember::create([
            'project_id' => $visible->id,
            'user_id' => $collaborator->id,
            'project_role' => 'design',
            'status' => ProjectMember::STATUS_ACTIVE,
            'added_by' => $owner->id,
        ]);
        Task::create([
            'project_id' => $hidden->id,
            'title' => 'Dato confidencial ajeno',
            'status' => 'blocked',
            'priority' => 'high',
            'sort_order' => 0,
        ]);

        app(AiAssistant::class)->answer($collaborator, 'Dame un resumen general.', channel: 'whatsapp');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.openai.com/v1/responses'
            && str_contains($request['input'], 'Proyecto visible')
            && ! str_contains($request['input'], 'Proyecto secreto')
            && ! str_contains($request['input'], 'Dato confidencial ajeno'));
    }

    private function project(int $clientId, int $ownerId, string $name, string $code): Project
    {
        return Project::create([
            'client_id' => $clientId,
            'owner_id' => $ownerId,
            'name' => $name,
            'code' => $code,
            'project_type' => 'campana',
            'priority' => 'normal',
            'status' => 'active',
            'current_stage' => 'design',
        ]);
    }
}
