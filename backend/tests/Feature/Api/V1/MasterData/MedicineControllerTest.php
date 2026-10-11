<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\GenericMedicine;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\MedicineCategory;
use App\Models\MasterData\MedicineClassification;
use App\Models\MasterData\MedicineRoute;
use App\Models\MasterData\MedicineUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MedicineControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_can_list_medicines()
    {
        $unit = MedicineUnit::create(['name' => 'Tablet']);
        $cat = MedicineCategory::create(['name' => 'Analgesik']);
        $class = MedicineClassification::create(['name' => 'Obat Bebas']);
        $route = MedicineRoute::create(['name' => 'Oral']);
        $gen = GenericMedicine::create(['name' => 'Paracetamol']);

        Medicine::create([
            'code' => 'MED-001',
            'name' => 'Paracetamol 500mg',
            'kfa_code' => 'KFA-12345',
            'function' => 'Pereda demam',
            'medicine_unit_id' => $unit->id,
            'medicine_category_id' => $cat->id,
            'medicine_classification_id' => $class->id,
            'medicine_route_id' => $route->id,
            'generic_medicine_id' => $gen->id,
            'description' => 'Paracetamol tablet',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/master-data/medicines');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'kfa_code',
                        'function',
                        'unit',
                        'category',
                        'classification',
                        'route',
                        'generic',
                        'description',
                        'is_active',
                    ],
                ],
            ]);
    }

    public function test_can_create_medicine()
    {
        $unit = MedicineUnit::create(['name' => 'Tablet']);

        $data = [
            'code' => 'MED-002',
            'name' => 'Amoxicillin 500mg',
            'kfa_code' => 'KFA-999',
            'function' => 'Antibiotik',
            'medicine_unit_id' => $unit->id,
            'description' => 'Antibiotik oral',
        ];

        $response = $this->postJson('/api/v1/master-data/medicines', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Amoxicillin 500mg');

        $this->assertDatabaseHas('medicines', [
            'code' => 'MED-002',
            'name' => 'Amoxicillin 500mg',
            'is_active' => true,
        ]);
    }

    public function test_can_update_medicine()
    {
        $medicine = Medicine::create([
            'code' => 'MED-003',
            'name' => 'Ibuprofen 200mg',
            'is_active' => true,
        ]);

        $data = [
            'code' => 'MED-003-REV',
            'name' => 'Ibuprofen 400mg',
            'description' => 'Dosis dinaikkan',
        ];

        $response = $this->putJson("/api/v1/master-data/medicines/{$medicine->id}", $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Ibuprofen 400mg');

        $this->assertDatabaseHas('medicines', [
            'id' => $medicine->id,
            'name' => 'Ibuprofen 400mg',
        ]);
    }

    public function test_can_delete_medicine()
    {
        $medicine = Medicine::create([
            'code' => 'MED-004',
            'name' => 'Antasida Doen',
            'is_active' => true,
        ]);

        $response = $this->deleteJson("/api/v1/master-data/medicines/{$medicine->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('medicines', ['id' => $medicine->id]);
    }
}
