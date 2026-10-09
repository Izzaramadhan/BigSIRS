<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\MedicineUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MedicineUnitControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_can_list_categories()
    {
        MedicineUnit::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/master-data/medicine-units');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'is_active']
                ]
            ]);
    }

    public function test_can_create_category()
    {
        $data = [
            'name' => 'New Category',
            'description' => 'Test Desc'
        ];

        $response = $this->postJson('/api/v1/master-data/medicine-units', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Category');
            
        $this->assertDatabaseHas('medicine_units', $data);
    }

    public function test_can_update_category()
    {
        $category = MedicineUnit::factory()->create();

        $data = [
            'name' => 'Updated Category',
            'description' => 'Updated Desc'
        ];

        $response = $this->putJson("/api/v1/master-data/medicine-units/{$category->id}", $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Category');
            
        $this->assertDatabaseHas('medicine_units', $data);
    }

    public function test_can_delete_category()
    {
        $category = MedicineUnit::factory()->create();

        $response = $this->deleteJson("/api/v1/master-data/medicine-units/{$category->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('medicine_units', ['id' => $category->id]);
    }
}
