<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\Room;
use App\Models\MasterData\RoomClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomClassControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_user_cannot_access_room_classes(): void
    {
        $this->getJson('/api/v1/master-data/room-classes/options')->assertUnauthorized();
        $this->getJson('/api/v1/master-data/room-classes')->assertUnauthorized();
        $this->postJson('/api/v1/master-data/room-classes', ['name' => 'Kelas I'])->assertUnauthorized();
        $this->getJson('/api/v1/master-data/room-classes/1')->assertUnauthorized();
        $this->putJson('/api/v1/master-data/room-classes/1', ['name' => 'Kelas I'])->assertUnauthorized();
        $this->deleteJson('/api/v1/master-data/room-classes/1')->assertUnauthorized();
    }

    public function test_can_list_room_classes_with_pagination(): void
    {
        RoomClass::factory()->count(15)->create();

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/room-classes?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'price',
                        'formatted_price',
                        'description',
                        'aplicare_code',
                        'sirs_code',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links',
            ]);

        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(15, $response->json('meta.total'));
    }

    public function test_can_create_room_class_with_all_fields(): void
    {
        $payload = [
            'code' => 'KLS-03',
            'name' => 'Kelas 3',
            'price' => 150000,
            'description' => 'Kelas perawatan tingkat tiga',
            'aplicare_code' => '03',
            'sirs_code' => '3',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/room-classes', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'KLS-03')
            ->assertJsonPath('data.name', 'Kelas 3')
            ->assertJsonPath('data.price', 150000)
            ->assertJsonPath('data.formatted_price', 'Rp150.000')
            ->assertJsonPath('data.description', 'Kelas perawatan tingkat tiga')
            ->assertJsonPath('data.aplicare_code', '03')
            ->assertJsonPath('data.sirs_code', '3');

        $this->assertDatabaseHas('room_classes', [
            'code' => 'KLS-03',
            'name' => 'Kelas 3',
            'price' => 150000.00,
            'aplicare_code' => '03',
            'sirs_code' => '3',
            'deleted_at' => null,
        ]);
    }

    public function test_create_room_class_validates_required_name(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/room-classes', [
            'name' => '',
            'code' => 'KLS-01',
            'price' => 100000,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJsonPath('errors.name.0', 'Nama kelas wajib diisi.');
    }

    public function test_create_room_class_rejects_duplicate_name_case_insensitive(): void
    {
        RoomClass::factory()->create(['name' => 'Kelas VIP']);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/room-classes', [
            'name' => '   kelas   vip   ',
            'code' => 'KLS-VIP-2',
            'price' => 500000,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJsonPath('errors.name.0', 'Nama kelas sudah digunakan.');
    }

    public function test_create_room_class_validates_required_code(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/room-classes', [
            'name' => 'Kelas Baru',
            'code' => '',
            'price' => 100000,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code'])
            ->assertJsonPath('errors.code.0', 'Kode kelas wajib diisi.');
    }

    public function test_create_room_class_rejects_duplicate_code_case_insensitive(): void
    {
        RoomClass::factory()->create(['code' => 'VIP']);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/room-classes', [
            'name' => 'Kelas Baru',
            'code' => '  vip  ',
            'price' => 500000,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code'])
            ->assertJsonPath('errors.code.0', 'Kode kelas sudah digunakan.');
    }

    public function test_create_room_class_validates_required_price(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/room-classes', [
            'name' => 'Kelas Baru',
            'code' => 'KL-BR',
            'price' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price'])
            ->assertJsonPath('errors.price.0', 'Harga kelas wajib diisi.');
    }

    public function test_create_room_class_validates_price_must_be_numeric(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/room-classes', [
            'name' => 'Kelas Baru',
            'code' => 'KL-BR',
            'price' => 'gratis',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price'])
            ->assertJsonPath('errors.price.0', 'Harga kelas harus berupa angka.');
    }

    public function test_create_room_class_validates_price_cannot_be_negative(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/room-classes', [
            'name' => 'Kelas Baru',
            'code' => 'KL-BR',
            'price' => -50000,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price'])
            ->assertJsonPath('errors.price.0', 'Harga kelas tidak boleh bernilai negatif.');
    }

    public function test_aplicare_code_and_sirs_code_are_optional(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/room-classes', [
            'name' => 'Kelas Tanpa Kode',
            'code' => 'KL-TK',
            'price' => 100000,
            'aplicare_code' => null,
            'sirs_code' => null,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.aplicare_code', null)
            ->assertJsonPath('data.sirs_code', null);

        $this->assertDatabaseHas('room_classes', [
            'code' => 'KL-TK',
            'aplicare_code' => null,
            'sirs_code' => null,
        ]);
    }

    public function test_can_update_room_class(): void
    {
        $roomClass = RoomClass::factory()->create([
            'code' => 'KL-LAMA',
            'name' => 'Kelas Lama',
            'price' => 100000,
            'aplicare_code' => '01',
            'sirs_code' => '1',
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/room-classes/{$roomClass->id}", [
            'code' => 'KL-BARU',
            'name' => 'Kelas Diperbarui',
            'price' => 250000,
            'description' => 'Deskripsi update',
            'aplicare_code' => '02',
            'sirs_code' => '2',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.code', 'KL-BARU')
            ->assertJsonPath('data.name', 'Kelas Diperbarui')
            ->assertJsonPath('data.price', 250000)
            ->assertJsonPath('data.formatted_price', 'Rp250.000')
            ->assertJsonPath('data.description', 'Deskripsi update')
            ->assertJsonPath('data.aplicare_code', '02')
            ->assertJsonPath('data.sirs_code', '2');

        $this->assertDatabaseHas('room_classes', [
            'id' => $roomClass->id,
            'code' => 'KL-BARU',
            'name' => 'Kelas Diperbarui',
            'price' => 250000.00,
            'aplicare_code' => '02',
            'sirs_code' => '2',
        ]);
    }

    public function test_update_room_class_unique_validation_ignores_self(): void
    {
        $roomClass = RoomClass::factory()->create([
            'code' => 'VIP',
            'name' => 'VIP',
            'price' => 500000,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/room-classes/{$roomClass->id}", [
            'code' => 'VIP',
            'name' => 'VIP',
            'price' => 550000,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.price', 550000);
    }

    public function test_cannot_delete_room_class_used_by_room(): void
    {
        $roomClass = RoomClass::factory()->create();
        Room::factory()->create(['room_class_id' => $roomClass->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/room-classes/{$roomClass->id}");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Data kelas tidak dapat dihapus karena masih digunakan oleh data kamar.');

        $this->assertDatabaseHas('room_classes', ['id' => $roomClass->id, 'deleted_at' => null]);
    }

    public function test_can_delete_unused_room_class(): void
    {
        $roomClass = RoomClass::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/room-classes/{$roomClass->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('room_classes', ['id' => $roomClass->id]);
    }

    public function test_soft_deleted_room_class_does_not_appear_in_index(): void
    {
        $active = RoomClass::factory()->create(['name' => 'Kelas Aktif']);
        $deleted = RoomClass::factory()->create(['name' => 'Kelas Dihapus']);
        $deleted->delete();

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/room-classes');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertContains('Kelas Aktif', $names);
        $this->assertNotContains('Kelas Dihapus', $names);
    }

    public function test_can_get_room_class_options(): void
    {
        RoomClass::factory()->create(['name' => 'VIP', 'code' => 'VIP', 'price' => 500000]);
        RoomClass::factory()->create(['name' => 'Kelas 1', 'code' => 'KL1', 'price' => 100000]);
        $deleted = RoomClass::factory()->create(['name' => 'Kelas Lama']);
        $deleted->delete();

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/room-classes/options');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'name', 'price', 'formatted_price'],
                ],
            ]);

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertContains('Kelas 1', $names);
        $this->assertContains('VIP', $names);
        $this->assertNotContains('Kelas Lama', $names);
    }

    public function test_search_can_find_room_class_by_name_code_or_external_codes(): void
    {
        $class = RoomClass::factory()->create([
            'name' => 'Super Executive Room',
            'code' => 'KLS-EXEC',
            'price' => 1200000,
            'aplicare_code' => 'APL-99',
            'sirs_code' => 'SRS-88',
        ]);

        // Search by name
        $resName = $this->actingAs($this->user)->getJson('/api/v1/master-data/room-classes?search=Executive');
        $resName->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals($class->id, $resName->json('data.0.id'));

        // Search by code
        $resCode = $this->actingAs($this->user)->getJson('/api/v1/master-data/room-classes?search=KLS-EXEC');
        $resCode->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals($class->id, $resCode->json('data.0.id'));

        // Search by aplicare_code
        $resApl = $this->actingAs($this->user)->getJson('/api/v1/master-data/room-classes?search=APL-99');
        $resApl->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals($class->id, $resApl->json('data.0.id'));

        // Search by sirs_code
        $resSirs = $this->actingAs($this->user)->getJson('/api/v1/master-data/room-classes?search=SRS-88');
        $resSirs->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals($class->id, $resSirs->json('data.0.id'));
    }
}
