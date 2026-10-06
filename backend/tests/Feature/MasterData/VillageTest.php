<?php

namespace Tests\Feature\MasterData;

use App\Models\District;
use App\Models\Regency;
use App\Models\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class VillageTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_villages()
    {
        $regency = Regency::factory()->create();
        $district = District::factory()->create(['regency_id' => $regency->id]);
        Village::factory()->count(5)->create(['district_id' => $district->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/master-data/villages');

        $response->assertStatus(200)
                 ->assertJsonCount(5, 'data');
    }

    public function test_can_create_village_with_valid_hierarchy()
    {
        $regency = Regency::factory()->create();
        $district = District::factory()->create(['regency_id' => $regency->id]);

        $payload = [
            'code' => 'VILL01',
            'name' => 'Desa Makmur',
            'district_id' => $district->id,
            'is_active' => true
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/master-data/villages', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('data.name', 'Desa Makmur');

        $this->assertDatabaseHas('villages', [
            'code' => 'VILL01',
            'name' => 'Desa Makmur',
            'district_id' => $district->id
        ]);
    }

    public function test_cannot_create_village_without_district()
    {
        $payload = [
            'code' => 'VILL02',
            'name' => 'Desa Invalid',
            'district_id' => null
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/master-data/villages', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('district_id');
    }

    public function test_can_update_village()
    {
        $regency = Regency::factory()->create();
        $district = District::factory()->create(['regency_id' => $regency->id]);
        $village = Village::factory()->create(['district_id' => $district->id]);

        $payload = [
            'name' => 'Desa Baru',
            'district_id' => $district->id,
            'is_active' => false
        ];

        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/v1/master-data/villages/{$village->id}", $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('data.name', 'Desa Baru')
                 ->assertJsonPath('data.is_active', false);
    }

    public function test_can_delete_village()
    {
        $regency = Regency::factory()->create();
        $district = District::factory()->create(['regency_id' => $regency->id]);
        $village = Village::factory()->create(['district_id' => $district->id]);

        $response = $this->actingAs($this->user, 'sanctum')->deleteJson("/api/v1/master-data/villages/{$village->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('villages', ['id' => $village->id]);
    }

    public function test_can_fetch_district_options_filtered_by_regency()
    {
        $regency1 = Regency::factory()->create();
        $regency2 = Regency::factory()->create();
        
        $district1 = District::factory()->create(['regency_id' => $regency1->id, 'is_active' => true]);
        $district2 = District::factory()->create(['regency_id' => $regency2->id, 'is_active' => true]);
        District::factory()->create(['regency_id' => $regency1->id, 'is_active' => false]); // inactive

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/v1/lookups/districts?regency_id={$regency1->id}");

        $response->assertStatus(200)
                 ->assertJsonCount(1) // only 1 active district for regency 1
                 ->assertJsonPath('0.id', $district1->id)
                 ->assertJsonPath('0.regency_id', $regency1->id);

        $response2 = $this->actingAs($this->user, 'sanctum')->getJson("/api/v1/lookups/districts?regency_id={$regency2->id}");
        
        $response2->assertStatus(200)
                 ->assertJsonCount(1)
                 ->assertJsonPath('0.id', $district2->id);
    }
}
