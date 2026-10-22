<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\Building;
use App\Models\MasterData\Floor;
use App\Models\MasterData\Room;
use App\Models\MasterData\RoomClass;
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

    public function test_can_list_rooms_with_pagination_and_eager_loaded_ward_floor_building_and_room_class(): void
    {
        $building = Building::factory()->create(['name' => 'RUANG BUGENVILLE']);
        $floor = Floor::factory()->create(['building_id' => $building->id, 'name' => 'Lantai 2']);
        $ward = Ward::factory()->create(['floor_id' => $floor->id, 'name' => 'Bangsal Maternal Perinatal']);
        $roomClass = RoomClass::factory()->create(['name' => 'Kelas II', 'code' => 'KLS-02']);
        Room::factory()->count(15)->create(['ward_id' => $ward->id, 'room_class_id' => $roomClass->id]);

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
                        'room_class_id',
                        'room_class' => [
                            'id',
                            'code',
                            'name',
                        ],
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
        $this->assertEquals('Kelas II', $response->json('data.0.room_class.name'));
        $this->assertEquals('KLS-02', $response->json('data.0.room_class.code'));
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
        Room::factory()->create(['ward_id' => $ward->id, 'code' => 'KMR-01', 'name' => 'Kamar Mawar 1']);
        Room::factory()->create(['ward_id' => $ward->id, 'code' => 'KMR-02', 'name' => 'Kamar Melati 1']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/rooms?search=Melati');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Kamar Melati 1');
    }

    public function test_can_search_rooms_by_class_name(): void
    {
        $ward = Ward::factory()->create();
        $classVIP = RoomClass::factory()->create(['name' => 'Kelas VIP Khusus']);
        $classReg = RoomClass::factory()->create(['name' => 'Kelas Reguler']);

        Room::factory()->create(['ward_id' => $ward->id, 'name' => 'Kamar A', 'room_class_id' => $classVIP->id]);
        Room::factory()->create(['ward_id' => $ward->id, 'name' => 'Kamar B', 'room_class_id' => $classReg->id]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/rooms?search=VIP Khusus');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Kamar A')
            ->assertJsonPath('data.0.room_class.name', 'Kelas VIP Khusus');
    }

    public function test_can_search_rooms_by_ward_name(): void
    {
        $wardA = Ward::factory()->create(['name' => 'Bangsal Anggrek']);
        $wardB = Ward::factory()->create(['name' => 'Bangsal Melati']);

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
        $roomClass = RoomClass::factory()->create(['name' => 'Kelas I', 'code' => 'KLS-01']);
        $room = Room::factory()->create([
            'ward_id' => $ward->id,
            'room_class_id' => $roomClass->id,
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
            ->assertJsonPath('data.room_class_id', $roomClass->id)
            ->assertJsonPath('data.room_class.name', 'Kelas I')
            ->assertJsonPath('data.ward.name', 'Bangsal Anak')
            ->assertJsonPath('data.ward.floor.name', 'Lantai 1')
            ->assertJsonPath('data.ward.floor.building.name', 'Gedung Utama')
            ->assertJsonPath('data.ward_display_name', 'Bangsal Anak (Lantai 1 - Gedung Utama)');
    }

    public function test_can_create_room_with_room_class(): void
    {
        $building = Building::factory()->create(['name' => 'RUANG BUGENVILLE']);
        $floor = Floor::factory()->create(['building_id' => $building->id, 'name' => 'Lantai 2']);
        $ward = Ward::factory()->create(['floor_id' => $floor->id, 'name' => 'Bangsal Maternal Perinatal']);
        $roomClass = RoomClass::factory()->create(['name' => 'Kelas II', 'code' => 'KLS-02']);

        $payload = [
            'ward_id' => $ward->id,
            'room_class_id' => $roomClass->id,
            'code' => 'KMR-001',
            'name' => 'Kamar Melati 1',
            'description' => 'Kamar perawatan maternal',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.ward_id', $ward->id)
            ->assertJsonPath('data.code', 'KMR-001')
            ->assertJsonPath('data.name', 'Kamar Melati 1')
            ->assertJsonPath('data.room_class_id', $roomClass->id)
            ->assertJsonPath('data.room_class.name', 'Kelas II')
            ->assertJsonPath('data.ward.name', 'Bangsal Maternal Perinatal')
            ->assertJsonPath('data.ward.floor.name', 'Lantai 2')
            ->assertJsonPath('data.ward.floor.building.name', 'RUANG BUGENVILLE')
            ->assertJsonPath('data.ward_display_name', 'Bangsal Maternal Perinatal (Lantai 2 - RUANG BUGENVILLE)')
            ->assertJsonPath('data.description', 'Kamar perawatan maternal');

        $this->assertDatabaseHas('rooms', [
            'ward_id' => $ward->id,
            'room_class_id' => $roomClass->id,
            'code' => 'KMR-001',
            'name' => 'Kamar Melati 1',
        ]);
    }

    public function test_create_room_trims_excessive_whitespace(): void
    {
        $ward = Ward::factory()->create();
        $roomClass = RoomClass::factory()->create();

        $payload = [
            'ward_id' => $ward->id,
            'room_class_id' => $roomClass->id,
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
            'room_class_id' => $roomClass->id,
            'code' => 'KMR - 002',
            'name' => 'Kamar Mawar 1',
        ]);
    }

    public function test_validation_ward_id_is_required_to_create_room(): void
    {
        $roomClass = RoomClass::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'room_class_id' => $roomClass->id,
            'code' => 'KMR-001',
            'name' => 'Kamar 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ward_id']);
    }

    public function test_validation_room_class_id_is_required_to_create_room(): void
    {
        $ward = Ward::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $ward->id,
            'code' => 'KMR-001',
            'name' => 'Kamar 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['room_class_id'])
            ->assertJsonPath('errors.room_class_id.0', 'Kelas wajib dipilih.');
    }

    public function test_validation_room_class_id_must_exist_and_not_be_soft_deleted(): void
    {
        $ward = Ward::factory()->create();

        // Non-existent class ID
        $resNotFound = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $ward->id,
            'room_class_id' => 999999,
            'code' => 'KMR-001',
            'name' => 'Kamar 1',
        ]);

        $resNotFound->assertStatus(422)
            ->assertJsonValidationErrors(['room_class_id'])
            ->assertJsonPath('errors.room_class_id.0', 'Kelas yang dipilih tidak valid.');

        // Soft deleted class
        $softDeletedClass = RoomClass::factory()->create();
        $softDeletedClass->delete();

        $resDeleted = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $ward->id,
            'room_class_id' => $softDeletedClass->id,
            'code' => 'KMR-001',
            'name' => 'Kamar 1',
        ]);

        $resDeleted->assertStatus(422)
            ->assertJsonValidationErrors(['room_class_id'])
            ->assertJsonPath('errors.room_class_id.0', 'Kelas yang dipilih tidak valid.');
    }

    public function test_validation_ward_id_must_exist_and_not_be_soft_deleted(): void
    {
        $roomClass = RoomClass::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => 999999,
            'room_class_id' => $roomClass->id,
            'code' => 'KMR-001',
            'name' => 'Kamar 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ward_id']);

        $softDeletedWard = Ward::factory()->create();
        $softDeletedWard->delete();

        $responseDeleted = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $softDeletedWard->id,
            'room_class_id' => $roomClass->id,
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
        $roomClass = RoomClass::factory()->create();
        $floor->delete(); // Soft delete floor

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $ward->id,
            'room_class_id' => $roomClass->id,
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
        $roomClass = RoomClass::factory()->create();
        Room::factory()->create(['ward_id' => $ward->id, 'room_class_id' => $roomClass->id, 'code' => 'KMR-EXISTING']);

        // Empty code
        $resEmpty = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $ward->id,
            'room_class_id' => $roomClass->id,
            'code' => '',
            'name' => 'Kamar Baru',
        ]);
        $resEmpty->assertStatus(422)->assertJsonValidationErrors(['code']);

        // Duplicate code
        $resDup = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $ward->id,
            'room_class_id' => $roomClass->id,
            'code' => 'KMR-EXISTING',
            'name' => 'Kamar Lain',
        ]);
        $resDup->assertStatus(422)->assertJsonValidationErrors(['code']);
    }

    public function test_validation_name_is_required_and_unique_per_ward(): void
    {
        $ward = Ward::factory()->create();
        $roomClass = RoomClass::factory()->create();
        Room::factory()->create(['ward_id' => $ward->id, 'room_class_id' => $roomClass->id, 'code' => 'KMR-01', 'name' => 'Kamar Mawar']);

        // Empty name
        $resEmpty = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $ward->id,
            'room_class_id' => $roomClass->id,
            'code' => 'KMR-02',
            'name' => '',
        ]);
        $resEmpty->assertStatus(422)->assertJsonValidationErrors(['name']);

        // Duplicate name in same ward
        $resDup = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $ward->id,
            'room_class_id' => $roomClass->id,
            'code' => 'KMR-02',
            'name' => 'Kamar Mawar',
        ]);
        $resDup->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_can_create_second_room_with_same_name_in_different_ward(): void
    {
        $wardA = Ward::factory()->create();
        $wardB = Ward::factory()->create();
        $roomClass = RoomClass::factory()->create();

        Room::factory()->create(['ward_id' => $wardA->id, 'room_class_id' => $roomClass->id, 'code' => 'KMR-A-01', 'name' => 'Kamar 1']);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $wardB->id,
            'room_class_id' => $roomClass->id,
            'code' => 'KMR-B-01',
            'name' => 'Kamar 1',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.ward_id', $wardB->id)
            ->assertJsonPath('data.name', 'Kamar 1');

        $this->assertDatabaseHas('rooms', [
            'ward_id' => $wardB->id,
            'room_class_id' => $roomClass->id,
            'code' => 'KMR-B-01',
            'name' => 'Kamar 1',
        ]);
    }

    public function test_can_create_room_with_same_name_or_code_as_soft_deleted_room(): void
    {
        $ward = Ward::factory()->create();
        $roomClass = RoomClass::factory()->create();
        $deletedRoom = Room::factory()->create([
            'ward_id' => $ward->id,
            'room_class_id' => $roomClass->id,
            'code' => 'KMR-DEL-01',
            'name' => 'Kamar Terhapus',
        ]);
        $deletedRoom->delete();

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $ward->id,
            'room_class_id' => $roomClass->id,
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
        $classA = RoomClass::factory()->create(['name' => 'Kelas I']);
        $classB = RoomClass::factory()->create(['name' => 'Kelas II']);
        $room = Room::factory()->create([
            'ward_id' => $wardA->id,
            'room_class_id' => $classA->id,
            'code' => 'KMR-AWAL',
            'name' => 'Kamar Awal',
            'description' => 'Awal',
        ]);

        $payload = [
            'ward_id' => $wardB->id,
            'room_class_id' => $classB->id,
            'code' => 'KMR-BARU',
            'name' => 'Kamar Baru',
            'description' => 'Deskripsi Baru',
        ];

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/rooms/{$room->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('data.id', $room->id)
            ->assertJsonPath('data.ward_id', $wardB->id)
            ->assertJsonPath('data.room_class_id', $classB->id)
            ->assertJsonPath('data.room_class.name', 'Kelas II')
            ->assertJsonPath('data.code', 'KMR-BARU')
            ->assertJsonPath('data.name', 'Kamar Baru')
            ->assertJsonPath('data.description', 'Deskripsi Baru');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'ward_id' => $wardB->id,
            'room_class_id' => $classB->id,
            'code' => 'KMR-BARU',
            'name' => 'Kamar Baru',
            'description' => 'Deskripsi Baru',
        ]);
    }

    public function test_can_update_room_keeping_same_name_and_code(): void
    {
        $ward = Ward::factory()->create();
        $roomClass = RoomClass::factory()->create();
        $room = Room::factory()->create([
            'ward_id' => $ward->id,
            'room_class_id' => $roomClass->id,
            'code' => 'KMR-TETAP',
            'name' => 'Kamar Tetap',
            'description' => 'Awal',
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/rooms/{$room->id}", [
            'ward_id' => $ward->id,
            'room_class_id' => $roomClass->id,
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
        $roomClass = RoomClass::factory()->create();
        Room::factory()->create(['ward_id' => $ward->id, 'room_class_id' => $roomClass->id, 'code' => 'KMR-01', 'name' => 'Kamar 1']);
        $roomB = Room::factory()->create(['ward_id' => $ward->id, 'room_class_id' => $roomClass->id, 'code' => 'KMR-02', 'name' => 'Kamar 2']);

        // Update with duplicate code
        $resCode = $this->actingAs($this->user)->putJson("/api/v1/master-data/rooms/{$roomB->id}", [
            'ward_id' => $ward->id,
            'room_class_id' => $roomClass->id,
            'code' => 'KMR-01',
            'name' => 'Kamar 2',
        ]);
        $resCode->assertStatus(422)->assertJsonValidationErrors(['code']);

        // Update with duplicate name in same ward
        $resName = $this->actingAs($this->user)->putJson("/api/v1/master-data/rooms/{$roomB->id}", [
            'ward_id' => $ward->id,
            'room_class_id' => $roomClass->id,
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

    public function test_legacy_room_without_class_does_not_break_api(): void
    {
        $ward = Ward::factory()->create();
        // Room with null room_class_id (simulating legacy data prior to class mapping)
        $room = Room::factory()->create(['ward_id' => $ward->id, 'room_class_id' => null, 'class_id' => null]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/master-data/rooms/{$room->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $room->id)
            ->assertJsonPath('data.room_class', null)
            ->assertJsonPath('data.room_class_id', null);
    }
}
