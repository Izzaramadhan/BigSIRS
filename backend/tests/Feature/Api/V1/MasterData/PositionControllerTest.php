<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Employee;
use App\Models\MasterData\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_positions()
    {
        Position::factory()->count(15)->create();

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/positions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name']
                ],
                'meta' => ['current_page', 'last_page', 'total']
            ]);
    }

    public function test_can_search_positions()
    {
        Position::factory()->create(['name' => 'Dokter Umum']);
        Position::factory()->create(['name' => 'Perawat']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/positions?search=Dokter');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Dokter Umum');
    }

    public function test_can_create_position()
    {
        $payload = [
            'name' => 'Direktur'
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/positions', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Direktur');

        $this->assertDatabaseHas('positions', ['name' => 'Direktur']);
    }

    public function test_cannot_create_duplicate_name()
    {
        Position::factory()->create(['name' => 'Dokter']);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/positions', [
            'name' => 'Dokter'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_position()
    {
        $position = Position::factory()->create(['name' => 'Dokter']);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/positions/{$position->id}", [
            'name' => 'Dokter Spesialis'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('positions', ['id' => $position->id, 'name' => 'Dokter Spesialis']);
    }


    public function test_can_delete_position()
    {
        $position = Position::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/positions/{$position->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('positions', ['id' => $position->id]);
    }

    public function test_cannot_delete_position_used_by_employee()
    {
        $position = Position::factory()->create();
        Employee::factory()->create(['position_id' => $position->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/positions/{$position->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('positions', ['id' => $position->id, 'deleted_at' => null]);
    }
}
