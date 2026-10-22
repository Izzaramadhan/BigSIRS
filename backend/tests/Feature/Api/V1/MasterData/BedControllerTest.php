<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\Bed;
use App\Models\MasterData\Building;
use App\Models\MasterData\Floor;
use App\Models\MasterData\Room;
use App\Models\MasterData\RoomClass;
use App\Models\MasterData\Ward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BedControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    protected function createValidRoom(array $roomAttributes = []): Room
    {
        $building = Building::factory()->create(['name' => 'RUANG BUGENVILLE']);
        $floor = Floor::factory()->create(['building_id' => $building->id, 'name' => 'Lantai 2']);
        $ward = Ward::factory()->create(['floor_id' => $floor->id, 'name' => 'Bangsal Maternal Perinatal']);
        $roomClass = RoomClass::factory()->create(['name' => 'Kelas 3', 'code' => 'KLS-3']);

        return Room::factory()->create(array_merge([
            'ward_id' => $ward->id,
            'room_class_id' => $roomClass->id,
            'name' => 'Kamar 1',
            'code' => 'KMR-001',
        ], $roomAttributes));
    }

    public function test_unauthenticated_user_cannot_access_beds(): void
    {
        $this->getJson('/api/v1/master-data/beds')->assertUnauthorized();
        $this->postJson('/api/v1/master-data/beds', ['name' => 'Bed 1'])->assertUnauthorized();
        $this->getJson('/api/v1/master-data/beds/1')->assertUnauthorized();
        $this->putJson('/api/v1/master-data/beds/1', ['name' => 'Bed 1'])->assertUnauthorized();
        $this->deleteJson('/api/v1/master-data/beds/1')->assertUnauthorized();
    }

    public function test_can_list_beds_with_pagination_and_eager_loaded_room_ward_and_room_class(): void
    {
        $room = $this->createValidRoom();
        Bed::factory()->count(15)->create(['room_id' => $room->id]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/beds?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'legacy_id',
                        'room_id',
                        'name',
                        'description',
                        'room' => [
                            'id',
                            'code',
                            'name',
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
                            'room_class' => [
                                'id',
                                'code',
                                'name',
                            ],
                            'class',
                        ],
                        'room_table_display_name',
                        'room_form_display_name',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links',
            ]);

        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(15, $response->json('meta.total'));
        $this->assertEquals('Kamar 1', $response->json('data.0.room.name'));
        $this->assertEquals('Bangsal Maternal Perinatal', $response->json('data.0.room.ward.name'));
        $this->assertEquals('Kelas 3', $response->json('data.0.room.room_class.name'));
        $this->assertEquals('Kamar 1 - Bangsal Maternal Perinatal', $response->json('data.0.room_table_display_name'));
        $this->assertEquals('Kamar 1 - Bangsal Maternal Perinatal - Kelas 3', $response->json('data.0.room_form_display_name'));
    }

    public function test_response_contains_room_ward_and_room_class_relations(): void
    {
        $room = $this->createValidRoom();
        $bed = Bed::factory()->create([
            'room_id' => $room->id,
            'name' => 'Bed 101',
            'description' => 'Dekat jendela',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/master-data/beds/{$bed->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $bed->id)
            ->assertJsonPath('data.name', 'Bed 101')
            ->assertJsonPath('data.room.id', $room->id)
            ->assertJsonPath('data.room.name', 'Kamar 1')
            ->assertJsonPath('data.room.ward.name', 'Bangsal Maternal Perinatal')
            ->assertJsonPath('data.room.ward.floor.name', 'Lantai 2')
            ->assertJsonPath('data.room.ward.floor.building.name', 'RUANG BUGENVILLE')
            ->assertJsonPath('data.room.room_class.name', 'Kelas 3');
    }

    public function test_can_create_bed(): void
    {
        $room = $this->createValidRoom();

        $payload = [
            'room_id' => $room->id,
            'name' => 'Bed 1',
            'description' => 'Bed dekat jendela',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/beds', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Bed 1')
            ->assertJsonPath('data.description', 'Bed dekat jendela')
            ->assertJsonPath('data.room_id', $room->id);

        $this->assertDatabaseHas('beds', [
            'room_id' => $room->id,
            'name' => 'Bed 1',
            'description' => 'Bed dekat jendela',
            'deleted_at' => null,
        ]);
    }

    public function test_room_id_is_required(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/beds', [
            'name' => 'Bed 1',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['room_id']);
    }

    public function test_room_id_must_be_valid(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/beds', [
            'room_id' => 99999,
            'name' => 'Bed 1',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['room_id']);
    }

    public function test_cannot_select_deleted_room(): void
    {
        $room = $this->createValidRoom();
        $room->delete();

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/beds', [
            'room_id' => $room->id,
            'name' => 'Bed 1',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['room_id']);
    }

    public function test_name_is_required(): void
    {
        $room = $this->createValidRoom();

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/beds', [
            'room_id' => $room->id,
            'name' => '',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_duplicate_bed_name_in_same_room_is_rejected(): void
    {
        $room = $this->createValidRoom();
        Bed::factory()->create(['room_id' => $room->id, 'name' => 'Bed 1']);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/beds', [
            'room_id' => $room->id,
            'name' => 'Bed 1',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        // Case-insensitive duplicate
        $responseCase = $this->actingAs($this->user)->postJson('/api/v1/master-data/beds', [
            'room_id' => $room->id,
            'name' => 'bed 1',
        ]);

        $responseCase->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_same_bed_name_in_different_rooms_is_allowed(): void
    {
        $room1 = $this->createValidRoom(['name' => 'Kamar 1', 'code' => 'KMR-001']);
        $room2 = $this->createValidRoom(['name' => 'Kamar 2', 'code' => 'KMR-002']);

        Bed::factory()->create(['room_id' => $room1->id, 'name' => 'Bed 1']);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/beds', [
            'room_id' => $room2->id,
            'name' => 'Bed 1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Bed 1')
            ->assertJsonPath('data.room_id', $room2->id);

        $this->assertDatabaseHas('beds', [
            'room_id' => $room2->id,
            'name' => 'Bed 1',
        ]);
    }

    public function test_can_update_bed(): void
    {
        $room = $this->createValidRoom();
        $bed = Bed::factory()->create(['room_id' => $room->id, 'name' => 'Bed Lama']);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/beds/{$bed->id}", [
            'room_id' => $room->id,
            'name' => 'Bed Baru',
            'description' => 'Deskripsi baru',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Bed Baru')
            ->assertJsonPath('data.description', 'Deskripsi baru');

        $this->assertDatabaseHas('beds', [
            'id' => $bed->id,
            'name' => 'Bed Baru',
            'description' => 'Deskripsi baru',
        ]);
    }

    public function test_unique_validation_ignores_self_on_update(): void
    {
        $room = $this->createValidRoom();
        $bed = Bed::factory()->create(['room_id' => $room->id, 'name' => 'Bed 1']);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/beds/{$bed->id}", [
            'room_id' => $room->id,
            'name' => 'Bed 1',
            'description' => 'Updated desc',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Bed 1');
    }

    public function test_can_change_room_on_bed(): void
    {
        $room1 = $this->createValidRoom(['name' => 'Kamar 1', 'code' => 'KMR-001']);
        $room2 = $this->createValidRoom(['name' => 'Kamar 2', 'code' => 'KMR-002']);
        $bed = Bed::factory()->create(['room_id' => $room1->id, 'name' => 'Bed 1']);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/beds/{$bed->id}", [
            'room_id' => $room2->id,
            'name' => 'Bed 1',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.room_id', $room2->id);

        $this->assertDatabaseHas('beds', [
            'id' => $bed->id,
            'room_id' => $room2->id,
        ]);
    }

    public function test_can_delete_bed(): void
    {
        $room = $this->createValidRoom();
        $bed = Bed::factory()->create(['room_id' => $room->id, 'name' => 'Bed 1']);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/beds/{$bed->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('beds', ['id' => $bed->id]);
    }

    public function test_delete_is_rejected_if_bed_is_used_in_transactions(): void
    {
        $room = $this->createValidRoom();
        $bed = Bed::factory()->create([
            'room_id' => $room->id,
            'name' => 'Bed Transaksi',
            'legacy_id' => 999999,
        ]);

        // Mock method or anonymous subclass simulating active transaction
        $mockBed = new class extends Bed {
            public function isUsedInTransactions(): bool
            {
                return true;
            }
        };

        // Or we test through controller by binding a mock or simulating Bed::isUsedInTransactions
        // Since isUsedInTransactions calls method_exists or legacy trx_bed, we can test that when isUsedInTransactions returns true it aborts with 422:
        $this->instance(Bed::class, $mockBed);

        // Let's create an actual bed and test with isUsedInTransactions
        // To test cleanly without mocking container if route-model binding is used:
        // We can create a test checking the controller response
        $controller = new \App\Http\Controllers\Api\V1\MasterData\BedController();
        $response = $controller->destroy($mockBed);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertStringContainsString('Bed tidak dapat dihapus', $response->getData()->message);
    }

    public function test_soft_deleted_bed_does_not_appear_in_index(): void
    {
        $room = $this->createValidRoom();
        $bed1 = Bed::factory()->create(['room_id' => $room->id, 'name' => 'Bed Aktif']);
        $bed2 = Bed::factory()->create(['room_id' => $room->id, 'name' => 'Bed Terhapus']);
        $bed2->delete();

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/beds');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertContains('Bed Aktif', $names);
        $this->assertNotContains('Bed Terhapus', $names);
    }

    public function test_search_can_find_data_by_bed_room_ward_or_class(): void
    {
        $building = Building::factory()->create(['name' => 'Gedung Teratai']);
        $floor = Floor::factory()->create(['building_id' => $building->id, 'name' => 'Lantai 3']);
        $ward = Ward::factory()->create(['floor_id' => $floor->id, 'name' => 'Bangsal Anak']);
        $roomClass = RoomClass::factory()->create(['name' => 'Kelas VIP Khusus', 'code' => 'VIP-KH']);

        $room = Room::factory()->create([
            'ward_id' => $ward->id,
            'room_class_id' => $roomClass->id,
            'name' => 'Kamar Melati Super',
            'code' => 'KMR-MLT-01',
        ]);

        $bed = Bed::factory()->create([
            'room_id' => $room->id,
            'name' => 'Bed Khusus Anak',
            'description' => 'Bed elektrik khusus anak',
        ]);

        // Search by Bed name
        $res1 = $this->actingAs($this->user)->getJson('/api/v1/master-data/beds?search=Khusus+Anak');
        $res1->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals($bed->id, $res1->json('data.0.id'));

        // Search by Room name
        $res2 = $this->actingAs($this->user)->getJson('/api/v1/master-data/beds?search=Melati+Super');
        $res2->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals($bed->id, $res2->json('data.0.id'));

        // Search by Ward name
        $res3 = $this->actingAs($this->user)->getJson('/api/v1/master-data/beds?search=Bangsal+Anak');
        $res3->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals($bed->id, $res3->json('data.0.id'));

        // Search by Class name
        $res4 = $this->actingAs($this->user)->getJson('/api/v1/master-data/beds?search=VIP+Khusus');
        $res4->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals($bed->id, $res4->json('data.0.id'));
    }

    public function test_pagination_works_correctly(): void
    {
        $room = $this->createValidRoom();
        Bed::factory()->count(25)->create(['room_id' => $room->id]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/beds?per_page=10&page=2');

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonCount(10, 'data');
    }
}
