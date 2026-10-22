<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\Building;
use App\Models\MasterData\Floor;
use App\Models\MasterData\Room;
use App\Models\MasterData\Ward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_user_cannot_access_rooms(): void
    {
        $this->getJson('/api/v1/master-data/rooms')->assertUnauthorized();
        $this->postJson('/api/v1/master-data/rooms', ['name' => 'Kamar 1', 'code' => 'KMR-01'])->assertUnauthorized();
        $this->getJson('/api/v1/master-data/rooms/1')->assertUnauthorized();
        $this->putJson('/api/v1/master-data/rooms/1', ['name' => 'Kamar 1', 'code' => 'KMR-01'])->assertUnauthorized();
        $this->deleteJson('/api/v1/master-data/rooms/1')->assertUnauthorized();
    }

    public function test_can_list_rooms_with_pagination_and_eager_loaded_ward_floor_building(): void
    {
        $building = Building::factory()->create(['name' => 'RUANG BUGENVILLE']);
        $floor = Floor::factory()->create(['building_id' => $building->id, 'name' => 'Lantai 2']);
        $ward = Ward::factory()->create(['floor_id' => $floor->id, 'name' => 'Bangsal Maternal Perinatal']);
        Room::factory()->count(15)->create(['ward_id' => $ward->id]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/rooms?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'ward_id',
                        'ward' => [
                            'id',
                            'name',
                            'floor' => [
                                'id',
                                'name',
                                'building' => [
                                    'id',
                                    'name',
                                ],
                            ],
                        ],
                        'ward_display_name',
                        'code',
                        'name',
                        'class_id',
                        'class',
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
        $this->assertEquals('Bangsal Maternal Perinatal', $response->json('data.0.ward.name'));
        $this->assertEquals('Lantai 2', $response->json('data.0.ward.floor.name'));
        $this->assertEquals('RUANG BUGENVILLE', $response->json('data.0.ward.floor.building.name'));
        $this->assertEquals(
            'Bangsal Maternal Perinatal (Lantai 2 - RUANG BUGENVILLE)',
            $response->json('data.0.ward_display_name')
        );
    }

    public function test_can_search_rooms_by_code(): void
    {
        $ward = Ward::factory()->create();
        Room::factory()->create(['ward_id' => $ward->id, 'code' => 'KMR-VIP-01', 'name' => 'Kamar VIP 1']);
        Room::factory()->create(['ward_id' => $ward->id, 'code' => 'KMR-REG-01', 'name' => 'Kamar Reguler 1']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/rooms?search=VIP');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'KMR-VIP-01');
    }

    public function test_can_search_rooms_by_name(): void
    {
        $ward = Ward::factory()->create();
        Room::factory()->create(['ward_id' => $ward->id, 'code' => 'KMR-01', 'name' => 'Kamar Melati']);
        Room::factory()->create(['ward_id' => $ward->id, 'code' => 'KMR-02', 'name' => 'Kamar Mawar']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/rooms?search=Melati');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Kamar Melati');
    }

    public function test_can_search_rooms_by_ward_name(): void
    {
        $wardA = Ward::factory()->create(['name' => 'Bangsal Anggrek']);
        $wardB = Ward::factory()->create(['name' => 'Bangsal Cempaka']);

        Room::factory()->create(['ward_id' => $wardA->id, 'code' => 'KMR-01', 'name' => 'Kamar 1']);
        Room::factory()->create(['ward_id' => $wardB->id, 'code' => 'KMR-02', 'name' => 'Kamar 2']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/rooms?search=Anggrek');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.ward.name', 'Bangsal Anggrek');
    }

    public function test_can_filter_rooms_by_ward_id(): void
    {
        $wardA = Ward::factory()->create();
        $wardB = Ward::factory()->create();

        Room::factory()->count(3)->create(['ward_id' => $wardA->id]);
        Room::factory()->count(2)->create(['ward_id' => $wardB->id]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/master-data/rooms?ward_id={$wardA->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_show_room_with_relations(): void
    {
        $building = Building::factory()->create(['name' => 'Gedung Utama']);
        $floor = Floor::factory()->create(['building_id' => $building->id, 'name' => 'Lantai 1']);
        $ward = Ward::factory()->create(['floor_id' => $floor->id, 'name' => 'Bangsal Anak']);
        $room = Room::factory()->create([
            'ward_id' => $ward->id,
            'code' => 'KMR-001',
            'name' => 'Kamar Anak 1',
            'description' => 'Kamar perawatan anak',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/master-data/rooms/{$room->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $room->id)
            ->assertJsonPath('data.ward_id', $ward->id)
            ->assertJsonPath('data.code', 'KMR-001')
            ->assertJsonPath('data.name', 'Kamar Anak 1')
            ->assertJsonPath('data.ward.name', 'Bangsal Anak')
            ->assertJsonPath('data.ward.floor.name', 'Lantai 1')
            ->assertJsonPath('data.ward.floor.building.name', 'Gedung Utama')
            ->assertJsonPath('data.ward_display_name', 'Bangsal Anak (Lantai 1 - Gedung Utama)')
            ->assertJsonPath('data.class', null);
    }

    public function test_can_create_room_without_class_id_when_class_not_yet_integrated(): void
    {
        $building = Building::factory()->create(['name' => 'RUANG BUGENVILLE']);
        $floor = Floor::factory()->create(['building_id' => $building->id, 'name' => 'Lantai 2']);
        $ward = Ward::factory()->create(['floor_id' => $floor->id, 'name' => 'Bangsal Maternal Perinatal']);

        $payload = [
            'ward_id' => $ward->id,
            'code' => 'KMR-001',
            'name' => 'Kamar Melati 1',
            'class_id' => null,
            'description' => 'Kamar perawatan maternal',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.ward_id', $ward->id)
            ->assertJsonPath('data.code', 'KMR-001')
            ->assertJsonPath('data.name', 'Kamar Melati 1')
            ->assertJsonPath('data.ward.name', 'Bangsal Maternal Perinatal')
            ->assertJsonPath('data.ward.floor.name', 'Lantai 2')
            ->assertJsonPath('data.ward.floor.building.name', 'RUANG BUGENVILLE')
            ->assertJsonPath('data.ward_display_name', 'Bangsal Maternal Perinatal (Lantai 2 - RUANG BUGENVILLE)')
            ->assertJsonPath('data.class_id', null)
            ->assertJsonPath('data.class', null)
            ->assertJsonPath('data.description', 'Kamar perawatan maternal');

        $this->assertDatabaseHas('rooms', [
            'ward_id' => $ward->id,
            'code' => 'KMR-001',
            'name' => 'Kamar Melati 1',
            'class_id' => null,
        ]);
    }

    public function test_create_room_trims_excessive_whitespace(): void
    {
        $ward = Ward::factory()->create();

        $payload = [
            'ward_id' => $ward->id,
            'code' => '   KMR - 002   ',
            'name' => '   Kamar    Mawar   1   ',
            'description' => '   Deskripsi kamar   ',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'KMR - 002')
            ->assertJsonPath('data.name', 'Kamar Mawar 1')
            ->assertJsonPath('data.description', 'Deskripsi kamar');

        $this->assertDatabaseHas('rooms', [
            'ward_id' => $ward->id,
            'code' => 'KMR - 002',
            'name' => 'Kamar Mawar 1',
        ]);
    }

    public function test_validation_ward_id_is_required_to_create_room(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'code' => 'KMR-001',
            'name' => 'Kamar 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ward_id']);
    }

    public function test_validation_ward_id_must_exist_and_not_be_soft_deleted(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => 999999,
            'code' => 'KMR-001',
            'name' => 'Kamar 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ward_id']);

        $softDeletedWard = Ward::factory()->create();
        $softDeletedWard->delete();

        $responseDeleted = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $softDeletedWard->id,
            'code' => 'KMR-001',
            'name' => 'Kamar 1',
        ]);

        $responseDeleted->assertStatus(422)
            ->assertJsonValidationErrors(['ward_id']);
    }

    public function test_validation_ward_must_have_valid_floor_and_building(): void
    {
        $building = Building::factory()->create();
        $floor = Floor::factory()->create(['building_id' => $building->id]);
        $ward = Ward::factory()->create(['floor_id' => $floor->id]);
        $floor->delete(); // Soft delete floor

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $ward->id,
            'code' => 'KMR-001',
            'name' => 'Kamar 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ward_id'])
            ->assertJsonPath('errors.ward_id.0', 'Bangsal yang dipilih harus memiliki relasi Lantai dan Gedung yang valid.');
    }

    public function test_validation_code_is_required_and_unique(): void
    {
        $ward = Ward::factory()->create();
        Room::factory()->create(['ward_id' => $ward->id, 'code' => 'KMR-EXISTING']);

        // Empty code
        $resEmpty = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $ward->id,
            'code' => '',
            'name' => 'Kamar Baru',
        ]);
        $resEmpty->assertStatus(422)->assertJsonValidationErrors(['code']);

        // Duplicate code
        $resDup = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $ward->id,
            'code' => 'KMR-EXISTING',
            'name' => 'Kamar Lain',
        ]);
        $resDup->assertStatus(422)
            ->assertJsonValidationErrors(['code'])
            ->assertJsonPath('errors.code.0', 'Kode kamar sudah digunakan.');
    }

    public function test_validation_name_is_required(): void
    {
        $ward = Ward::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $ward->id,
            'code' => 'KMR-001',
            'name' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_validation_prevents_duplicate_room_name_in_same_ward(): void
    {
        $ward = Ward::factory()->create();
        Room::factory()->create([
            'ward_id' => $ward->id,
            'code' => 'KMR-001',
            'name' => 'Kamar Melati 1',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $ward->id,
            'code' => 'KMR-002',
            'name' => 'Kamar Melati 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJsonPath('errors.name.0', 'Nama kamar sudah digunakan pada bangsal yang dipilih.');
    }

    public function test_can_create_same_room_name_in_different_ward(): void
    {
        $wardA = Ward::factory()->create();
        $wardB = Ward::factory()->create();

        Room::factory()->create([
            'ward_id' => $wardA->id,
            'code' => 'KMR-A-01',
            'name' => 'Kamar 1',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $wardB->id,
            'code' => 'KMR-B-01',
            'name' => 'Kamar 1',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.ward_id', $wardB->id)
            ->assertJsonPath('data.name', 'Kamar 1');

        $this->assertDatabaseHas('rooms', [
            'ward_id' => $wardB->id,
            'code' => 'KMR-B-01',
            'name' => 'Kamar 1',
        ]);
    }

    public function test_can_create_room_with_same_name_or_code_as_soft_deleted_room(): void
    {
        $ward = Ward::factory()->create();
        $deletedRoom = Room::factory()->create([
            'ward_id' => $ward->id,
            'code' => 'KMR-DEL-01',
            'name' => 'Kamar Terhapus',
        ]);
        $deletedRoom->delete();

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $ward->id,
            'code' => 'KMR-DEL-01',
            'name' => 'Kamar Terhapus',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'KMR-DEL-01')
            ->assertJsonPath('data.name', 'Kamar Terhapus');
    }

    public function test_can_update_room(): void
    {
        $wardA = Ward::factory()->create();
        $wardB = Ward::factory()->create();
        $room = Room::factory()->create([
            'ward_id' => $wardA->id,
            'code' => 'KMR-AWAL',
            'name' => 'Kamar Awal',
            'description' => 'Awal',
        ]);

        $payload = [
            'ward_id' => $wardB->id,
            'code' => 'KMR-BARU',
            'name' => 'Kamar Baru',
            'description' => 'Deskripsi Baru',
        ];

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/rooms/{$room->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('data.id', $room->id)
            ->assertJsonPath('data.ward_id', $wardB->id)
            ->assertJsonPath('data.code', 'KMR-BARU')
            ->assertJsonPath('data.name', 'Kamar Baru')
            ->assertJsonPath('data.description', 'Deskripsi Baru');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'ward_id' => $wardB->id,
            'code' => 'KMR-BARU',
            'name' => 'Kamar Baru',
            'description' => 'Deskripsi Baru',
        ]);
    }

    public function test_can_update_room_keeping_same_name_and_code(): void
    {
        $ward = Ward::factory()->create();
        $room = Room::factory()->create([
            'ward_id' => $ward->id,
            'code' => 'KMR-TETAP',
            'name' => 'Kamar Tetap',
            'description' => 'Awal',
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/rooms/{$room->id}", [
            'ward_id' => $ward->id,
            'code' => 'KMR-TETAP',
            'name' => 'Kamar Tetap',
            'description' => 'Deskripsi Diperbarui',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.code', 'KMR-TETAP')
            ->assertJsonPath('data.name', 'Kamar Tetap')
            ->assertJsonPath('data.description', 'Deskripsi Diperbarui');
    }

    public function test_validation_prevents_duplicate_code_or_name_on_update(): void
    {
        $ward = Ward::factory()->create();
        Room::factory()->create(['ward_id' => $ward->id, 'code' => 'KMR-01', 'name' => 'Kamar 1']);
        $roomB = Room::factory()->create(['ward_id' => $ward->id, 'code' => 'KMR-02', 'name' => 'Kamar 2']);

        // Update with duplicate code
        $resCode = $this->actingAs($this->user)->putJson("/api/v1/master-data/rooms/{$roomB->id}", [
            'ward_id' => $ward->id,
            'code' => 'KMR-01',
            'name' => 'Kamar 2',
        ]);
        $resCode->assertStatus(422)->assertJsonValidationErrors(['code']);

        // Update with duplicate name in same ward
        $resName = $this->actingAs($this->user)->putJson("/api/v1/master-data/rooms/{$roomB->id}", [
            'ward_id' => $ward->id,
            'code' => 'KMR-02',
            'name' => 'Kamar 1',
        ]);
        $resName->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_can_delete_room_using_soft_delete(): void
    {
        $room = Room::factory()->create(['name' => 'Kamar Hapus']);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/rooms/{$room->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('rooms', [
            'id' => $room->id,
            'name' => 'Kamar Hapus',
        ]);

        // Soft deleted rooms do not appear in index listing
        $listResponse = $this->actingAs($this->user)->getJson('/api/v1/master-data/rooms');
        $listResponse->assertOk()
            ->assertJsonMissing(['name' => 'Kamar Hapus']);
    }

    public function test_ward_deletion_is_prevented_if_has_rooms(): void
    {
        $ward = Ward::factory()->create(['name' => 'Bangsal Ada Kamar']);
        Room::factory()->create(['ward_id' => $ward->id, 'name' => 'Kamar 1']);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/wards/{$ward->id}");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Bangsal tidak dapat dihapus karena masih digunakan oleh data kamar.');

        $this->assertNotSoftDeleted('wards', [
            'id' => $ward->id,
        ]);
    }
}
