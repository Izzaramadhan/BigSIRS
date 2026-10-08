<?php

namespace Tests\Feature\MasterData;

use App\Models\MasterData\RadiologyType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RadiologyTypeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected string $endpoint = '/api/v1/master-data/radiology-types';

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_can_list_radiology_types_with_pagination()
    {
        RadiologyType::factory()->count(15)->create();

        $response = $this->actingAs($this->admin)->getJson($this->endpoint);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'is_active', 'created_at', 'updated_at'],
                ],
                'links',
                'meta',
            ])
            ->assertJsonPath('meta.total', 15);
    }

    public function test_can_search_radiology_types()
    {
        RadiologyType::factory()->create(['name' => 'UMUM/RESIDEN']);
        RadiologyType::factory()->create(['name' => 'KOAS']);

        $response = $this->actingAs($this->admin)->getJson($this->endpoint.'?search=UMUM');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'UMUM/RESIDEN');
    }

    public function test_can_filter_by_status()
    {
        RadiologyType::factory()->create(['is_active' => true]);
        RadiologyType::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->admin)->getJson($this->endpoint.'?is_active=1');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_active', true);
    }

    public function test_can_create_radiology_type()
    {
        $data = [
            'name' => 'New Type',
            'description' => 'Description test',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->postJson($this->endpoint, $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Type');

        $this->assertDatabaseHas('radiology_types', [
            'name' => 'New Type',
            'description' => 'Description test',
        ]);
    }

    public function test_cannot_create_duplicate_radiology_type()
    {
        RadiologyType::factory()->create(['name' => 'Duplicate Name']);

        $response = $this->actingAs($this->admin)->postJson($this->endpoint, [
            'name' => 'Duplicate Name',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_radiology_type()
    {
        $type = RadiologyType::factory()->create();

        $response = $this->actingAs($this->admin)->putJson("{$this->endpoint}/{$type->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('radiology_types', [
            'id' => $type->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_soft_delete_radiology_type()
    {
        $type = RadiologyType::factory()->create();

        $response = $this->actingAs($this->admin)->deleteJson("{$this->endpoint}/{$type->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('radiology_types', [
            'id' => $type->id,
        ]);
    }

    public function test_lookup_returns_active_types_only()
    {
        RadiologyType::factory()->create(['name' => 'Active Type', 'is_active' => true]);
        RadiologyType::factory()->create(['name' => 'Inactive Type', 'is_active' => false]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/lookups/radiology-types');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
        $this->assertEquals('Active Type', $response->json()[0]['name']);
    }
}
