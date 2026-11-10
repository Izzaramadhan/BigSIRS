<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Enums\GuarantorType;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacyPatientServiceAndFlowCrudTest extends TestCase
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
            CREATE TABLE ref_jenis_asuransi (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kode VARCHAR(50) NULL,
                jenis_asuransi VARCHAR(150) NULL,
                pemerintah INTEGER DEFAULT 0,
                status INTEGER DEFAULT 1,
                id_user INTEGER NULL,
                type VARCHAR(20) DEFAULT "UMUM",
                id_inacbg VARCHAR(10) NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_penanganan_lanjutan (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kode VARCHAR(10) NULL,
                nama VARCHAR(250) NULL,
                status INTEGER DEFAULT 1,
                id_user INTEGER NULL,
                kode_inacbg INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_jenis_layanan (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(45) NULL,
                status VARCHAR(10) DEFAULT "1",
                id_user INTEGER NULL,
                tipe VARCHAR(10) NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_jenis_diet (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(50) NULL,
                deskripsi VARCHAR(255) NULL,
                status VARCHAR(10) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_jenis_surat (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(50) NULL,
                deskripsi VARCHAR(255) NULL,
                resource VARCHAR(255) NULL,
                status VARCHAR(10) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_jenis_kegiatan (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                parent_id INTEGER NULL,
                nama VARCHAR(100) NULL,
                status INTEGER DEFAULT 1,
                id_user INTEGER NULL,
                tipe VARCHAR(255) NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE trx_admisi (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_jenis_asuransi INTEGER NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE trx_visit (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_penanganan_lanjutan INTEGER NULL,
                id_jenis_layanan INTEGER NULL
            )
        ');

        $this->authUser = User::factory()->create();
        Sanctum::actingAs($this->authUser);
    }

    /* -------------------------------------------------------------
     * 1. GUARANTOR (ref_jenis_asuransi)
     * ------------------------------------------------------------- */
    public function test_guarantor_full_legacy_crud_lifecycle(): void
    {
        // 1. Create BPJS guarantor
        $postRes = $this->postJson('/api/v1/master-data/guarantors', [
            'code' => 'ASR-BPJS',
            'name' => 'BPJS Kesehatan Mandiri',
            'type' => GuarantorType::Bpjs->value,
            'is_government' => true,
            'is_active' => true,
            'inacbg_id' => '001',
        ]);

        $postRes->assertStatus(201)
            ->assertJsonPath('data.name', 'BPJS Kesehatan Mandiri')
            ->assertJsonPath('data.code', 'ASR-BPJS');

        $id = $postRes->json('data.id');
        $this->assertDatabaseHas('ref_jenis_asuransi', [
            'id' => $id,
            'jenis_asuransi' => 'BPJS Kesehatan Mandiri',
            'kode' => 'ASR-BPJS',
            'type' => 'BPJS',
            'pemerintah' => 1,
            'status' => 1,
        ], 'legacy');

        // 2. Index with search
        $listRes = $this->getJson('/api/v1/master-data/guarantors?search=Mandiri');
        $listRes->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id);

        // 3. Show
        $showRes = $this->getJson("/api/v1/master-data/guarantors/{$id}");
        $showRes->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', 'BPJS Kesehatan Mandiri');

        // 4. Update
        $putRes = $this->putJson("/api/v1/master-data/guarantors/{$id}", [
            'code' => 'ASR-BPJS-UPD',
            'name' => 'BPJS Kesehatan PBI',
            'type' => GuarantorType::Bpjs->value,
            'is_government' => true,
            'is_active' => true,
            'inacbg_id' => '002',
        ]);
        $putRes->assertOk()
            ->assertJsonPath('data.name', 'BPJS Kesehatan PBI')
            ->assertJsonPath('data.code', 'ASR-BPJS-UPD');

        $this->assertDatabaseHas('ref_jenis_asuransi', [
            'id' => $id,
            'jenis_asuransi' => 'BPJS Kesehatan PBI',
            'kode' => 'ASR-BPJS-UPD',
        ], 'legacy');

        // 5. Update status
        $statusRes = $this->patchJson("/api/v1/master-data/guarantors/{$id}/status", [
            'is_active' => false,
        ]);
        $statusRes->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('ref_jenis_asuransi', [
            'id' => $id,
            'status' => 0,
        ], 'legacy');

        // 6. Delete prevention when used in trx_admisi
        DB::connection('legacy')->table('trx_admisi')->insert([
            'id_jenis_asuransi' => $id,
        ]);

        $delBlockedRes = $this->deleteJson("/api/v1/master-data/guarantors/{$id}");
        $delBlockedRes->assertStatus(422)
            ->assertJsonPath('message', 'Penjamin tidak dapat dihapus karena sudah digunakan pada data admisi pasien.');

        // Clean up trx_admisi reference and delete successfully
        DB::connection('legacy')->table('trx_admisi')->truncate();

        $delRes = $this->deleteJson("/api/v1/master-data/guarantors/{$id}");
        $delRes->assertNoContent();

        $this->assertSoftDeleted('ref_jenis_asuransi', ['id' => $id], 'legacy');
    }

    /* -------------------------------------------------------------
     * 2. FOLLOW UP HANDLING (ref_penanganan_lanjutan)
     * ------------------------------------------------------------- */
    public function test_follow_up_handling_full_legacy_crud_lifecycle(): void
    {
        // 1. Create
        $postRes = $this->postJson('/api/v1/master-data/follow-up-handlings', [
            'code' => 'PULANG',
            'name' => 'Pulang Sembuh',
            'is_active' => true,
            'inacbg_code' => 1,
        ]);
        $postRes->assertStatus(201)
            ->assertJsonPath('data.name', 'Pulang Sembuh')
            ->assertJsonPath('data.code', 'PULANG');

        $id = $postRes->json('data.id');
        $this->assertDatabaseHas('ref_penanganan_lanjutan', [
            'id' => $id,
            'nama' => 'Pulang Sembuh',
            'kode' => 'PULANG',
            'status' => 1,
            'kode_inacbg' => 1,
        ], 'legacy');

        // 2. Index
        $listRes = $this->getJson('/api/v1/master-data/follow-up-handlings?search=Sembuh');
        $listRes->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id);

        // 3. Show
        $showRes = $this->getJson("/api/v1/master-data/follow-up-handlings/{$id}");
        $showRes->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', 'Pulang Sembuh');

        // 4. Update
        $putRes = $this->putJson("/api/v1/master-data/follow-up-handlings/{$id}", [
            'code' => 'RUJUK',
            'name' => 'Rujuk ke RS Vertikal',
            'is_active' => true,
            'inacbg_code' => 2,
        ]);
        $putRes->assertOk()
            ->assertJsonPath('data.name', 'Rujuk ke RS Vertikal')
            ->assertJsonPath('data.code', 'RUJUK');

        $this->assertDatabaseHas('ref_penanganan_lanjutan', [
            'id' => $id,
            'nama' => 'Rujuk ke RS Vertikal',
            'kode' => 'RUJUK',
        ], 'legacy');

        // 5. Delete prevention when used in trx_visit
        DB::connection('legacy')->table('trx_visit')->insert([
            'id_penanganan_lanjutan' => $id,
        ]);

        $delBlockedRes = $this->deleteJson("/api/v1/master-data/follow-up-handlings/{$id}");
        $delBlockedRes->assertStatus(422)
            ->assertJsonPath('message', 'Penanganan lanjutan tidak dapat dihapus karena sudah digunakan pada data pelayanan pasien.');

        // Clean up trx_visit and delete successfully
        DB::connection('legacy')->table('trx_visit')->truncate();

        $delRes = $this->deleteJson("/api/v1/master-data/follow-up-handlings/{$id}");
        $delRes->assertNoContent();

        $this->assertSoftDeleted('ref_penanganan_lanjutan', ['id' => $id], 'legacy');
    }

    /* -------------------------------------------------------------
     * 3. SERVICE TYPE (ref_jenis_layanan)
     * ------------------------------------------------------------- */
    public function test_service_type_full_legacy_crud_lifecycle(): void
    {
        // 1. Create
        $postRes = $this->postJson('/api/v1/master-data/service-types', [
            'name' => 'Rawat Intensif ICU',
            'is_active' => true,
        ]);
        $postRes->assertStatus(201)
            ->assertJsonPath('data.name', 'Rawat Intensif ICU');

        $id = $postRes->json('data.id');
        $this->assertDatabaseHas('ref_jenis_layanan', [
            'id' => $id,
            'nama' => 'Rawat Intensif ICU',
            'status' => '1',
        ], 'legacy');

        // 2. Index
        $listRes = $this->getJson('/api/v1/master-data/service-types?search=Intensif');
        $listRes->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id);

        // 3. Show
        $showRes = $this->getJson("/api/v1/master-data/service-types/{$id}");
        $showRes->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', 'Rawat Intensif ICU');

        // 4. Update
        $putRes = $this->putJson("/api/v1/master-data/service-types/{$id}", [
            'name' => 'Rawat Intensif PICU/NICU',
            'is_active' => true,
        ]);
        $putRes->assertOk()
            ->assertJsonPath('data.name', 'Rawat Intensif PICU/NICU');

        $this->assertDatabaseHas('ref_jenis_layanan', [
            'id' => $id,
            'nama' => 'Rawat Intensif PICU/NICU',
        ], 'legacy');

        // 5. Delete prevention when used in trx_visit
        DB::connection('legacy')->table('trx_visit')->insert([
            'id_jenis_layanan' => $id,
        ]);

        $delBlockedRes = $this->deleteJson("/api/v1/master-data/service-types/{$id}");
        $delBlockedRes->assertStatus(422);

        // Clean up trx_visit and delete successfully
        DB::connection('legacy')->table('trx_visit')->truncate();

        $delRes = $this->deleteJson("/api/v1/master-data/service-types/{$id}");
        $delRes->assertNoContent();

        $this->assertSoftDeleted('ref_jenis_layanan', ['id' => $id], 'legacy');
    }

    /* -------------------------------------------------------------
     * 4. DIET TYPE (ref_jenis_diet)
     * ------------------------------------------------------------- */
    public function test_diet_type_full_legacy_crud_lifecycle(): void
    {
        // 1. Create
        $postRes = $this->postJson('/api/v1/master-data/diet-types', [
            'name' => 'Diet Diabetes Melitus',
            'description' => 'Diet khusus pasien DM rendah gula',
            'is_active' => true,
        ]);
        $postRes->assertStatus(201)
            ->assertJsonPath('data.name', 'Diet Diabetes Melitus');

        $id = $postRes->json('data.id');
        $this->assertDatabaseHas('ref_jenis_diet', [
            'id' => $id,
            'nama' => 'Diet Diabetes Melitus',
            'deskripsi' => 'Diet khusus pasien DM rendah gula',
            'status' => '1',
        ], 'legacy');

        // 2. Index
        $listRes = $this->getJson('/api/v1/master-data/diet-types?search=Diabetes');
        $listRes->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id);

        // 3. Show
        $showRes = $this->getJson("/api/v1/master-data/diet-types/{$id}");
        $showRes->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', 'Diet Diabetes Melitus');

        // 4. Update
        $putRes = $this->putJson("/api/v1/master-data/diet-types/{$id}", [
            'name' => 'Diet DM Tipe 2',
            'description' => 'Diet DM modifikasi serat tinggi',
            'is_active' => true,
        ]);
        $putRes->assertOk()
            ->assertJsonPath('data.name', 'Diet DM Tipe 2');

        $this->assertDatabaseHas('ref_jenis_diet', [
            'id' => $id,
            'nama' => 'Diet DM Tipe 2',
        ], 'legacy');

        // 5. Delete
        $delRes = $this->deleteJson("/api/v1/master-data/diet-types/{$id}");
        $delRes->assertOk();

        $this->assertSoftDeleted('ref_jenis_diet', ['id' => $id], 'legacy');
    }

    /* -------------------------------------------------------------
     * 5. LETTER TYPE (ref_jenis_surat)
     * ------------------------------------------------------------- */
    public function test_letter_type_full_legacy_crud_lifecycle(): void
    {
        // 1. Create
        $postRes = $this->postJson('/api/v1/master-data/letter-types', [
            'name' => 'Surat Sakit Standar',
            'description' => 'Surat keterangan istirahat sakit',
            'legacy_resource' => 'surat_sakit_v2.php',
            'is_active' => true,
        ]);
        $postRes->assertStatus(201)
            ->assertJsonPath('data.name', 'Surat Sakit Standar');

        $id = $postRes->json('data.id');
        $this->assertDatabaseHas('ref_jenis_surat', [
            'id' => $id,
            'nama' => 'Surat Sakit Standar',
            'deskripsi' => 'Surat keterangan istirahat sakit',
            'resource' => 'surat_sakit_v2.php',
            'status' => '1',
        ], 'legacy');

        // 2. Index
        $listRes = $this->getJson('/api/v1/master-data/letter-types?search=Standar');
        $listRes->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id);

        // 3. Show
        $showRes = $this->getJson("/api/v1/master-data/letter-types/{$id}");
        $showRes->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', 'Surat Sakit Standar');

        // 4. Update
        $putRes = $this->putJson("/api/v1/master-data/letter-types/{$id}", [
            'name' => 'Surat Sakit Karyawan',
            'description' => 'Surat keterangan cuti sakit',
            'legacy_resource' => 'surat_sakit_karyawan.php',
            'is_active' => true,
        ]);
        $putRes->assertOk()
            ->assertJsonPath('data.name', 'Surat Sakit Karyawan');

        $this->assertDatabaseHas('ref_jenis_surat', [
            'id' => $id,
            'nama' => 'Surat Sakit Karyawan',
            'resource' => 'surat_sakit_karyawan.php',
        ], 'legacy');

        // 5. Update status
        $statusRes = $this->patchJson("/api/v1/master-data/letter-types/{$id}/status", [
            'is_active' => false,
        ]);
        $statusRes->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('ref_jenis_surat', [
            'id' => $id,
            'status' => '0',
        ], 'legacy');

        // 6. Delete
        $delRes = $this->deleteJson("/api/v1/master-data/letter-types/{$id}");
        $delRes->assertOk();

        $this->assertSoftDeleted('ref_jenis_surat', ['id' => $id], 'legacy');
    }

    /* -------------------------------------------------------------
     * 6. ACTIVITY TYPE (ref_jenis_kegiatan)
     * ------------------------------------------------------------- */
    public function test_activity_type_full_legacy_crud_lifecycle(): void
    {
        // 1. Create parent activity
        $parentRes = $this->postJson('/api/v1/master-data/activity-types', [
            'name' => 'Pelayanan Bedah Mayor',
            'is_active' => true,
        ]);
        $parentRes->assertStatus(201)
            ->assertJsonPath('data.name', 'Pelayanan Bedah Mayor');

        $parentId = $parentRes->json('data.id');
        $this->assertDatabaseHas('ref_jenis_kegiatan', [
            'id' => $parentId,
            'nama' => 'Pelayanan Bedah Mayor',
            'parent_id' => null,
            'status' => 1,
        ], 'legacy');

        // 2. Create child activity
        $childRes = $this->postJson('/api/v1/master-data/activity-types', [
            'name' => 'Bedah Laparoskopi',
            'parent_id' => $parentId,
            'is_active' => true,
        ]);
        $childRes->assertStatus(201)
            ->assertJsonPath('data.name', 'Bedah Laparoskopi')
            ->assertJsonPath('data.parent_id', $parentId);

        $childId = $childRes->json('data.id');
        $this->assertDatabaseHas('ref_jenis_kegiatan', [
            'id' => $childId,
            'nama' => 'Bedah Laparoskopi',
            'parent_id' => $parentId,
        ], 'legacy');

        // 3. Lookup returns tree
        $lookupRes = $this->getJson('/api/v1/master-data/activity-types/lookup');
        $lookupRes->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'original_name']]]);

        // 4. Show child with parent relation
        $showRes = $this->getJson("/api/v1/master-data/activity-types/{$childId}");
        $showRes->assertOk()
            ->assertJsonPath('data.id', $childId)
            ->assertJsonPath('data.parent.id', $parentId);

        // 5. Update child name
        $putRes = $this->putJson("/api/v1/master-data/activity-types/{$childId}", [
            'name' => 'Bedah Laparoskopi Elektif',
            'parent_id' => $parentId,
            'is_active' => true,
        ]);
        $putRes->assertOk()
            ->assertJsonPath('data.name', 'Bedah Laparoskopi Elektif');

        // 6. Delete prevention: parent cannot be deleted if child exists
        $delParentFail = $this->deleteJson("/api/v1/master-data/activity-types/{$parentId}");
        $delParentFail->assertStatus(422);

        // Delete child first
        $delChildRes = $this->deleteJson("/api/v1/master-data/activity-types/{$childId}");
        $delChildRes->assertOk();
        $this->assertSoftDeleted('ref_jenis_kegiatan', ['id' => $childId], 'legacy');

        // Now parent can be deleted
        $delParentRes = $this->deleteJson("/api/v1/master-data/activity-types/{$parentId}");
        $delParentRes->assertOk();
        $this->assertSoftDeleted('ref_jenis_kegiatan', ['id' => $parentId], 'legacy');
    }
}
