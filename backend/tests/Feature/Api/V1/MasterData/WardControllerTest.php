<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\Building;
use App\Models\MasterData\Floor;
use App\Models\MasterData\Ward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_user_cannot_access_wards(): void
    {
        $this->getJson('/api/v1/master-data/wards')->assertUnauthorized();
        $this->postJson('/api/v1/master-data/wards', ['name' => 'Bangsal Melati'])->assertUnauthorized();
        $this->getJson('/api/v1/master-data/wards/1')->assertUnauthorized();
        $this->putJson('/api/v1/master-data/wards/1', ['name' => 'Bangsal Melati'])->assertUnauthorized();
        $this->deleteJson('/api/v1/master-data/wards/1')->assertUnauthorized();
    }

    public function test_can_list_wards_with_pagination_and_eager_loaded_floor_and_building(): void
    {
        $building = Building::factory()->create(['name' => 'RUANG ARJUNA']);
        $floor = Floor::factory()->create(['building_id' => $building->id, 'name' => 'Lantai 4']);
        Ward::factory()->count(15)->create(['floor_id' => $floor->id]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/wards?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'floor_id',
                        'floor' => [
                            'id',
                            'name',
                            'building' => [
                                'id',
                                'name',
                            ],
                        ],
                        'floor_display_name',
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
        $this->assertEquals('Lantai 4', $response->json('data.0.floor.name'));
        $this->assertEquals('RUANG ARJUNA', $response->json('data.0.floor.building.name'));
        $this->assertEquals('Lantai 4 - RUANG ARJUNA', $response->json('data.0.floor_display_name'));
    }

    public function test_can_search_wards_by_name(): void
    {
        $floor = Floor::factory()->create();
        Ward::factory()->create(['floor_id' => $floor->id, 'name' => 'Bangsal Melati']);
        Ward::factory()->create(['floor_id' => $floor->id, 'name' => 'Bangsal Mawar']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/wards?search=Melati');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Bangsal Melati');
    }

    public function test_can_search_wards_by_floor_name(): void
    {
        $floorA = Floor::factory()->create(['name' => 'Lantai Khusus']);
        $floorB = Floor::factory()->create(['name' => 'Lantai Biasa']);

        Ward::factory()->create(['floor_id' => $floorA->id, 'name' => 'Bangsal 1']);
        Ward::factory()->create(['floor_id' => $floorB->id, 'name' => 'Bangsal 2']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/wards?search=Khusus');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Bangsal 1');
    }

    public function test_can_search_wards_by_building_name(): void
    {
        $buildingA = Building::factory()->create(['name' => 'Paviliun Anggrek']);
        $buildingB = Building::factory()->create(['name' => 'Paviliun Cempaka']);

        $floorA = Floor::factory()->create(['building_id' => $buildingA->id]);
        $floorB = Floor::factory()->create(['building_id' => $buildingB->id]);

        Ward::factory()->create(['floor_id' => $floorA->id, 'name' => 'Bangsal Anggrek 1']);
        Ward::factory()->create(['floor_id' => $floorB->id, 'name' => 'Bangsal Cempaka 1']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/wards?search=Anggrek');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Bangsal Anggrek 1');
    }

    public function test_can_filter_wards_by_floor_id(): void
    {
        $floorA = Floor::factory()->create();
        $floorB = Floor::factory()->create();

        Ward::factory()->count(3)->create(['floor_id' => $floorA->id]);
        Ward::factory()->count(2)->create(['floor_id' => $floorB->id]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/master-data/wards?floor_id={$floorA->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_show_ward(): void
    {
        $building = Building::factory()->create(['name' => 'Gedung Utama']);
        $floor = Floor::factory()->create(['building_id' => $building->id, 'name' => 'Lantai 1']);
        $ward = Ward::factory()->create([
            'floor_id' => $floor->id,
            'name' => 'Bangsal Arjuna A',
            'description' => 'Bangsal perawatan pasien',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/master-data/wards/{$ward->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $ward->id)
            ->assertJsonPath('data.floor_id', $floor->id)
            ->assertJsonPath('data.floor.name', 'Lantai 1')
            ->assertJsonPath('data.floor.building.name', 'Gedung Utama')
            ->assertJsonPath('data.floor_display_name', 'Lantai 1 - Gedung Utama')
            ->assertJsonPath('data.name', 'Bangsal Arjuna A')
            ->assertJsonPath('data.description', 'Bangsal perawatan pasien');
    }

    public function test_can_create_ward(): void
    {
        $building = Building::factory()->create(['name' => 'RUANG ARJUNA']);
        $floor = Floor::factory()->create(['building_id' => $building->id, 'name' => 'Lantai 4']);

        $payload = [
            'floor_id' => $floor->id,
            'name' => 'Bangsal Arjuna A',
            'description' => 'Bangsal perawatan pasien',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/wards', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.floor_id', $floor->id)
            ->assertJsonPath('data.floor.name', 'Lantai 4')
            ->assertJsonPath('data.floor.building.name', 'RUANG ARJUNA')
            ->assertJsonPath('data.floor_display_name', 'Lantai 4 - RUANG ARJUNA')
            ->assertJsonPath('data.name', 'Bangsal Arjuna A')
            ->assertJsonPath('data.description', 'Bangsal perawatan pasien');

        $this->assertDatabaseHas('wards', [
            'floor_id' => $floor->id,
            'name' => 'Bangsal Arjuna A',
            'description' => 'Bangsal perawatan pasien',
        ]);
    }

    public function test_create_ward_trims_excessive_whitespace(): void
    {
        $floor = Floor::factory()->create();

        $payload = [
            'floor_id' => $floor->id,
            'name' => '   Bangsal    Dahlia   1   ',
            'description' => '   Perawatan khusus   ',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/wards', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Bangsal Dahlia 1')
            ->assertJsonPath('data.description', 'Perawatan khusus');

        $this->assertDatabaseHas('wards', [
            'floor_id' => $floor->id,
            'name' => 'Bangsal Dahlia 1',
            'description' => 'Perawatan khusus',
        ]);
    }

    public function test_validation_floor_id_is_required_to_create_ward(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/wards', [
            'name' => 'Bangsal Melati',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['floor_id']);
    }

    public function test_validation_floor_id_must_exist_and_not_be_soft_deleted(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/wards', [
            'floor_id' => 999999,
            'name' => 'Bangsal Melati',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['floor_id']);

        $softDeletedFloor = Floor::factory()->create();
        $softDeletedFloor->delete();

        $responseDeleted = $this->actingAs($this->user)->postJson('/api/v1/master-data/wards', [
            'floor_id' => $softDeletedFloor->id,
            'name' => 'Bangsal Melati',
        ]);

        $responseDeleted->assertStatus(422)
            ->assertJsonValidationErrors(['floor_id']);
    }

    public function test_validation_floor_must_have_valid_building(): void
    {
        $building = Building::factory()->create();
        $floor = Floor::factory()->create(['building_id' => $building->id]);
        $building->delete(); // Soft delete building

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/wards', [
            'floor_id' => $floor->id,
            'name' => 'Bangsal Melati',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['floor_id'])
            ->assertJsonPath('errors.floor_id.0', 'Lantai yang dipilih harus memiliki relasi Gedung yang valid.');
    }

    public function test_validation_name_is_required_to_create_ward(): void
    {
        $floor = Floor::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/wards', [
            'floor_id' => $floor->id,
            'name' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_validation_prevents_duplicate_ward_name_in_same_floor(): void
    {
        $floor = Floor::factory()->create();
        Ward::factory()->create([
            'floor_id' => $floor->id,
            'name' => 'Bangsal Mawar',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/wards', [
            'floor_id' => $floor->id,
            'name' => 'Bangsal Mawar',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJsonPath('errors.name.0', 'Nama bangsal sudah digunakan pada lantai yang dipilih.');
    }

    public function test_can_create_same_ward_name_in_different_floor(): void
    {
        $floorA = Floor::factory()->create();
        $floorB = Floor::factory()->create();

        Ward::factory()->create([
            'floor_id' => $floorA->id,
            'name' => 'Bangsal Mawar',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/wards', [
            'floor_id' => $floorB->id,
            'name' => 'Bangsal Mawar',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.floor_id', $floorB->id)
            ->assertJsonPath('data.name', 'Bangsal Mawar');

        $this->assertDatabaseHas('wards', [
            'floor_id' => $floorB->id,
            'name' => 'Bangsal Mawar',
        ]);
    }

    public function test_can_create_ward_with_same_name_as_soft_deleted_ward_in_same_floor(): void
    {
        $floor = Floor::factory()->create();
        $deletedWard = Ward::factory()->create([
            'floor_id' => $floor->id,
            'name' => 'Bangsal Lama',
        ]);
        $deletedWard->delete();

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/wards', [
            'floor_id' => $floor->id,
            'name' => 'Bangsal Lama',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Bangsal Lama');
    }

    public function test_can_update_ward(): void
    {
        $floorA = Floor::factory()->create();
        $floorB = Floor::factory()->create();
        $ward = Ward::factory()->create([
            'floor_id' => $floorA->id,
            'name' => 'Bangsal Mawar Awal',
            'description' => 'Awal',
        ]);

        $payload = [
            'floor_id' => $floorB->id,
            'name' => 'Bangsal Mawar Baru',
            'description' => 'Deskripsi Baru',
        ];

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/wards/{$ward->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('data.id', $ward->id)
            ->assertJsonPath('data.floor_id', $floorB->id)
            ->assertJsonPath('data.name', 'Bangsal Mawar Baru')
            ->assertJsonPath('data.description', 'Deskripsi Baru');

        $this->assertDatabaseHas('wards', [
            'id' => $ward->id,
            'floor_id' => $floorB->id,
            'name' => 'Bangsal Mawar Baru',
            'description' => 'Deskripsi Baru',
        ]);
    }

    public function test_can_update_ward_keeping_same_name(): void
    {
        $floor = Floor::factory()->create();
        $ward = Ward::factory()->create([
            'floor_id' => $floor->id,
            'name' => 'Bangsal Aster',
            'description' => 'Awal',
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/wards/{$ward->id}", [
            'floor_id' => $floor->id,
            'name' => 'Bangsal Aster',
            'description' => 'Deskripsi Diperbarui',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Bangsal Aster')
            ->assertJsonPath('data.description', 'Deskripsi Diperbarui');
    }

    public function test_validation_prevents_duplicate_ward_name_on_update_in_same_floor(): void
    {
        $floor = Floor::factory()->create();
        Ward::factory()->create(['floor_id' => $floor->id, 'name' => 'Bangsal Mawar']);
        $wardB = Ward::factory()->create(['floor_id' => $floor->id, 'name' => 'Bangsal Melati']);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/wards/{$wardB->id}", [
            'floor_id' => $floor->id,
            'name' => 'Bangsal Mawar',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_delete_ward_using_soft_delete(): void
    {
        $ward = Ward::factory()->create(['name' => 'Bangsal Hapus']);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/wards/{$ward->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('wards', [
            'id' => $ward->id,
            'name' => 'Bangsal Hapus',
        ]);
    }

    public function test_floor_deletion_is_prevented_if_has_wards(): void
    {
        $floor = Floor::factory()->create(['name' => 'Lantai Ada Bangsal']);
        Ward::factory()->create(['floor_id' => $floor->id, 'name' => 'Bangsal 1']);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/floors/{$floor->id}");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Lantai tidak dapat dihapus karena masih digunakan oleh data bangsal.');

        $this->assertNotSoftDeleted('floors', [
            'id' => $floor->id,
        ]);
    }
}
