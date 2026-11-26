<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Province;
use App\Models\Regency;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProvinceControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        DB::purge('legacy');
        LegacyAccess::clearCache();

        $this->user = User::factory()->create();
    }

    public function test_internal_provinces_table_does_not_have_deleted_at_column(): void
    {
        $this->assertTrue(Schema::hasTable('provinces'));
        $this->assertFalse(Schema::hasColumn('provinces', 'deleted_at'));
    }

    public function test_can_create_province_with_unique_name(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/provinces', [
            'name' => 'JAWA BARAT',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'JAWA BARAT');

        $this->assertDatabaseHas('provinces', [
            'name' => 'JAWA BARAT',
        ]);
    }

    public function test_cannot_create_province_with_duplicate_name(): void
    {
        Province::factory()->create(['name' => 'JAWA TIMUR']);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/provinces', [
            'name' => '  JAWA   TIMUR  ',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_province_keeping_own_name(): void
    {
        $province = Province::factory()->create(['name' => 'JAWA TENGAH']);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/provinces/{$province->id}", [
            'name' => 'JAWA TENGAH',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $province->id)
            ->assertJsonPath('data.name', 'JAWA TENGAH');
    }

    public function test_cannot_update_province_with_another_provinces_name(): void
    {
        Province::factory()->create(['name' => 'BANTEN']);
        $province = Province::factory()->create(['name' => 'BALI']);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/provinces/{$province->id}", [
            'name' => 'BANTEN',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseHas('provinces', [
            'id' => $province->id,
            'name' => 'BALI',
        ]);
    }

    public function test_can_update_province_with_new_name(): void
    {
        $province = Province::factory()->create(['name' => 'PROVINSI LAMA']);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/provinces/{$province->id}", [
            'name' => 'PROVINSI BARU',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'PROVINSI BARU');

        $this->assertDatabaseHas('provinces', [
            'id' => $province->id,
            'name' => 'PROVINSI BARU',
        ]);
    }

    public function test_create_province_requires_name(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/provinces', [
            'name' => '   ',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_and_update_province_validate_name_max_length(): void
    {
        $tooLongName = str_repeat('A', 256);

        $createResponse = $this->actingAs($this->user)->postJson('/api/v1/master-data/provinces', [
            'name' => $tooLongName,
        ]);
        $createResponse->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $province = Province::factory()->create(['name' => 'SUMATERA BARAT']);

        $updateResponse = $this->actingAs($this->user)->putJson("/api/v1/master-data/provinces/{$province->id}", [
            'name' => $tooLongName,
        ]);
        $updateResponse->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_internal_province_preserves_regency_relation_lookup_and_delete_protection(): void
    {
        $province = Province::factory()->create(['name' => 'DI YOGYAKARTA']);
        $regency = Regency::factory()->create([
            'province_id' => $province->id,
            'name' => 'KABUPATEN SLEMAN',
        ]);

        // Lookup provinsi mengembalikan data internal
        $this->actingAs($this->user)
            ->getJson('/api/v1/lookups/provinces?search=YOGYAKARTA')
            ->assertOk()
            ->assertJsonFragment(['id' => $province->id, 'name' => 'DI YOGYAKARTA']);

        // Update nama tetap mempertahankan ID dan relasi ke Regency
        $this->actingAs($this->user)
            ->putJson("/api/v1/master-data/provinces/{$province->id}", [
                'name' => 'DAERAH ISTIMEWA YOGYAKARTA',
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $province->id);

        $this->assertDatabaseHas('regencies', [
            'id' => $regency->id,
            'province_id' => $province->id,
        ]);

        // Provinsi yang masih dipakai Regency dilindungi dengan HTTP 409
        $this->actingAs($this->user)
            ->deleteJson("/api/v1/master-data/provinces/{$province->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Provinsi masih digunakan dan tidak dapat dihapus.');

        $regency->delete();

        // Setelah Regency dihapus, Provinsi dapat dihapus
        $this->actingAs($this->user)
            ->deleteJson("/api/v1/master-data/provinces/{$province->id}")
            ->assertNoContent();
    }

    public function test_internal_index_search_and_unsupported_filters_do_not_crash(): void
    {
        $jabar = Province::factory()->create(['name' => 'JAWA BARAT', 'legacy_id' => '32']);
        Province::factory()->create(['name' => 'JAWA TIMUR', 'legacy_id' => '35']);

        // 1. List tanpa filter
        $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/provinces')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        // 2. Search berdasarkan nama pada mode internal tidak boleh query kolom code
        $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/provinces?search=BARAT')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $jabar->id)
            ->assertJsonPath('data.0.legacy_id', '32')
            ->assertJsonPath('data.0.code', null)
            ->assertJsonPath('data.0.is_active', null);

        // 3. Filter tidak didukung pada mode internal (is_active, code, sort=code) ditolak jelas dengan 422, bukan 500
        $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/provinces?is_active=1')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['is_active']);

        $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/provinces?code=32')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/provinces?sort=code')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sort']);
    }

    public function test_internal_store_and_update_reject_unsupported_code_and_is_active_fields_with_422(): void
    {
        $this->actingAs($this->user)->postJson('/api/v1/master-data/provinces', [
            'name' => 'SULAWESI SELATAN',
            'code' => '73',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $this->actingAs($this->user)->postJson('/api/v1/master-data/provinces', [
            'name' => 'SULAWESI SELATAN',
            'is_active' => false,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['is_active']);

        $province = Province::factory()->create(['name' => 'SULAWESI UTARA', 'legacy_id' => '71']);

        $this->actingAs($this->user)->putJson("/api/v1/master-data/provinces/{$province->id}", [
            'name' => 'SULAWESI UTARA',
            'code' => '71',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $this->actingAs($this->user)->putJson("/api/v1/master-data/provinces/{$province->id}", [
            'name' => 'SULAWESI UTARA',
            'is_active' => false,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['is_active']);

        // Nilai null pada code/is_active tetap kompatibel (tidak memutus payload null opsional) dan name/legacy_id tetap terjaga
        $this->actingAs($this->user)->putJson("/api/v1/master-data/provinces/{$province->id}", [
            'name' => 'SULAWESI UTARA REVISI',
            'code' => null,
            'is_active' => null,
        ])->assertOk()
            ->assertJsonPath('data.name', 'SULAWESI UTARA REVISI')
            ->assertJsonPath('data.legacy_id', '71');
    }

    public function test_internal_show_returns_clean_resource_shape(): void
    {
        $province = Province::factory()->create(['name' => 'KALIMANTAN TIMUR', 'legacy_id' => '64']);

        $this->actingAs($this->user)
            ->getJson("/api/v1/master-data/provinces/{$province->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $province->id)
            ->assertJsonPath('data.legacy_id', '64')
            ->assertJsonPath('data.name', 'KALIMANTAN TIMUR')
            ->assertJsonPath('data.code', null)
            ->assertJsonPath('data.is_active', null);
    }
}
