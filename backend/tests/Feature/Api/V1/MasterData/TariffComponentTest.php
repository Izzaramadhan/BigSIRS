<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\TariffComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TariffComponentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_tariff_components()
    {
        TariffComponent::factory()->count(15)->create();

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/tariff-components');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['id', 'legacy_id', 'name', 'description', 'is_active', 'created_at', 'updated_at']
                     ],
                     'meta',
                     'links'
                 ]);
                 
        $this->assertCount(10, $response->json('data')); // default per_page is 10
    }

    public function test_can_search_tariff_components_by_name()
    {
        TariffComponent::factory()->create(['name' => 'Jasa Medis']);
        TariffComponent::factory()->create(['name' => 'Biaya Obat']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/tariff-components?search=Medis');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Jasa Medis', $response->json('data.0.name'));
    }

    public function test_can_create_tariff_component_and_normalizes_input()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/tariff-components', [
            'name' => '  BHP   Medis  ',
            'description' => '   ',
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $this->assertEquals('BHP Medis', $response->json('data.name'));
        $this->assertNull($response->json('data.description'));

        $this->assertDatabaseHas('tariff_components', [
            'name' => 'BHP Medis',
            'description' => null,
            'is_active' => 1,
        ]);
    }

    public function test_cannot_create_tariff_component_without_name()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/tariff-components', [
            'description' => 'Test',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_tariff_component()
    {
        $component = TariffComponent::factory()->create([
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/tariff-components/{$component->id}", [
            'name' => 'New Name',
            'is_active' => false,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('New Name', $response->json('data.name'));
        
        $this->assertDatabaseHas('tariff_components', [
            'id' => $component->id,
            'name' => 'New Name',
            'is_active' => 0,
        ]);
    }

    public function test_can_update_status_only()
    {
        $component = TariffComponent::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->patchJson("/api/v1/master-data/tariff-components/{$component->id}/status", [
            'is_active' => false,
        ]);

        $response->assertStatus(200);
        $this->assertFalse($response->json('data.is_active'));
    }

    public function test_can_soft_delete_tariff_component()
    {
        $component = TariffComponent::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/tariff-components/{$component->id}");

        $response->assertStatus(204);
        
        $this->assertSoftDeleted('tariff_components', [
            'id' => $component->id,
        ]);
    }
}
