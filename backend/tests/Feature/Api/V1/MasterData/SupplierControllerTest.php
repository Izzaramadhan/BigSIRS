<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class SupplierControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_can_get_suppliers_list()
    {
        Supplier::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/master-data/suppliers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'phone', 'address', 'is_active']
                ],
                'meta',
                'links'
            ]);
    }

    public function test_can_search_suppliers_by_name()
    {
        Supplier::factory()->create(['name' => 'KIMIA FARMA']);
        Supplier::factory()->create(['name' => 'INDO FARMA']);

        $response = $this->getJson('/api/v1/master-data/suppliers?search=KIMIA');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'KIMIA FARMA');
    }

    public function test_can_create_supplier()
    {
        $payload = [
            'name' => 'PT BARU JAYA',
            'phone' => '08123456789',
            'address' => 'Jl Baru No 1',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/master-data/suppliers', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'PT BARU JAYA');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'PT BARU JAYA',
            'phone' => '08123456789'
        ]);
    }

    public function test_can_update_supplier()
    {
        $supplier = Supplier::factory()->create(['name' => 'Old Name']);

        $payload = [
            'name' => 'New Name',
            'phone' => '123',
            'address' => 'Updated',
            'is_active' => false,
        ];

        $response = $this->putJson("/api/v1/master-data/suppliers/{$supplier->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'New Name',
            'is_active' => 0
        ]);
    }

    public function test_can_delete_supplier()
    {
        $supplier = Supplier::factory()->create();

        $response = $this->deleteJson("/api/v1/master-data/suppliers/{$supplier->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('suppliers', [
            'id' => $supplier->id
        ]);
    }
}
