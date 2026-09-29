<?php

namespace Tests\Feature\Api\V1\MasterData;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\MasterData\ProcedurePackage;
use App\Models\MasterData\MedicalProcedure;
use App\Models\ProcedureCategory;

class ProcedurePackageTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $procedure;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $cat = ProcedureCategory::factory()->create();
        $this->procedure = MedicalProcedure::create([
            'code' => 'TEST01',
            'name' => 'Test Procedure',
            'procedure_category_id' => $cat->id,
            'is_visible' => true,
        ]);
    }

    public function test_can_list_packages()
    {
        ProcedurePackage::create([
            'name' => 'Paket A',
            'total_amount' => 10000,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/procedure-packages');

        $response->assertStatus(200)
                 ->assertJsonPath('data.0.name', 'Paket A');
    }

    public function test_can_create_package_and_calculates_total()
    {
        $payload = [
            'name' => 'New Package',
            'is_active' => true,
            'items' => [
                [
                    'medical_procedure_id' => $this->procedure->id,
                    'quantity' => 2,
                    'unit_amount' => 15000,
                    'sort_order' => 1
                ]
            ]
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/procedure-packages', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('data.total_amount', "30000.00"); // 2 * 15000
                 
        $this->assertDatabaseHas('procedure_packages', ['name' => 'New Package', 'total_amount' => 30000]);
        $this->assertDatabaseHas('procedure_package_items', ['unit_amount' => 15000, 'subtotal_amount' => 30000]);
    }

    public function test_validates_empty_items()
    {
        $payload = [
            'name' => 'Empty Package',
            'items' => []
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/procedure-packages', $payload);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['items']);
    }

    public function test_invalid_procedure_returns_422()
    {
        $payload = [
            'name' => 'Bad Package',
            'items' => [
                [
                    'medical_procedure_id' => 9999, // Does not exist
                    'quantity' => 1,
                    'unit_amount' => 1000,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/procedure-packages', $payload);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['items.0.medical_procedure_id']);
    }

    public function test_delete_without_auth_fails()
    {
        $response = $this->deleteJson('/api/v1/master-data/procedure-packages/1');
        $response->assertStatus(401);
    }

    public function test_can_soft_delete_package()
    {
        $package = ProcedurePackage::create([
            'name' => 'Paket Hapus',
            'total_amount' => 10000,
            'is_active' => true
        ]);
        
        $item = $package->items()->create([
            'medical_procedure_id' => $this->procedure->id,
            'quantity' => 1,
            'unit_amount' => 10000,
            'subtotal_amount' => 10000,
            'sort_order' => 1
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/procedure-packages/{$package->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);

        // Verify package is soft-deleted
        $this->assertSoftDeleted('procedure_packages', ['id' => $package->id]);

        // Verify procedure is NOT deleted
        $this->assertDatabaseHas('medical_procedures', ['id' => $this->procedure->id, 'deleted_at' => null]);
        
        // Verify package items remain intact in DB
        $this->assertDatabaseHas('procedure_package_items', ['id' => $item->id]);

        // Verify package doesn't show in list anymore
        $listResponse = $this->actingAs($this->user)->getJson('/api/v1/master-data/procedure-packages');
        $listResponse->assertJsonMissing(['name' => 'Paket Hapus']);
    }

    public function test_delete_invalid_id_returns_404()
    {
        $response = $this->actingAs($this->user)->deleteJson('/api/v1/master-data/procedure-packages/9999');
        $response->assertStatus(404);
    }

    public function test_can_update_package_and_remove_an_item()
    {
        // 1. Create a package with two items
        $package = ProcedurePackage::create([
            'name' => 'Package Update Test',
            'total_amount' => 30000,
            'is_active' => true
        ]);
        
        $item1 = $package->items()->create([
            'medical_procedure_id' => $this->procedure->id,
            'quantity' => 1,
            'unit_amount' => 10000,
            'subtotal_amount' => 10000,
            'sort_order' => 1
        ]);

        $procedure2 = MedicalProcedure::create([
            'code' => 'TEST02',
            'name' => 'Test Procedure 2',
            'procedure_category_id' => ProcedureCategory::first()->id,
            'is_visible' => true,
        ]);

        $item2 = $package->items()->create([
            'medical_procedure_id' => $procedure2->id,
            'quantity' => 2,
            'unit_amount' => 10000,
            'subtotal_amount' => 20000,
            'sort_order' => 2
        ]);

        // 2. Update package by sending only item1, which should remove item2
        $payload = [
            'name' => 'Package Update Test',
            'is_active' => true,
            'items' => [
                [
                    'id' => $item1->id,
                    'medical_procedure_id' => $this->procedure->id,
                    'quantity' => 2, // updating quantity
                    'unit_amount' => 10000,
                    'sort_order' => 1
                ]
                // item2 is intentionally omitted
            ]
        ];

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/procedure-packages/{$package->id}", $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('data.total_amount', "20000.00"); // 2 * 10000

        // Database checks
        $this->assertDatabaseHas('procedure_package_items', [
            'id' => $item1->id,
            'quantity' => 2,
            'subtotal_amount' => 20000
        ]);

        $this->assertDatabaseMissing('procedure_package_items', [
            'id' => $item2->id
        ]);
    }
}
