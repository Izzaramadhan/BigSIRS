<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\Medicine;
use App\Models\MasterData\MedicineCategory;
use App\Models\MasterData\MedicineRoute;
use App\Models\MasterData\MedicineUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MedicineRouteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(function ($user, $ability) {
            return true;
        });
    }

    public function test_can_get_all_medicine_routes()
    {
        MedicineRoute::create(['name' => 'Oral', 'object_code' => 'MDCTx001']);
        MedicineRoute::create(['name' => 'Intravena', 'object_code' => 'MDCTx002']);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/master-data/medicine-routes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'legacy_id', 'name', 'object_code', 'is_active', 'created_at', 'updated_at'],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_can_search_medicine_routes()
    {
        MedicineRoute::create(['name' => 'Oral', 'object_code' => 'MDCTx001']);
        MedicineRoute::create(['name' => 'Subkutan', 'object_code' => 'MDCTx003']);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/master-data/medicine-routes?search=Subkutan');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Subkutan');
    }

    public function test_can_sort_medicine_routes()
    {
        MedicineRoute::create(['name' => 'Oral', 'object_code' => 'MDCTx001']);
        MedicineRoute::create(['name' => 'Intravena', 'object_code' => 'MDCTx002']);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/master-data/medicine-routes?sort=name&direction=desc');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Oral')
            ->assertJsonPath('data.1.name', 'Intravena');
    }

    public function test_can_create_medicine_route()
    {
        Sanctum::actingAs(User::factory()->create());

        $data = [
            'name' => 'Topikal',
            'object_code' => 'MDCTx010',
        ];

        $response = $this->postJson('/api/v1/master-data/medicine-routes', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Topikal')
            ->assertJsonPath('data.object_code', 'MDCTx010');

        $this->assertDatabaseHas('medicine_routes', ['name' => 'Topikal', 'object_code' => 'MDCTx010']);
    }

    public function test_cannot_create_medicine_route_without_name()
    {
        Sanctum::actingAs(User::factory()->create());

        $data = [
            'object_code' => 'MDCTx010',
        ];

        $response = $this->postJson('/api/v1/master-data/medicine-routes', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_medicine_route()
    {
        $route = MedicineRoute::create(['name' => 'Inhalasi', 'object_code' => 'MDCTx020']);
        Sanctum::actingAs(User::factory()->create());

        $data = [
            'name' => 'Inhalasi Revisi',
            'object_code' => 'MDCTx021',
        ];

        $response = $this->putJson("/api/v1/master-data/medicine-routes/{$route->id}", $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Inhalasi Revisi')
            ->assertJsonPath('data.object_code', 'MDCTx021');

        $this->assertDatabaseHas('medicine_routes', ['id' => $route->id, 'name' => 'Inhalasi Revisi']);
    }

    public function test_can_delete_medicine_route()
    {
        $route = MedicineRoute::create(['name' => 'Rektal']);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->deleteJson("/api/v1/master-data/medicine-routes/{$route->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('medicine_routes', ['id' => $route->id]);
    }

    public function test_cannot_delete_medicine_route_when_used_by_medicines()
    {
        $route = MedicineRoute::create(['name' => 'Oral']);
        $category = MedicineCategory::create(['name' => 'Analgesik']);
        $unit = MedicineUnit::create(['name' => 'Tablet']);

        Medicine::create([
            'name' => 'Paracetamol 500mg',
            'medicine_category_id' => $category->id,
            'medicine_unit_id' => $unit->id,
            'medicine_route_id' => $route->id,
        ]);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->deleteJson("/api/v1/master-data/medicine-routes/{$route->id}");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Jalur masuk obat tidak dapat dihapus karena sudah digunakan pada data obat.');

        $this->assertDatabaseHas('medicine_routes', ['id' => $route->id, 'deleted_at' => null]);
    }
}
