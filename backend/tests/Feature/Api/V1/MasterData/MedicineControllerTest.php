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

    public function test_can_create_and_return_medicine_with_selling_price_per_unit()
    {
        $data = [
            'name' => 'Amoxan Drop',
            'selling_price_per_unit' => 2251.7,
        ];

        $response = $this->postJson('/api/v1/master-data/medicines', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Amoxan Drop')
            ->assertJsonPath('data.selling_price_per_unit', 2251.7)
            ->assertJsonPath('data.unit_price', 2251.7)
            ->assertJsonPath('data.price', 2251.7);

        $this->assertDatabaseHas('medicines', [
            'name' => 'Amoxan Drop',
            'selling_price_per_unit' => 2251.7,
        ]);
    }

    public function test_can_create_medicine_with_valid_generic_medicine_id()
    {
        $generic = GenericMedicine::create(['name' => 'Paracetamol', 'is_active' => true]);

        $data = [
            'name' => 'Paracetamol 500mg',
            'generic_medicine_id' => $generic->id,
        ];

        $response = $this->postJson('/api/v1/master-data/medicines', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.generic_medicine_id', $generic->id)
            ->assertJsonPath('data.generic.name', 'Paracetamol');

        $this->assertDatabaseHas('medicines', [
            'name' => 'Paracetamol 500mg',
            'generic_medicine_id' => $generic->id,
        ]);
    }

    public function test_rejects_create_medicine_with_invalid_generic_medicine_id()
    {
        $data = [
            'name' => 'Obat Abal',
            'generic_medicine_id' => 999999,
        ];

        $response = $this->postJson('/api/v1/master-data/medicines', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['generic_medicine_id']);
    }

    public function test_show_medicine_includes_generic_relation()
    {
        $generic = GenericMedicine::create(['name' => 'Amoxicillin Trihydrate', 'is_active' => true]);
        $medicine = Medicine::create([
            'name' => 'Amoxicillin 500mg',
            'generic_medicine_id' => $generic->id,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/master-data/medicines/{$medicine->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.generic_medicine_id', $generic->id)
            ->assertJsonPath('data.generic.id', $generic->id)
            ->assertJsonPath('data.generic.name', 'Amoxicillin Trihydrate');
    }

    public function test_can_update_medicine_generic_medicine_id()
    {
        $gen1 = GenericMedicine::create(['name' => 'Generik Lama', 'is_active' => true]);
        $gen2 = GenericMedicine::create(['name' => 'Generik Baru', 'is_active' => true]);

        $medicine = Medicine::create([
            'name' => 'Obat Uji',
            'generic_medicine_id' => $gen1->id,
            'is_active' => true,
        ]);

        $response = $this->putJson("/api/v1/master-data/medicines/{$medicine->id}", [
            'name' => 'Obat Uji',
            'generic_medicine_id' => $gen2->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.generic_medicine_id', $gen2->id)
            ->assertJsonPath('data.generic.name', 'Generik Baru');

        $this->assertDatabaseHas('medicines', [
            'id' => $medicine->id,
            'generic_medicine_id' => $gen2->id,
        ]);
    }
}
