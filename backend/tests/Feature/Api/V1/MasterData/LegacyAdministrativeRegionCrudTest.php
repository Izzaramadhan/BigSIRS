<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacyAdministrativeRegionCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $authUser;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.connections.legacy', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        LegacyAccess::clearCache();

        DB::connection('legacy')->statement('
            CREATE TABLE ref_provinsi (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(255) NULL,
                kode_provinsi VARCHAR(50) NULL,
                status INTEGER DEFAULT 1,
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_kabupaten (
                id VARCHAR(10) PRIMARY KEY,
                id_provinsi VARCHAR(10) NULL,
                nama VARCHAR(255) NULL,
                kode_kabupaten VARCHAR(50) NULL,
                status INTEGER DEFAULT 1,
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_kecamatan (
                id VARCHAR(15) PRIMARY KEY,
                id_kabupaten VARCHAR(10) NULL,
                nama VARCHAR(255) NULL,
                kode_kecamatan VARCHAR(50) NULL,
                status INTEGER DEFAULT 1,
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_kelurahan (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kode_kelurahan VARCHAR(50) NULL,
                id_kecamatan VARCHAR(15) NULL,
                nama VARCHAR(255) NULL,
                status INTEGER DEFAULT 1,
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE master_person_index (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_provinsi VARCHAR(10) NULL,
                id_kabupaten VARCHAR(10) NULL,
                id_kecamatan VARCHAR(15) NULL,
                id_kelurahan INTEGER NULL
            )
        ');

        $this->authUser = User::factory()->create();
        Sanctum::actingAs($this->authUser);
    }

    /* -------------------------------------------------------------
     * 1. PROVINCE / PROVINSI (ref_provinsi)
     * ------------------------------------------------------------- */
    public function test_province_full_legacy_crud_lifecycle(): void
    {
        // 1. Create Province
        $res = $this->postJson('/api/v1/master-data/provinces', [
            'name' => 'DAERAH KHUSUS JAKARTA',
            'code' => '31',
            'is_active' => true,
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.name', 'DAERAH KHUSUS JAKARTA');

        $id = $res->json('data.id');
        $this->assertDatabaseHas('ref_provinsi', [
            'id' => $id,
            'nama' => 'DAERAH KHUSUS JAKARTA',
            'kode_provinsi' => '31',
            'status' => 1,
        ], 'legacy');

        // 2. Index with search
        $listRes = $this->getJson('/api/v1/master-data/provinces?search=JAKARTA');
        $listRes->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id);

        // 3. Show
        $showRes = $this->getJson("/api/v1/master-data/provinces/{$id}");
        $showRes->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', 'DAERAH KHUSUS JAKARTA');

        // 4. Update
        $updRes = $this->putJson("/api/v1/master-data/provinces/{$id}", [
            'name' => 'DKI JAKARTA RAYA',
            'code' => '31-REV',
            'is_active' => true,
        ]);
        $updRes->assertOk()
            ->assertJsonPath('data.name', 'DKI JAKARTA RAYA');

        $this->assertDatabaseHas('ref_provinsi', [
            'id' => $id,
            'nama' => 'DKI JAKARTA RAYA',
            'kode_provinsi' => '31-REV',
        ], 'legacy');

        // 5. Delete prevention when used by Regency
        DB::connection('legacy')->table('ref_kabupaten')->insert([
            'id' => '3171',
            'id_provinsi' => (string) $id,
            'nama' => 'JAKARTA SELATAN',
            'status' => 1,
        ]);

        $delBlocked = $this->deleteJson("/api/v1/master-data/provinces/{$id}");
        $delBlocked->assertStatus(409)
            ->assertJsonPath('message', 'Provinsi masih digunakan dan tidak dapat dihapus.');

        // Clean up & delete
        DB::connection('legacy')->table('ref_kabupaten')->truncate();

        $delRes = $this->deleteJson("/api/v1/master-data/provinces/{$id}");
        $delRes->assertNoContent();

        $this->assertSoftDeleted('ref_provinsi', ['id' => $id], 'legacy');
    }

    /* -------------------------------------------------------------
     * 2. REGENCY / KABUPATEN (ref_kabupaten)
     * ------------------------------------------------------------- */
    public function test_regency_full_legacy_crud_lifecycle(): void
    {
        // Seed Province
        DB::connection('legacy')->table('ref_provinsi')->insert([
            'id' => 31,
            'nama' => 'DKI JAKARTA',
            'status' => 1,
        ]);

        // 1. Create Regency
        $res = $this->postJson('/api/v1/master-data/regencies', [
            'province_id' => '31',
            'name' => 'KOTA JAKARTA PUSAT',
            'code' => '3171',
            'is_active' => true,
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.name', 'KOTA JAKARTA PUSAT');

        $id = $res->json('data.id');
        $this->assertDatabaseHas('ref_kabupaten', [
            'id' => $id,
            'nama' => 'KOTA JAKARTA PUSAT',
            'id_provinsi' => '31',
            'status' => 1,
        ], 'legacy');

        // 2. Index
        $listRes = $this->getJson('/api/v1/master-data/regencies?search=PUSAT');
        $listRes->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id);

        // 3. Show
        $showRes = $this->getJson("/api/v1/master-data/regencies/{$id}");
        $showRes->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', 'KOTA JAKARTA PUSAT');

        // 4. Update
        $updRes = $this->putJson("/api/v1/master-data/regencies/{$id}", [
            'province_id' => '31',
            'name' => 'KOTA ADM. JAKARTA PUSAT',
            'code' => '3171',
            'is_active' => true,
        ]);
        $updRes->assertOk()
            ->assertJsonPath('data.name', 'KOTA ADM. JAKARTA PUSAT');

        $this->assertDatabaseHas('ref_kabupaten', [
            'id' => $id,
            'nama' => 'KOTA ADM. JAKARTA PUSAT',
        ], 'legacy');

        // 5. Delete prevention when used by District
        DB::connection('legacy')->table('ref_kecamatan')->insert([
            'id' => '3171010',
            'id_kabupaten' => (string) $id,
            'nama' => 'GAMBIR',
            'status' => 1,
        ]);

        $delBlocked = $this->deleteJson("/api/v1/master-data/regencies/{$id}");
        $delBlocked->assertStatus(409);

        // Clean up & delete
        DB::connection('legacy')->table('ref_kecamatan')->truncate();

        $delRes = $this->deleteJson("/api/v1/master-data/regencies/{$id}");
        $delRes->assertNoContent();

        $this->assertSoftDeleted('ref_kabupaten', ['id' => $id], 'legacy');
    }

    /* -------------------------------------------------------------
     * 3. DISTRICT / KECAMATAN (ref_kecamatan)
     * ------------------------------------------------------------- */
    public function test_district_full_legacy_crud_lifecycle(): void
    {
        // Seed Regency
        DB::connection('legacy')->table('ref_kabupaten')->insert([
            'id' => '3171',
            'id_provinsi' => '31',
            'nama' => 'KOTA JAKARTA PUSAT',
            'status' => 1,
        ]);

        // 1. Create District
        $res = $this->postJson('/api/v1/master-data/districts', [
            'regency_id' => '3171',
            'name' => 'GAMBIR',
            'code' => '3171010',
            'is_active' => true,
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.name', 'GAMBIR');

        $id = $res->json('data.id');
        $this->assertDatabaseHas('ref_kecamatan', [
            'id' => $id,
            'nama' => 'GAMBIR',
            'id_kabupaten' => '3171',
            'status' => 1,
        ], 'legacy');

        // 2. Index
        $listRes = $this->getJson('/api/v1/master-data/districts?search=GAMBIR');
        $listRes->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id);

        // 3. Show
        $showRes = $this->getJson("/api/v1/master-data/districts/{$id}");
        $showRes->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', 'GAMBIR');

        // 4. Update
        $updRes = $this->putJson("/api/v1/master-data/districts/{$id}", [
            'regency_id' => '3171',
            'name' => 'KECAMATAN GAMBIR UTAMA',
            'code' => '3171010',
            'is_active' => true,
        ]);
        $updRes->assertOk()
            ->assertJsonPath('data.name', 'KECAMATAN GAMBIR UTAMA');

        $this->assertDatabaseHas('ref_kecamatan', [
            'id' => $id,
            'nama' => 'KECAMATAN GAMBIR UTAMA',
        ], 'legacy');

        // 5. Delete prevention when used by Village
        DB::connection('legacy')->table('ref_kelurahan')->insert([
            'id_kecamatan' => (string) $id,
            'nama' => 'PETOJO UTARA',
            'status' => 1,
        ]);

        $delBlocked = $this->deleteJson("/api/v1/master-data/districts/{$id}");
        $delBlocked->assertStatus(409);

        // Clean up & delete
        DB::connection('legacy')->table('ref_kelurahan')->truncate();

        $delRes = $this->deleteJson("/api/v1/master-data/districts/{$id}");
        $delRes->assertNoContent();

        $this->assertSoftDeleted('ref_kecamatan', ['id' => $id], 'legacy');
    }

    /* -------------------------------------------------------------
     * 4. VILLAGE / KELURAHAN (ref_kelurahan)
     * ------------------------------------------------------------- */
    public function test_village_full_legacy_crud_lifecycle(): void
    {
        // Seed Regency & District
        DB::connection('legacy')->table('ref_kabupaten')->insert([
            'id' => '3171',
            'id_provinsi' => '31',
            'nama' => 'KOTA JAKARTA PUSAT',
            'status' => 1,
        ]);
        DB::connection('legacy')->table('ref_kecamatan')->insert([
            'id' => '3171010',
            'id_kabupaten' => '3171',
            'nama' => 'GAMBIR',
            'status' => 1,
        ]);

        // 1. Create Village
        $res = $this->postJson('/api/v1/master-data/villages', [
            'district_id' => '3171010',
            'name' => 'PETOJO SELATAN',
            'code' => '3171010001',
            'is_active' => true,
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.name', 'PETOJO SELATAN');

        $id = $res->json('data.id');
        $this->assertDatabaseHas('ref_kelurahan', [
            'id' => $id,
            'nama' => 'PETOJO SELATAN',
            'id_kecamatan' => '3171010',
            'status' => 1,
        ], 'legacy');

        // Prevent duplicate name in same district
        $dupRes = $this->postJson('/api/v1/master-data/villages', [
            'district_id' => '3171010',
            'name' => 'PETOJO SELATAN',
        ]);
        $dupRes->assertStatus(422);

        // 2. Index
        $listRes = $this->getJson('/api/v1/master-data/villages?search=PETOJO');
        $listRes->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id);

        // 3. Show
        $showRes = $this->getJson("/api/v1/master-data/villages/{$id}");
        $showRes->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', 'PETOJO SELATAN');

        // 4. Update
        $updRes = $this->putJson("/api/v1/master-data/villages/{$id}", [
            'district_id' => '3171010',
            'name' => 'PETOJO SELATAN RAYA',
            'code' => '3171010001',
            'is_active' => true,
        ]);
        $updRes->assertOk()
            ->assertJsonPath('data.name', 'PETOJO SELATAN RAYA');

        $this->assertDatabaseHas('ref_kelurahan', [
            'id' => $id,
            'nama' => 'PETOJO SELATAN RAYA',
        ], 'legacy');

        // 5. Delete prevention when used by MPI
        DB::connection('legacy')->table('master_person_index')->insert([
            'id_kelurahan' => $id,
        ]);

        $delBlocked = $this->deleteJson("/api/v1/master-data/villages/{$id}");
        $delBlocked->assertStatus(409);

        // Clean up & delete
        DB::connection('legacy')->table('master_person_index')->truncate();

        $delRes = $this->deleteJson("/api/v1/master-data/villages/{$id}");
        $delRes->assertNoContent();

        $this->assertDatabaseHas('ref_kelurahan', [
            'id' => $id,
            'status' => 0,
        ], 'legacy');
    }
}
