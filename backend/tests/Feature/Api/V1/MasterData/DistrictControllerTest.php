<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Regency;
use App\Models\District;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistrictControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (config('database.default') !== 'sqlite') {
            $this->markTestSkipped('Database tests can only be run on SQLite in-memory database.');
        }

        $this->actingAs(User::factory()->create());
    }

    public function test_can_list_districts()
    {
        District::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/master-data/districts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'regency_id', 'regency_name', 'code', 'name', 'is_active', 'created_at', 'updated_at'
                    ]
                ],
                'meta',
                'links'
            ]);
    }

    public function test_can_search_districts()
    {
        $district = District::factory()->create(['name' => 'KECAMATAN ABCDE']);
        District::factory()->create(['name' => 'KECAMATAN XYZ']);

        $response = $this->getJson('/api/v1/master-data/districts?search=ABCDE');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'KECAMATAN ABCDE');
    }

    public function test_can_filter_by_regency()
    {
        $regency1 = Regency::factory()->create();
        $regency2 = Regency::factory()->create();

        District::factory()->create(['regency_id' => $regency1->id]);
        District::factory()->create(['regency_id' => $regency2->id]);
        District::factory()->create(['regency_id' => $regency2->id]);

        $response = $this->getJson("/api/v1/master-data/districts?regency_id={$regency2->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_create_district()
    {
        $regency = Regency::factory()->create();

        $payload = [
            'regency_id' => $regency->id,
            'code' => '1234567',
            'name' => 'KECAMATAN BARU',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/master-data/districts', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'KECAMATAN BARU')
            ->assertJsonPath('data.regency_id', $regency->id);

        $this->assertDatabaseHas('districts', [
            'code' => '1234567',
            'name' => 'KECAMATAN BARU',
            'regency_id' => $regency->id,
        ]);
    }

    public function test_cannot_create_with_duplicate_code()
    {
        $existing = District::factory()->create();
        $regency = Regency::factory()->create();

        $payload = [
            'regency_id' => $regency->id,
            'code' => $existing->code,
            'name' => 'KECAMATAN LAIN',
        ];

        $response = $this->postJson('/api/v1/master-data/districts', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_cannot_create_with_duplicate_name_in_same_regency()
    {
        $existing = District::factory()->create();

        $payload = [
            'regency_id' => $existing->regency_id,
            'code' => '9999999',
            'name' => $existing->name,
        ];

        $response = $this->postJson('/api/v1/master-data/districts', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_create_with_duplicate_name_in_different_regency()
    {
        $existing = District::factory()->create();
        $regency = Regency::factory()->create();

        $payload = [
            'regency_id' => $regency->id,
            'code' => '9999999',
            'name' => $existing->name,
        ];

        $response = $this->postJson('/api/v1/master-data/districts', $payload);

        $response->assertStatus(201);
    }

    public function test_can_update_district()
    {
        $district = District::factory()->create();
        $newRegency = Regency::factory()->create();

        $payload = [
            'regency_id' => $newRegency->id,
            'name' => 'KECAMATAN UPDATE',
            'code' => '7654321',
        ];

        $response = $this->putJson("/api/v1/master-data/districts/{$district->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'KECAMATAN UPDATE');

        $this->assertDatabaseHas('districts', [
            'id' => $district->id,
            'name' => 'KECAMATAN UPDATE',
            'regency_id' => $newRegency->id,
            'code' => '7654321',
        ]);
    }

    public function test_can_delete_district()
    {
        $district = District::factory()->create();

        $response = $this->deleteJson("/api/v1/master-data/districts/{$district->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('districts', [
            'id' => $district->id,
        ]);
    }
}
