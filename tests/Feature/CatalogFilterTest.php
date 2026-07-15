<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_clients_can_be_searched(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('clients.index', ['q' => 'Roche diagnóstica']))
            ->assertOk()
            ->assertSee('Roche diagnóstica')
            ->assertDontSee('Grunenthal');
    }

    public function test_brands_can_be_filtered_by_client(): void
    {
        $user = User::factory()->create();
        $grunenthal = Client::query()->where('name', 'Grunenthal')->firstOrFail();

        $this->actingAs($user)
            ->get(route('brands.index', ['client_id' => $grunenthal->id]))
            ->assertOk()
            ->assertSee('Dicynone')
            ->assertSee('Palexia')
            ->assertDontSee('Evrisdy');
    }
}
