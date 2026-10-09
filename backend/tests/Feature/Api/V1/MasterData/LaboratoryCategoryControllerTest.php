<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\LaboratoryCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaboratoryCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_can_list_categories()
    {
        LaboratoryCategory::factory()->count(15)->create();

        $response = $this->getJson('/api/v1/master-data/laboratory-categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'type', 'loinc_code', 'loinc_url', 'snomed_code', 'snomed_url', 'is_active'],
                ],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    }

    public function test_can_create_category()
    {
        $payload = [
            'name' => 'Hematologi Rutin',
            'description' => 'Pemeriksaan darah lengkap',
            'type' => 'lab klinik',
            'loinc_code' => '123-4',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/master-data/laboratory-categories', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Hematologi Rutin');

        $this->assertDatabaseHas('laboratory_categories', ['name' => 'Hematologi Rutin', 'type' => 'lab klinik', 'loinc_code' => '123-4']);
    }

    public function test_cannot_create_duplicate_category()
    {
        LaboratoryCategory::factory()->create(['name' => 'Serologi']);

        $response = $this->postJson('/api/v1/master-data/laboratory-categories', [
            'name' => 'Serologi',
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_category()
    {
        $category = LaboratoryCategory::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/v1/master-data/laboratory-categories/{$category->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('laboratory_categories', ['name' => 'New Name']);
    }

    public function test_can_update_status()
    {
        $category = LaboratoryCategory::factory()->create(['is_active' => true]);

        $response = $this->patchJson("/api/v1/master-data/laboratory-categories/{$category->id}/status", [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('laboratory_categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);
    }

    public function test_can_delete_category()
    {
        $category = LaboratoryCategory::factory()->create();

        $response = $this->deleteJson("/api/v1/master-data/laboratory-categories/{$category->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('laboratory_categories', ['id' => $category->id]);
    }
}
