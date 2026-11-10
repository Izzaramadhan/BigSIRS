<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Legacy\LegacyIcd10;
use App\Models\Legacy\LegacyMedicineUnit;
use App\Models\MasterData\MedicineUnit;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacyMedicineUnitCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $authUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure isolated in-memory SQLite for legacy connection
        Config::set('database.connections.legacy', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        LegacyAccess::clearCache();

        // 1. Create legacy ref_satuan_obat table
        DB::connection('legacy')->statement('
            CREATE TABLE ref_satuan_obat (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(255) NOT NULL,
                deskripsi TEXT NULL,
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME,
                id_user INTEGER NULL
            )
        ');

        // 2. Create legacy tbl_user table for identity mapping
        DB::connection('legacy')->statement('
            CREATE TABLE tbl_user (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_pegawai INTEGER NULL,
                nama VARCHAR(100),
                username VARCHAR(40) NOT NULL,
                password VARCHAR(50),
                login VARCHAR(1) DEFAULT "0",
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME,
                id_user INTEGER NULL,
                is_active INTEGER DEFAULT 1
            )
        ');

        // 3. Create legacy ref_obat table for referential protection
        DB::connection('legacy')->statement('
            CREATE TABLE ref_obat (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_kategori_obat INTEGER NULL,
                id_satuan_obat INTEGER NULL,
                nama VARCHAR(255) NOT NULL,
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME,
                deleted_at DATETIME,
                id_user INTEGER NULL
            )
        ');

        // 4. Create legacy prescription detail table for referential protection
        DB::connection('legacy')->statement('
            CREATE TABLE trx_resep_detail (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_satuan INTEGER NULL,
                jumlah INTEGER DEFAULT 1,
                created_at DATETIME
            )
        ');

        // Insert legacy user
        DB::connection('legacy')->table('tbl_user')->insert([
            'id' => 103,
            'nama' => 'Administrator Farmasi',
            'username' => 'admin',
            'created_at' => now(),
            'status' => '1',
        ]);

        // Create application user
        $this->authUser = User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@simrs.test',
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/master-data/medicine-units');
        $response->assertStatus(401);
    }

    public function test_user_without_legacy_tbl_user_mapping_is_rejected_with_403(): void
    {
        $unmappedUser = User::factory()->create([
            'username' => 'unmapped_unit_user',
            'email' => 'unmapped_unit@simrs.test',
        ]);

        Sanctum::actingAs($unmappedUser);

        $payload = [
            'name' => 'Ampul',
            'description' => 'Sediaan injeksi',
        ];

        $response = $this->postJson('/api/v1/master-data/medicine-units', $payload);
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
        ]);
    }

    public function test_can_list_units_from_legacy_ref_satuan_obat(): void
    {
        DB::connection('legacy')->table('ref_satuan_obat')->insert([
            ['id' => 1, 'nama' => 'Tablet', 'deskripsi' => 'Bentuk padat', 'status' => '1', 'created_at' => now(), 'deleted_at' => null],
            ['id' => 2, 'nama' => 'Botol', 'deskripsi' => 'Bentuk cair', 'status' => '1', 'created_at' => now(), 'deleted_at' => null],
            ['id' => 3, 'nama' => 'Terhapus', 'deskripsi' => 'Satuan terhapus', 'status' => '0', 'created_at' => now(), 'deleted_at' => now()],
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->getJson('/api/v1/master-data/medicine-units');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.name', 'Botol');
        $response->assertJsonPath('data.1.name', 'Tablet');
    }

    public function test_can_search_units_in_legacy_database(): void
    {
        DB::connection('legacy')->table('ref_satuan_obat')->insert([
            ['id' => 1, 'nama' => 'Kapsul', 'deskripsi' => 'Cangkang gelatin', 'status' => '1', 'created_at' => now(), 'deleted_at' => null],
            ['id' => 2, 'nama' => 'Vial', 'deskripsi' => 'Wadah steril serbuk/cair', 'status' => '1', 'created_at' => now(), 'deleted_at' => null],
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->getJson('/api/v1/master-data/medicine-units?search=gelatin');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Kapsul');
    }

    public function test_can_show_single_unit_from_legacy_ref_satuan_obat(): void
    {
        DB::connection('legacy')->table('ref_satuan_obat')->insert([
            'id' => 15,
            'nama' => 'Suppositoria',
            'deskripsi' => 'Sediaan rektal',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->getJson('/api/v1/master-data/medicine-units/15');

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', 15);
        $response->assertJsonPath('data.name', 'Suppositoria');
    }

    public function test_can_create_unit_directly_in_legacy_ref_satuan_obat(): void
    {
        Sanctum::actingAs($this->authUser);

        $payload = [
            'name' => 'Inhaler',
            'description' => 'Sediaan inhalasi pernapasan',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/master-data/medicine-units', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Inhaler');

        $newId = $response->json('data.id');

        // 1. Verify saved directly in simrs_legacy.ref_satuan_obat
        $legacyRow = DB::connection('legacy')->table('ref_satuan_obat')->where('id', $newId)->first();
        $this->assertNotNull($legacyRow);
        $this->assertEquals('Inhaler', $legacyRow->nama);
        $this->assertEquals('Sediaan inhalasi pernapasan', $legacyRow->deskripsi);
        $this->assertEquals('1', (string) $legacyRow->status);
        $this->assertEquals(103, $legacyRow->id_user);
        $this->assertNull($legacyRow->deleted_at);

        // 2. CRITICAL: Verify ZERO records duplicated to bigsirs_dev.medicine_units
        $this->assertEquals(0, MedicineUnit::count(), 'Zero records should exist in bigsirs_dev.medicine_units');
    }

    public function test_prevents_duplicate_name_in_legacy_database(): void
    {
        DB::connection('legacy')->table('ref_satuan_obat')->insert([
            'id' => 1,
            'nama' => 'Tablet',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $payload = [
            'name' => 'Tablet',
            'description' => 'Duplikat',
        ];

        $response = $this->postJson('/api/v1/master-data/medicine-units', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_unit_in_legacy_ref_satuan_obat(): void
    {
        DB::connection('legacy')->table('ref_satuan_obat')->insert([
            'id' => 7,
            'nama' => 'Sachet',
            'deskripsi' => 'Kemasan lama',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $payload = [
            'name' => 'Sachet Serbuk',
            'description' => 'Kemasan sachet terstandar',
            'is_active' => true,
        ];

        $response = $this->putJson('/api/v1/master-data/medicine-units/7', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Sachet Serbuk');

        // Check in legacy table
        $legacyRow = DB::connection('legacy')->table('ref_satuan_obat')->where('id', 7)->first();
        $this->assertEquals('Sachet Serbuk', $legacyRow->nama);
        $this->assertEquals('Kemasan sachet terstandar', $legacyRow->deskripsi);
        $this->assertEquals(103, $legacyRow->id_user);
        $this->assertNotNull($legacyRow->updated_at);

        // Verify zero writes to bigsirs_dev
        $this->assertEquals(0, MedicineUnit::count());
    }

    public function test_can_toggle_unit_status_in_legacy_database(): void
    {
        DB::connection('legacy')->table('ref_satuan_obat')->insert([
            'id' => 8,
            'nama' => 'Nebules',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->patchJson('/api/v1/master-data/medicine-units/8/status', [
            'is_active' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.is_active', false);

        $legacyRow = DB::connection('legacy')->table('ref_satuan_obat')->where('id', 8)->first();
        $this->assertEquals('0', (string) $legacyRow->status);
        $this->assertEquals(103, $legacyRow->id_user);
    }

    public function test_can_soft_delete_unit_in_legacy_ref_satuan_obat(): void
    {
        DB::connection('legacy')->table('ref_satuan_obat')->insert([
            'id' => 9,
            'nama' => 'SATUAN DIHAPUS',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->deleteJson('/api/v1/master-data/medicine-units/9');
        $response->assertStatus(204);

        // Check legacy row is soft deleted
        $legacyRow = DB::connection('legacy')->table('ref_satuan_obat')->where('id', 9)->first();
        $this->assertNotNull($legacyRow);
        $this->assertEquals('0', (string) $legacyRow->status);
        $this->assertNotNull($legacyRow->deleted_at);
        $this->assertEquals(103, $legacyRow->id_user);

        // Verify it is excluded from listing
        $listResponse = $this->getJson('/api/v1/master-data/medicine-units');
        $listResponse->assertJsonCount(0, 'data');
    }

    public function test_cannot_delete_unit_referenced_in_medicines(): void
    {
        DB::connection('legacy')->table('ref_satuan_obat')->insert([
            'id' => 1,
            'nama' => 'Tablet Terpakai',
            'status' => '1',
            'created_at' => now(),
        ]);

        DB::connection('legacy')->table('ref_obat')->insert([
            'id' => 201,
            'id_satuan_obat' => 1,
            'nama' => 'Paracetamol 500mg',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->deleteJson('/api/v1/master-data/medicine-units/1');
        $response->assertStatus(409);
        $response->assertJson([
            'message' => 'Satuan obat tidak dapat dihapus karena masih digunakan pada data obat atau riwayat resep.',
        ]);
    }

    public function test_cannot_delete_unit_referenced_in_prescription_details(): void
    {
        DB::connection('legacy')->table('ref_satuan_obat')->insert([
            'id' => 2,
            'nama' => 'Kapsul Resep',
            'status' => '1',
            'created_at' => now(),
        ]);

        DB::connection('legacy')->table('trx_resep_detail')->insert([
            'id' => 301,
            'id_satuan' => 2,
            'jumlah' => 10,
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->deleteJson('/api/v1/master-data/medicine-units/2');
        $response->assertStatus(409);
        $response->assertJson([
            'message' => 'Satuan obat tidak dapat dihapus karena masih digunakan pada data obat atau riwayat resep.',
        ]);
    }

    public function test_other_legacy_models_remain_strictly_read_only(): void
    {
        $spec = new \App\Models\Legacy\LegacySpecialization();
        $spec->nama_spesialisasi = 'Spesialis Tulis Ilegal';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Operasi tulis ke database legacy dinonaktifkan untuk menjaga integritas data sumber.');
        $spec->save();
    }
}

