<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyIcdCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.connections.legacy', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        LegacyAccess::clearCache();

        // 1. Setup ref_diagnosa (ICD-10)
        DB::connection('legacy')->statement('
            CREATE TABLE ref_diagnosa (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code VARCHAR(11) NULL,
                code_root VARCHAR(11) NULL,
                nama VARCHAR(255) NULL,
                nama_en VARCHAR(255) NULL,
                deskripsi VARCHAR(100) NULL,
                riwayat_penyakit VARCHAR(2) DEFAULT "0",
                status VARCHAR(2) DEFAULT "1",
                id_user INTEGER NULL,
                ihs_id VARCHAR(255) NULL,
                kode_inacbg VARCHAR(100) NULL,
                deskripsi_inacbg TEXT NULL,
                tarif_kelas1 FLOAT NULL,
                tarif_kelas2 FLOAT NULL,
                tarif_kelas3 FLOAT NULL,
                tarif_rawat_jalan FLOAT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        // 2. Setup trx_diagnosa
        DB::connection('legacy')->statement('
            CREATE TABLE trx_diagnosa (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_diagnosa INTEGER NOT NULL
            )
        ');

        // 3. Setup ref_icd_9 (ICD-9-CM)
        DB::connection('legacy')->statement('
            CREATE TABLE ref_icd_9 (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kode VARCHAR(255) NOT NULL,
                nama TEXT NOT NULL,
                status INTEGER DEFAULT 1,
                id_user INTEGER DEFAULT 0,
                kode_inacbg VARCHAR(100) NULL,
                deskripsi_inacbg TEXT NULL,
                tarif_kelas1 FLOAT NULL,
                tarif_kelas2 FLOAT NULL,
                tarif_kelas3 FLOAT NULL,
                tarif_rawat_jalan FLOAT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        // 4. Setup trx_icd_9
        DB::connection('legacy')->statement('
            CREATE TABLE trx_icd_9 (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_icd_9 INTEGER NOT NULL
            )
        ');

        // 5. Setup tbl_user
        DB::connection('legacy')->statement('
            CREATE TABLE tbl_user (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(100) NOT NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->table('tbl_user')->insert([
            'id' => 99,
            'username' => 'admin_test',
        ]);

        $this->user = User::factory()->create([
            'username' => 'admin_test',
        ]);
        $this->actingAs($this->user);
    }

    /* -------------------------------------------------------------
     * 1. ICD-10 (ref_diagnosa)
     * ------------------------------------------------------------- */
    public function test_icd10_full_legacy_crud_lifecycle(): void
    {
        // 1. Create ICD-10
        $res = $this->postJson('/api/v1/master-data/icd10', [
            'code' => 'a00.1',
            'name' => 'Kolera Asiatika',
            'english_name' => 'Cholera due to Vibrio cholerae',
            'description' => 'Infeksi saluran cerna akut',
            'is_medical_history' => true,
            'is_active' => true,
            'inacbg_code' => 'A-4-10-I',
            'inacbg_name' => 'Gastroenteritis Akut',
            'class_1_tariff' => 150000,
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.code', 'A00.1')
            ->assertJsonPath('data.name', 'Kolera Asiatika')
            ->assertJsonPath('data.english_name', 'Cholera due to Vibrio cholerae')
            ->assertJsonPath('data.is_medical_history', true)
            ->assertJsonPath('data.is_active', true);

        $id = $res->json('data.id');

        $this->assertDatabaseHas('ref_diagnosa', [
            'id' => $id,
            'code' => 'A00.1',
            'code_root' => 'A00',
            'nama' => 'Kolera Asiatika',
            'id_user' => 99,
            'status' => '1',
        ], 'legacy');

        // 2. Reject Duplicate Code
        $dupRes = $this->postJson('/api/v1/master-data/icd10', [
            'code' => 'A00.1',
            'name' => 'Kolera Duplikat',
        ]);
        $dupRes->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        // 3. Read Single
        $showRes = $this->getJson("/api/v1/master-data/icd10/{$id}");
        $showRes->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.code', 'A00.1');

        // 4. Update ICD-10
        $updRes = $this->putJson("/api/v1/master-data/icd10/{$id}", [
            'code' => 'A00.1',
            'name' => 'Kolera El Tor Terbarui',
            'english_name' => 'Updated Cholera',
            'is_medical_history' => false,
            'is_active' => true,
        ]);
        $updRes->assertOk()
            ->assertJsonPath('data.name', 'Kolera El Tor Terbarui')
            ->assertJsonPath('data.is_medical_history', false);

        $this->assertDatabaseHas('ref_diagnosa', [
            'id' => $id,
            'nama' => 'Kolera El Tor Terbarui',
            'riwayat_penyakit' => '0',
        ], 'legacy');

        // 5. Update Status Toggle
        $statusRes = $this->patchJson("/api/v1/master-data/icd10/{$id}/status", [
            'is_active' => false,
        ]);
        $statusRes->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('ref_diagnosa', [
            'id' => $id,
            'status' => '0',
        ], 'legacy');

        // Reactivate
        $this->patchJson("/api/v1/master-data/icd10/{$id}/status", ['is_active' => true]);

        // 6. Integrity Guard: Cannot delete if referenced in trx_diagnosa
        DB::connection('legacy')->table('trx_diagnosa')->insert([
            'id' => 101,
            'id_diagnosa' => $id,
        ]);

        $failDel = $this->deleteJson("/api/v1/master-data/icd10/{$id}");
        $failDel->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Kode diagnosis tidak dapat dihapus karena telah digunakan dalam rekam medis atau transaksi pasien.',
            ]);

        // 7. Soft Delete after removing transaction
        DB::connection('legacy')->table('trx_diagnosa')->where('id', 101)->delete();

        $delRes = $this->deleteJson("/api/v1/master-data/icd10/{$id}");
        $delRes->assertOk();

        $row = DB::connection('legacy')->table('ref_diagnosa')->where('id', $id)->first();
        $this->assertEquals('0', $row->status);
        $this->assertNotNull($row->deleted_at);
        $this->assertNotEquals('0000-00-00 00:00:00', $row->deleted_at);
    }

    /* -------------------------------------------------------------
     * 2. ICD-9-CM (ref_icd_9)
     * ------------------------------------------------------------- */
    public function test_icd9_full_legacy_crud_lifecycle(): void
    {
        // 1. Create ICD-9
        $res = $this->postJson('/api/v1/master-data/icd9-cms', [
            'code' => '87.49',
            'name' => 'Other chest x-ray',
            'description' => 'Foto Rontgen Dada Lainnya',
            'is_active' => true,
            'inacbg_code' => 'X-9-01-I',
            'class_1_tariff' => 120000,
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.code', '87.49')
            ->assertJsonPath('data.name', 'Other chest x-ray')
            ->assertJsonPath('data.is_active', true);

        $id = $res->json('data.id');

        $this->assertDatabaseHas('ref_icd_9', [
            'id' => $id,
            'kode' => '87.49',
            'nama' => 'Other chest x-ray',
            'status' => 1,
            'id_user' => 99,
        ], 'legacy');

        // 2. Reject Duplicate Code
        $dupRes = $this->postJson('/api/v1/master-data/icd9-cms', [
            'code' => '87.49',
            'name' => 'Other chest x-ray Duplicate',
        ]);
        $dupRes->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        // 3. Read Single
        $showRes = $this->getJson("/api/v1/master-data/icd9-cms/{$id}");
        $showRes->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.code', '87.49');

        // 4. Update ICD-9
        $updRes = $this->putJson("/api/v1/master-data/icd9-cms/{$id}", [
            'code' => '87.49',
            'name' => 'Other chest x-ray Updated',
            'description' => 'Deskripsi Terbarui',
            'is_active' => true,
        ]);
        $updRes->assertOk()
            ->assertJsonPath('data.name', 'Other chest x-ray Updated');

        $this->assertDatabaseHas('ref_icd_9', [
            'id' => $id,
            'nama' => 'Other chest x-ray Updated',
        ], 'legacy');

        // 5. Update Status Toggle
        $statusRes = $this->patchJson("/api/v1/master-data/icd9-cms/{$id}/status", [
            'is_active' => false,
        ]);
        $statusRes->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('ref_icd_9', [
            'id' => $id,
            'status' => 0,
        ], 'legacy');

        // Reactivate
        $this->patchJson("/api/v1/master-data/icd9-cms/{$id}/status", ['is_active' => true]);

        // 6. Test Lookup Endpoint
        $lookupRes = $this->getJson('/api/v1/lookups/icd9-cms?search=chest');
        $lookupRes->assertOk()
            ->assertJsonFragment([
                'id' => $id,
                'code' => '87.49',
                'name' => 'Other chest x-ray Updated',
                'is_active' => true,
            ]);

        // 7. Integrity Guard: Cannot delete if referenced in trx_icd_9
        DB::connection('legacy')->table('trx_icd_9')->insert([
            'id' => 201,
            'id_icd_9' => $id,
        ]);

        $failDel = $this->deleteJson("/api/v1/master-data/icd9-cms/{$id}");
        $failDel->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Prosedur ICD-9 tidak dapat dihapus karena telah digunakan dalam rekam medis atau transaksi pasien.',
            ]);

        // 8. Soft Delete after removing transaction
        DB::connection('legacy')->table('trx_icd_9')->where('id', 201)->delete();

        $delRes = $this->deleteJson("/api/v1/master-data/icd9-cms/{$id}");
        $delRes->assertNoContent();

        $row = DB::connection('legacy')->table('ref_icd_9')->where('id', $id)->first();
        $this->assertEquals(0, $row->status);
        $this->assertNotNull($row->deleted_at);
        $this->assertNotEquals('0000-00-00 00:00:00', $row->deleted_at);
    }
}

