<?php

namespace Tests\Feature\MasterData;

use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTypeTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_service_types()
    {
        ServiceType::create(['name' => 'Layanan A', 'is_active' => true]);
        ServiceType::create(['name' => 'Layanan B', 'is_active' => false]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/master-data/service-types');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    public function test_can_create_service_type()
    {
        $payload = [
            'name' => 'Layanan Baru',
            'is_active' => true
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/master-data/service-types', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('data.name', 'Layanan Baru');

        $this->assertDatabaseHas('service_types', [
            'name' => 'Layanan Baru'
        ]);
    }

    public function test_cannot_create_duplicate_service_type()
    {
        ServiceType::create(['name' => 'Layanan Duplikat', 'is_active' => true]);

        $payload = [
            'name' => 'Layanan Duplikat',
            'is_active' => true
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/master-data/service-types', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('name');
    }

    public function test_can_update_service_type()
    {
        $serviceType = ServiceType::create(['name' => 'Layanan Lama', 'is_active' => true]);

        $payload = [
            'name' => 'Layanan Diperbarui',
            'is_active' => false
        ];

        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/v1/master-data/service-types/{$serviceType->id}", $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('data.name', 'Layanan Diperbarui')
                 ->assertJsonPath('data.is_active', false);
    }

    public function test_can_update_service_type_status()
    {
        $serviceType = ServiceType::create(['name' => 'Layanan Aktif', 'is_active' => true]);

        $response = $this->actingAs($this->user, 'sanctum')->patchJson("/api/v1/master-data/service-types/{$serviceType->id}/status", [
            'is_active' => false
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('service_types', [
            'id' => $serviceType->id,
            'is_active' => false
        ]);
    }

    public function test_can_delete_service_type()
    {
        $serviceType = ServiceType::create(['name' => 'Layanan Hapus', 'is_active' => true]);

        $response = $this->actingAs($this->user, 'sanctum')->deleteJson("/api/v1/master-data/service-types/{$serviceType->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('service_types', ['id' => $serviceType->id]);
    }
}
