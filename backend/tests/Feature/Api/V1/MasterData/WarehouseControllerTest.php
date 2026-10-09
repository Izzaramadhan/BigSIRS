<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\Warehouse;
use App\Models\Polyclinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WarehouseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock authorization since policies return true by default anyway,
        // but just to be sure we don't hit unexpected auth gate issues.
        Gate::before(function ($user, $ability) {
            return true;
        });
    }

    public function test_can_get_all_warehouses()
    {
        Warehouse::factory()->count(3)->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/master-data/warehouses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'legacy_id', 'code', 'name', 'description', 'created_at', 'updated_at'],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_can_search_warehouses()
    {
        Warehouse::factory()->create(['name' => 'Gudang Utama']);
        Warehouse::factory()->create(['name' => 'Apotek Rajal']);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/master-data/warehouses?search=Utama');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Gudang Utama');
    }

    public function test_can_create_warehouse()
    {
        Sanctum::actingAs(User::factory()->create());

        $data = [
            'name' => 'Gudang Baru',
            'code' => 'GB',
            'description' => 'Test',
        ];

        $response = $this->postJson('/api/v1/master-data/warehouses', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Gudang Baru')
            ->assertJsonPath('data.code', 'GB');

        $this->assertDatabaseHas('warehouses', ['name' => 'Gudang Baru']);
    }

    public function test_cannot_create_warehouse_without_name()
    {
        Sanctum::actingAs(User::factory()->create());

        $data = [
            'code' => 'GB',
        ];

        $response = $this->postJson('/api/v1/master-data/warehouses', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_warehouse()
    {
        $warehouse = Warehouse::factory()->create(['name' => 'Gudang Lama']);
        Sanctum::actingAs(User::factory()->create());

        $data = [
            'name' => 'Gudang Diupdate',
            'code' => 'GD',
        ];

        $response = $this->putJson('/api/v1/master-data/warehouses/'.$warehouse->id, $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Gudang Diupdate');

        $this->assertDatabaseHas('warehouses', ['name' => 'Gudang Diupdate']);
    }

    public function test_can_delete_warehouse()
    {
        $warehouse = Warehouse::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->deleteJson('/api/v1/master-data/warehouses/'.$warehouse->id);

        $response->assertStatus(204);

        $this->assertSoftDeleted('warehouses', ['id' => $warehouse->id]);
    }

    public function test_cannot_delete_warehouse_if_used_in_polyclinic()
    {
        $warehouse = Warehouse::factory()->create(['legacy_id' => 999]);

        Polyclinic::factory()->create([
            'legacy_default_warehouse_id' => 999,
        ]);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->deleteJson('/api/v1/master-data/warehouses/'.$warehouse->id);

        $response->assertStatus(403);
        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id, 'deleted_at' => null]);
    }
}
