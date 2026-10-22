<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\Building;
use App\Models\MasterData\Floor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FloorControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_user_cannot_access_floors(): void
    {
        $this->getJson('/api/v1/master-data/floors')->assertUnauthorized();
        $this->postJson('/api/v1/master-data/floors', ['name' => 'Lantai 1'])->assertUnauthorized();
        $this->getJson('/api/v1/master-data/floors/1')->assertUnauthorized();
        $this->putJson('/api/v1/master-data/floors/1', ['name' => 'Lantai 1'])->assertUnauthorized();
        $this->deleteJson('/api/v1/master-data/floors/1')->assertUnauthorized();
    }

    public function test_can_list_floors_with_pagination_and_eager_loaded_building(): void
    {
        $building = Building::factory()->create(['name' => 'Gedung Teratai']);
        Floor::factory()->count(15)->create(['building_id' => $building->id]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/floors?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'building_id',
                        'building' => ['id', 'name'],
                        'name',
                        'description',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links',
            ]);

        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(15, $response->json('meta.total'));
        $this->assertEquals('Gedung Teratai', $response->json('data.0.building.name'));
    }

    public function test_can_search_floors_by_name(): void
    {
        $building = Building::factory()->create();
        Floor::factory()->create(['building_id' => $building->id, 'name' => 'Lantai Operasi']);
        Floor::factory()->create(['building_id' => $building->id, 'name' => 'Lantai Rawat']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/floors?search=Operasi');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Lantai Operasi');
    }

    public function test_can_search_floors_by_building_name(): void
    {
        $buildingA = Building::factory()->create(['name' => 'Gedung Dahlia']);
        $buildingB = Building::factory()->create(['name' => 'Gedung Melati']);

        Floor::factory()->create(['building_id' => $buildingA->id, 'name' => 'Lantai 1']);
        Floor::factory()->create(['building_id' => $buildingB->id, 'name' => 'Lantai 2']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/floors?search=Dahlia');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.building.name', 'Gedung Dahlia');
    }

    public function test_can_filter_floors_by_building_id(): void
    {
        $buildingA = Building::factory()->create();
        $buildingB = Building::factory()->create();

        Floor::factory()->count(3)->create(['building_id' => $buildingA->id]);
        Floor::factory()->count(2)->create(['building_id' => $buildingB->id]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/master-data/floors?building_id={$buildingA->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_show_floor(): void
    {
        $building = Building::factory()->create(['name' => 'Gedung Serbaguna']);
        $floor = Floor::factory()->create([
            'building_id' => $building->id,
            'name' => 'Lantai 3',
            'description' => 'Ruang Pertemuan',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/master-data/floors/{$floor->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $floor->id)
            ->assertJsonPath('data.building_id', $building->id)
            ->assertJsonPath('data.building.name', 'Gedung Serbaguna')
            ->assertJsonPath('data.name', 'Lantai 3')
            ->assertJsonPath('data.description', 'Ruang Pertemuan');
    }

    public function test_can_create_floor(): void
    {
        $building = Building::factory()->create(['name' => 'Gedung Utama']);

        $payload = [
            'building_id' => $building->id,
            'name' => 'Lantai 1',
            'description' => 'Lantai Poliklinik',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/floors', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.building_id', $building->id)
            ->assertJsonPath('data.building.name', 'Gedung Utama')
            ->assertJsonPath('data.name', 'Lantai 1')
            ->assertJsonPath('data.description', 'Lantai Poliklinik');

        $this->assertDatabaseHas('floors', [
            'building_id' => $building->id,
            'name' => 'Lantai 1',
            'description' => 'Lantai Poliklinik',
        ]);
    }

    public function test_create_floor_trims_excessive_whitespace(): void
    {
        $building = Building::factory()->create();

        $payload = [
            'building_id' => $building->id,
            'name' => '   Lantai    Basement   1  ',
            'description' => '   Area Parkir dan Genset   ',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/floors', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Lantai Basement 1')
            ->assertJsonPath('data.description', 'Area Parkir dan Genset');
    }

    public function test_validation_building_id_is_required_to_create_floor(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/floors', [
            'name' => 'Lantai 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['building_id']);
    }

    public function test_validation_building_id_must_exist_and_not_be_soft_deleted(): void
    {
        $deletedBuilding = Building::factory()->create();
        $deletedBuilding->delete();

        // Test non-existent building ID
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/floors', [
            'building_id' => 999999,
            'name' => 'Lantai 1',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['building_id']);

        // Test soft-deleted building ID
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/floors', [
            'building_id' => $deletedBuilding->id,
            'name' => 'Lantai 1',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['building_id']);
    }

    public function test_validation_name_is_required_to_create_floor(): void
    {
        $building = Building::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/floors', [
            'building_id' => $building->id,
            'name' => '   ',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_validation_prevents_duplicate_floor_name_in_same_building(): void
    {
        $building = Building::factory()->create();
        Floor::factory()->create([
            'building_id' => $building->id,
            'name' => 'Lantai 2',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/floors', [
            'building_id' => $building->id,
            'name' => 'Lantai 2',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_create_same_floor_name_in_different_building(): void
    {
        $buildingA = Building::factory()->create(['name' => 'Gedung A']);
        $buildingB = Building::factory()->create(['name' => 'Gedung B']);

        Floor::factory()->create([
            'building_id' => $buildingA->id,
            'name' => 'Lantai 1',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/floors', [
            'building_id' => $buildingB->id,
            'name' => 'Lantai 1',
            'description' => 'Lantai 1 pada gedung B',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.building_id', $buildingB->id)
            ->assertJsonPath('data.name', 'Lantai 1');

        $this->assertEquals(2, Floor::where('name', 'Lantai 1')->count());
    }

    public function test_can_create_floor_with_same_name_as_soft_deleted_floor_in_same_building(): void
    {
        $building = Building::factory()->create();
        $deletedFloor = Floor::factory()->create([
            'building_id' => $building->id,
            'name' => 'Lantai 3',
        ]);
        $deletedFloor->delete();

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/floors', [
            'building_id' => $building->id,
            'name' => 'Lantai 3',
            'description' => 'Lantai 3 dibuat ulang',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Lantai 3');
    }

    public function test_can_update_floor(): void
    {
        $buildingA = Building::factory()->create();
        $buildingB = Building::factory()->create();
        $floor = Floor::factory()->create([
            'building_id' => $buildingA->id,
            'name' => 'Lantai 1 Lama',
            'description' => 'Deskripsi Lama',
        ]);

        $payload = [
            'building_id' => $buildingB->id,
            'name' => 'Lantai 1 Baru',
            'description' => 'Deskripsi Baru',
        ];

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/floors/{$floor->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('data.id', $floor->id)
            ->assertJsonPath('data.building_id', $buildingB->id)
            ->assertJsonPath('data.name', 'Lantai 1 Baru')
            ->assertJsonPath('data.description', 'Deskripsi Baru');

        $this->assertDatabaseHas('floors', [
            'id' => $floor->id,
            'building_id' => $buildingB->id,
            'name' => 'Lantai 1 Baru',
            'description' => 'Deskripsi Baru',
        ]);
    }

    public function test_can_update_floor_keeping_same_name(): void
    {
        $building = Building::factory()->create();
        $floor = Floor::factory()->create([
            'building_id' => $building->id,
            'name' => 'Lantai 5',
            'description' => 'Awal',
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/floors/{$floor->id}", [
            'building_id' => $building->id,
            'name' => 'Lantai 5',
            'description' => 'Deskripsi Diperbarui',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Lantai 5')
            ->assertJsonPath('data.description', 'Deskripsi Diperbarui');
    }

    public function test_validation_prevents_duplicate_floor_name_on_update_in_same_building(): void
    {
        $building = Building::factory()->create();
        Floor::factory()->create(['building_id' => $building->id, 'name' => 'Lantai 1']);
        $floorB = Floor::factory()->create(['building_id' => $building->id, 'name' => 'Lantai 2']);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/floors/{$floorB->id}", [
            'building_id' => $building->id,
            'name' => 'Lantai 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_delete_floor_using_soft_delete(): void
    {
        $floor = Floor::factory()->create(['name' => 'Lantai Hapus']);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/floors/{$floor->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('floors', [
            'id' => $floor->id,
            'name' => 'Lantai Hapus',
        ]);
    }

    public function test_building_deletion_is_prevented_if_has_floors(): void
    {
        $building = Building::factory()->create(['name' => 'Gedung Ada Lantai']);
        Floor::factory()->create(['building_id' => $building->id, 'name' => 'Lantai 1']);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/buildings/{$building->id}");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Gedung tidak dapat dihapus karena masih digunakan oleh data lantai.');

        $this->assertNotSoftDeleted('buildings', [
            'id' => $building->id,
        ]);
    }
}
