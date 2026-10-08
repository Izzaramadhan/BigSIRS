<?php

namespace Tests\Feature\MasterData;

use App\Models\MasterData\RadiologyCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RadiologyCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected string $endpoint = '/api/v1/master-data/radiology-categories';

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_can_list_radiology_categories_with_pagination()
    {
        RadiologyCategory::factory()->count(15)->create();

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

    public function test_can_search_radiology_categories()
    {
        RadiologyCategory::factory()->create(['name' => 'ANALOG/KONVENSIONAL']);
        RadiologyCategory::factory()->create(['name' => 'DIGITAL (CD)']);

        $response = $this->actingAs($this->admin)->getJson($this->endpoint.'?search=ANALOG');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'ANALOG/KONVENSIONAL');
    }

    public function test_can_filter_by_status()
    {
        RadiologyCategory::factory()->create(['is_active' => true]);
        RadiologyCategory::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->admin)->getJson($this->endpoint.'?is_active=1');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_active', true);
    }

    public function test_can_create_radiology_category()
    {
        $data = [
            'name' => 'New Category',
            'description' => 'Description test',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->postJson($this->endpoint, $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Category');

        $this->assertDatabaseHas('radiology_categories', [
            'name' => 'New Category',
            'description' => 'Description test',
        ]);
    }

    public function test_cannot_create_duplicate_radiology_category()
    {
        RadiologyCategory::factory()->create(['name' => 'Duplicate Name']);

        $response = $this->actingAs($this->admin)->postJson($this->endpoint, [
            'name' => 'Duplicate Name',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_radiology_category()
    {
        $category = RadiologyCategory::factory()->create();

        $response = $this->actingAs($this->admin)->putJson("{$this->endpoint}/{$category->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('radiology_categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_soft_delete_radiology_category()
    {
        $category = RadiologyCategory::factory()->create();

        $response = $this->actingAs($this->admin)->deleteJson("{$this->endpoint}/{$category->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('radiology_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_lookup_returns_active_categories_only()
    {
        RadiologyCategory::factory()->create(['name' => 'Active Category', 'is_active' => true]);
        RadiologyCategory::factory()->create(['name' => 'Inactive Category', 'is_active' => false]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/lookups/radiology-categories');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
        $this->assertEquals('Active Category', $response->json()[0]['name']);
    }
}
