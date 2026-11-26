<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Employee;
use App\Models\Province;
use App\Models\Regency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegencyControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Province $province;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->province = Province::factory()->create();
    }

    public function test_can_list_regencies()
    {
        Regency::factory()->count(3)->create(['province_id' => $this->province->id]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/regencies');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'name', 'province_id', 'province_name', 'is_active'],
                ],
            ]);
    }

    public function test_can_search_regencies()
    {
        Regency::factory()->create(['code' => '1234', 'name' => 'KABUPATEN SLEMAN']);
        Regency::factory()->create(['code' => '5678', 'name' => 'KOTA BANTUL']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/regencies?search=SLEMAN');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'KABUPATEN SLEMAN');
    }

    public function test_can_filter_by_province()
    {
        $province2 = Province::factory()->create();
        Regency::factory()->create(['province_id' => $this->province->id]);
        Regency::factory()->create(['province_id' => $province2->id]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/regencies?province_id='.$this->province->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.province_id', $this->province->id);
    }

    public function test_can_create_regency()
    {
        $payload = [
            'code' => '0012',
            'name' => 'KABUPATEN BARU',
            'province_id' => $this->province->id,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/regencies', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', '0012')
            ->assertJsonPath('data.name', 'KABUPATEN BARU');

        $this->assertDatabaseHas('regencies', ['code' => '0012', 'name' => 'KABUPATEN BARU']);
    }

    public function test_cannot_create_with_duplicate_code()
    {
        Regency::factory()->create(['code' => '0012']);

        $payload = [
            'code' => '0012',
            'name' => 'KABUPATEN LAIN',
            'province_id' => $this->province->id,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/regencies', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_can_update_regency()
    {
        $regency = Regency::factory()->create([
            'code' => '0012',
            'name' => 'KABUPATEN LAMA',
            'province_id' => $this->province->id,
        ]);

        $payload = [
            'code' => '0012', // same code
            'name' => 'KABUPATEN UPDATE',
            'province_id' => $this->province->id,
            'is_active' => false,
        ];

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/regencies/{$regency->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'KABUPATEN UPDATE');

        $this->assertDatabaseHas('regencies', ['id' => $regency->id, 'name' => 'KABUPATEN UPDATE', 'is_active' => false]);
    }

    public function test_can_delete_regency()
    {
        $regency = Regency::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/regencies/{$regency->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('regencies', ['id' => $regency->id]);
    }

    public function test_cannot_delete_regency_with_districts()
    {
        $regency = Regency::factory()->create();
        \DB::table('districts')->insert([
            'name' => 'Test District',
            'regency_id' => $regency->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/regencies/{$regency->id}");

        $response->assertStatus(409);
        $this->assertNotSoftDeleted('regencies', ['id' => $regency->id]);
    }

    public function test_cannot_delete_regency_used_in_employee()
    {
        $regency = Regency::factory()->create();
        // Since employee factory might be complex, just manually insert to bypass factory complexity for this test.
        \DB::table('employees')->insert([
            'national_id' => '1234567890123456',
            'name' => 'Test Employee',
            'regency_id' => $regency->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/regencies/{$regency->id}");

        $response->assertStatus(409);
        $this->assertNotSoftDeleted('regencies', ['id' => $regency->id]);
    }

    public function test_create_and_update_regency_fails_422_when_province_id_does_not_exist()
    {
        $createRes = $this->actingAs($this->user)->postJson('/api/v1/master-data/regencies', [
            'code' => '9999',
            'name' => 'KABUPATEN TIDAK VALID',
            'province_id' => 999999,
            'is_active' => true,
        ]);

        $createRes->assertStatus(422)
            ->assertJsonValidationErrors(['province_id']);

        $regency = Regency::factory()->create([
            'code' => '0015',
            'name' => 'KABUPATEN ASLI',
            'province_id' => $this->province->id,
        ]);

        $updateRes = $this->actingAs($this->user)->putJson("/api/v1/master-data/regencies/{$regency->id}", [
            'code' => '0015',
            'name' => 'KABUPATEN GAGAL',
            'province_id' => 999999,
        ]);

        $updateRes->assertStatus(422)
            ->assertJsonValidationErrors(['province_id']);

        $this->assertDatabaseHas('regencies', [
            'id' => $regency->id,
            'name' => 'KABUPATEN ASLI',
            'province_id' => $this->province->id,
        ]);
    }

    public function test_create_and_update_regency_fails_422_when_province_id_is_empty()
    {
        $createRes = $this->actingAs($this->user)->postJson('/api/v1/master-data/regencies', [
            'code' => '0016',
            'name' => 'KABUPATEN TANPA PROVINSI',
            'province_id' => '',
        ]);

        $createRes->assertStatus(422)
            ->assertJsonValidationErrors(['province_id']);

        $regency = Regency::factory()->create([
            'province_id' => $this->province->id,
        ]);

        $updateRes = $this->actingAs($this->user)->putJson("/api/v1/master-data/regencies/{$regency->id}", [
            'code' => $regency->code,
            'name' => 'KABUPATEN EDIT TANPA PROVINSI',
            'province_id' => null,
        ]);

        $updateRes->assertStatus(422)
            ->assertJsonValidationErrors(['province_id']);
    }

    public function test_update_regency_moves_to_another_valid_province_and_preserves_province_data()
    {
        $originalName = $this->province->name;
        $province2 = Province::factory()->create(['name' => 'PROVINSI TUJUAN']);
        $regency = Regency::factory()->create([
            'code' => '0017',
            'name' => 'KABUPATEN PINDAH',
            'province_id' => $this->province->id,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/regencies/{$regency->id}", [
            'code' => '0017',
            'name' => 'KABUPATEN PINDAH REVISI',
            'province_id' => $province2->id,
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.province_id', $province2->id)
            ->assertJsonPath('data.province_name', 'PROVINSI TUJUAN');

        $this->assertDatabaseHas('regencies', [
            'id' => $regency->id,
            'name' => 'KABUPATEN PINDAH REVISI',
            'province_id' => $province2->id,
        ]);
        $this->assertDatabaseHas('provinces', [
            'id' => $this->province->id,
            'name' => $originalName,
        ]);
    }
}
