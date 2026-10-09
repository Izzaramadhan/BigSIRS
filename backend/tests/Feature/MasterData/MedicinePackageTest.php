<?php

namespace Tests\Feature\MasterData;

use App\Models\MasterData\MedicinePackage;
use App\Models\MasterData\MedicinePackageItem;
use App\Models\MasterData\Medicine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicinePackageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        // Ensure no foreign key errors for medicines (assuming it just needs some basic fields)
        $this->medicine = Medicine::create([
            'name' => 'Test Medicine',
            'is_active' => true,
        ]);
    }
    
    public function test_guest_cannot_access_medicine_packages()
    {
        $response = $this->getJson('/api/v1/master-data/medicine-packages');
        $response->assertStatus(401);
    }

    public function test_can_list_medicine_packages()
    {
        MedicinePackage::create(['name' => 'P1', 'price' => 10, 'quantity' => 1, 'is_active' => true]);
        MedicinePackage::create(['name' => 'P2', 'price' => 10, 'quantity' => 1, 'is_active' => true]);
        MedicinePackage::create(['name' => 'P3', 'price' => 10, 'quantity' => 1, 'is_active' => true]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/medicine-packages');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_medicine_package()
    {
        $payload = [
            'name' => 'Paket Cek',
            'price' => 15000,
            'quantity' => 10,
            'description' => 'Desc',
            'is_active' => true,
            'items' => [
                [
                    'medicine_id' => $this->medicine->id,
                    'quantity' => 5,
                    'unit_price' => 3000,
                    'subtotal' => 15000,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/medicine-packages', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Paket Cek');

        $this->assertDatabaseHas('medicine_packages', [
            'name' => 'Paket Cek',
            'price' => 15000,
        ]);

        $this->assertDatabaseHas('medicine_package_items', [
            'medicine_id' => $this->medicine->id,
            'quantity' => 5,
        ]);
    }

    public function test_cannot_create_without_items()
    {
        $payload = [
            'name' => 'Paket Cek',
            'price' => 15000,
            'quantity' => 10,
            'description' => 'Desc',
            'is_active' => true,
            'items' => []
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/medicine-packages', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('items');
    }

    public function test_can_update_medicine_package()
    {
        $package = MedicinePackage::create([
            'name' => 'P1',
            'price' => 10,
            'quantity' => 1,
            'is_active' => true
        ]);
        
        $package->items()->create([
            'medicine_id' => $this->medicine->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
        ]);

        $newMedicine = Medicine::create(['name' => 'New Med', 'is_active' => true]);

        $payload = [
            'name' => 'Paket Updated',
            'price' => 20000,
            'quantity' => 5,
            'is_active' => true,
            'items' => [
                [
                    'medicine_id' => $newMedicine->id,
                    'quantity' => 2,
                    'unit_price' => 10000,
                    'subtotal' => 20000,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/master-data/medicine-packages/{$package->id}", $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('medicine_packages', [
            'id' => $package->id,
            'name' => 'Paket Updated',
        ]);

        $this->assertDatabaseMissing('medicine_package_items', [
            'medicine_id' => $this->medicine->id,
        ]);

        $this->assertDatabaseHas('medicine_package_items', [
            'medicine_package_id' => $package->id,
            'medicine_id' => $newMedicine->id,
            'quantity' => 2,
        ]);
    }

    public function test_can_delete_medicine_package()
    {
        $package = MedicinePackage::create([
            'name' => 'P1',
            'price' => 10,
            'quantity' => 1,
            'is_active' => true
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/master-data/medicine-packages/{$package->id}");

        $response->assertStatus(204);
        
        $this->assertSoftDeleted('medicine_packages', [
            'id' => $package->id,
        ]);
    }
}
