<?php

namespace Tests\Feature\MasterData;

use App\Models\MasterData\FollowUpHandling;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FollowUpHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }
    
    public function test_guest_cannot_access_follow_up_handlings()
    {
        $response = $this->getJson('/api/v1/master-data/follow-up-handlings');
        $response->assertStatus(401);
    }

    public function test_can_list_follow_up_handlings()
    {
        FollowUpHandling::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/follow-up-handlings');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_search_follow_up_handlings()
    {
        FollowUpHandling::factory()->create(['name' => 'Sembuh']);
        FollowUpHandling::factory()->create(['name' => 'Meninggal']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/follow-up-handlings?search=semb');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Sembuh');
    }

    public function test_can_create_follow_up_handling()
    {
        $payload = [
            'name' => 'Rujuk Eksternal',
            'code' => 'RE',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/follow-up-handlings', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Rujuk Eksternal');

        $this->assertDatabaseHas('follow_up_handlings', [
            'name' => 'Rujuk Eksternal',
            'code' => 'RE',
        ]);
    }

    public function test_cannot_create_without_name()
    {
        $payload = [
            'code' => 'RE',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/follow-up-handlings', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_can_update_follow_up_handling()
    {
        $handling = FollowUpHandling::factory()->create([
            'name' => 'Sembuh',
        ]);

        $payload = [
            'name' => 'Sembuh Total',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/master-data/follow-up-handlings/{$handling->id}", $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('follow_up_handlings', [
            'id' => $handling->id,
            'name' => 'Sembuh Total',
        ]);
    }

    public function test_can_delete_follow_up_handling()
    {
        $handling = FollowUpHandling::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/master-data/follow-up-handlings/{$handling->id}");

        $response->assertStatus(204);
        
        $this->assertSoftDeleted('follow_up_handlings', [
            'id' => $handling->id,
        ]);
    }

    public function test_cannot_delete_when_used_in_transactions()
    {
        $handling = FollowUpHandling::factory()->create();

        // Create the dummy table and record to simulate trx_visit relation
        DB::statement('CREATE TABLE IF NOT EXISTS trx_visit (id_penanganan_lanjutan INTEGER)');
        DB::table('trx_visit')->insert(['id_penanganan_lanjutan' => $handling->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/master-data/follow-up-handlings/{$handling->id}");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Penanganan lanjutan tidak dapat dihapus karena sudah digunakan pada data pelayanan pasien.');
            
        $this->assertDatabaseHas('follow_up_handlings', [
            'id' => $handling->id,
            'deleted_at' => null, // Not deleted
        ]);
        
        // Clean up dummy
        DB::statement('DROP TABLE trx_visit');
    }
}
