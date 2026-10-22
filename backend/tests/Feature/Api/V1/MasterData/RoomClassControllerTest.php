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
    }

    public function test_can_get_room_class_options(): void
    {
        RoomClass::factory()->create(['name' => 'VIP', 'code' => 'VIP']);
        RoomClass::factory()->create(['name' => 'Kelas 1', 'code' => 'KL1']);
        $deleted = RoomClass::factory()->create(['name' => 'Kelas Lama']);
        $deleted->delete();

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/room-classes/options');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'name', 'description'],
                ],
            ]);

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertContains('Kelas 1', $names);
        $this->assertContains('VIP', $names);
        $this->assertNotContains('Kelas Lama', $names);
    }

    public function test_options_endpoint_supports_search(): void
    {
        RoomClass::factory()->create(['name' => 'Kelas Melati', 'code' => 'KLS-MLT']);
        RoomClass::factory()->create(['name' => 'Kelas Mawar', 'code' => 'KLS-MWR']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/room-classes/options?search=Melati');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Kelas Melati');
    }

    public function test_can_create_room_class_with_auto_generated_code(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/room-classes', [
            'name' => 'Kelas Eksekutif',
            'description' => 'Kelas kamar eksekutif',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Kelas Eksekutif')
            ->assertJsonPath('data.description', 'Kelas kamar eksekutif');

        $this->assertNotNull($response->json('data.code'));
        $this->assertDatabaseHas('room_classes', [
            'name' => 'Kelas Eksekutif',
        ]);
    }

    public function test_create_room_class_validates_required_name(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/room-classes', [
            'name' => '',
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
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJsonPath('errors.name.0', 'Nama kelas sudah digunakan.');
    }

    public function test_cannot_delete_room_class_used_by_room(): void
    {
        $roomClass = RoomClass::factory()->create();
        Room::factory()->create(['room_class_id' => $roomClass->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/room-classes/{$roomClass->id}");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Kelas tidak dapat dihapus karena masih digunakan oleh data kamar.');

        $this->assertDatabaseHas('room_classes', ['id' => $roomClass->id, 'deleted_at' => null]);
    }

    public function test_can_delete_unused_room_class(): void
    {
        $roomClass = RoomClass::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/room-classes/{$roomClass->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('room_classes', ['id' => $roomClass->id]);
    }
}
