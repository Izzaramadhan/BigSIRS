<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Legacy\LegacyIcd10;
use App\Models\Legacy\LegacyWarehouse;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacyWarehouseCrudTest extends TestCase
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

        // 1. Create legacy ref_gudang table
        DB::connection('legacy')->statement('
            CREATE TABLE ref_gudang (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kode VARCHAR(50) NULL,
                nama VARCHAR(50) NOT NULL,
                deskripsi VARCHAR(50) NULL,
                type INTEGER NULL,
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

        // 3. Create legacy transaction tables for referential protection
        DB::connection('legacy')->statement('
            CREATE TABLE lgs_log_stok (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_gudang INTEGER NOT NULL,
                id_lgs_faktur_detail INTEGER NULL,
                id_user INTEGER NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE lgs_mutasi (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_gudang_asal INTEGER NULL,
                id_gudang_tujuan INTEGER NULL,
                id_user INTEGER NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE lgs_stok_opname (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_gudang INTEGER NOT NULL,
                id_user INTEGER NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_poliklinik (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kode VARCHAR(11),
                nama VARCHAR(50),
                id_gudang INTEGER NULL,
                status VARCHAR(1) DEFAULT "1",
                deleted_at DATETIME
            )
        ');

        // Insert legacy user
        DB::connection('legacy')->table('tbl_user')->insert([
            'id' => 103,
            'nama' => 'Administrator Logistik',
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
        $response = $this->getJson('/api/v1/master-data/warehouses');
        $response->assertStatus(401);
    }

    public function test_user_without_legacy_tbl_user_mapping_is_rejected_with_403(): void
    {
        $unmappedUser = User::factory()->create([
            'username' => 'user_tidak_dikenal',
            'email' => 'unmapped@simrs.test',
        ]);

        Sanctum::actingAs($unmappedUser);

        $payload = [
            'name' => 'Gudang Satelit',
            'code' => 'GDS-01',
        ];

        $response = $this->postJson('/api/v1/master-data/warehouses', $payload);
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
        ]);
    }

    public function test_can_list_warehouses_from_legacy_ref_gudang(): void
    {
        DB::connection('legacy')->table('ref_gudang')->insert([
            ['id' => 1, 'kode' => 'GDF', 'nama' => 'Gudang Utama', 'deskripsi' => 'Penerimaan utama', 'status' => '1', 'created_at' => now(), 'deleted_at' => null],
            ['id' => 2, 'kode' => 'AP-RJ', 'nama' => 'Apotek Rajal', 'deskripsi' => 'Rawat jalan', 'status' => '1', 'created_at' => now(), 'deleted_at' => null],
            ['id' => 3, 'kode' => 'DEL', 'nama' => 'Gudang Terhapus', 'deskripsi' => 'Sudah dihapus', 'status' => '0', 'created_at' => now(), 'deleted_at' => now()],
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->getJson('/api/v1/master-data/warehouses');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.name', 'Apotek Rajal');
        $response->assertJsonPath('data.1.name', 'Gudang Utama');
    }

    public function test_can_search_warehouses_in_legacy_database(): void
    {
        DB::connection('legacy')->table('ref_gudang')->insert([
            ['id' => 1, 'kode' => 'GDF', 'nama' => 'Gudang Farmasi Utama', 'deskripsi' => 'Pusat', 'status' => '1', 'created_at' => now()],
            ['id' => 2, 'kode' => 'IGD', 'nama' => 'Apotek IGD 24 Jam', 'deskripsi' => 'Gawat darurat', 'status' => '1', 'created_at' => now()],
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->getJson('/api/v1/master-data/warehouses?search=IGD');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Apotek IGD 24 Jam');
    }

    public function test_can_show_single_warehouse_from_legacy_ref_gudang(): void
    {
        DB::connection('legacy')->table('ref_gudang')->insert([
            'id' => 10,
            'kode' => 'GD-FAR',
            'nama' => 'Gudang Farmasi Induk',
            'deskripsi' => 'Gudang Pusat',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->getJson('/api/v1/master-data/warehouses/10');

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', 10);
        $response->assertJsonPath('data.name', 'Gudang Farmasi Induk');
        $response->assertJsonPath('data.code', 'GD-FAR');
    }

    public function test_can_create_warehouse_directly_in_legacy_ref_gudang(): void
    {
        Sanctum::actingAs($this->authUser);

        $payload = [
            'name' => 'Gudang Vaksin dan Cold Chain',
            'code' => 'GD-VK',
            'description' => 'Khusus penyimpanan vaksin suhu dingin',
        ];

        $response = $this->postJson('/api/v1/master-data/warehouses', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Gudang Vaksin dan Cold Chain');
        $response->assertJsonPath('data.code', 'GD-VK');

        $newId = $response->json('data.id');

        // 1. Verify saved directly in simrs_legacy.ref_gudang
        $legacyRow = DB::connection('legacy')->table('ref_gudang')->where('id', $newId)->first();
        $this->assertNotNull($legacyRow);
        $this->assertEquals('Gudang Vaksin dan Cold Chain', $legacyRow->nama);
        $this->assertEquals('GD-VK', $legacyRow->kode);
        $this->assertEquals('Khusus penyimpanan vaksin suhu dingin', $legacyRow->deskripsi);
        $this->assertEquals('1', (string) $legacyRow->status);
        $this->assertEquals(103, $legacyRow->id_user);
        $this->assertNull($legacyRow->deleted_at);

        // 2. CRITICAL: Verify ZERO records duplicated to bigsirs_dev.warehouses
        $this->assertEquals(0, Warehouse::count(), 'Zero records should exist in bigsirs_dev.warehouses');
    }

    public function test_prevents_duplicate_name_in_legacy_database(): void
    {
        DB::connection('legacy')->table('ref_gudang')->insert([
            'id' => 1,
            'kode' => 'GDF',
            'nama' => 'Gudang Utama',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $payload = [
            'name' => 'Gudang Utama',
            'code' => 'GDF-2',
        ];

        $response = $this->postJson('/api/v1/master-data/warehouses', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_warehouse_in_legacy_ref_gudang(): void
    {
        DB::connection('legacy')->table('ref_gudang')->insert([
            'id' => 5,
            'kode' => 'GD-OLD',
            'nama' => 'Gudang Transit Lama',
            'deskripsi' => 'Deskripsi lama',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $payload = [
            'name' => 'Gudang Transit Baru',
            'code' => 'GD-NEW',
            'description' => 'Deskripsi diperbarui',
        ];

        $response = $this->putJson('/api/v1/master-data/warehouses/5', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Gudang Transit Baru');
        $response->assertJsonPath('data.code', 'GD-NEW');

        // Check in legacy table
        $legacyRow = DB::connection('legacy')->table('ref_gudang')->where('id', 5)->first();
        $this->assertEquals('Gudang Transit Baru', $legacyRow->nama);
        $this->assertEquals('GD-NEW', $legacyRow->kode);
        $this->assertEquals('Deskripsi diperbarui', $legacyRow->deskripsi);
        $this->assertEquals(103, $legacyRow->id_user);
        $this->assertNotNull($legacyRow->updated_at);

        // Verify zero writes to bigsirs_dev
        $this->assertEquals(0, Warehouse::count());
    }

    public function test_can_soft_delete_warehouse_in_legacy_ref_gudang(): void
    {
        DB::connection('legacy')->table('ref_gudang')->insert([
            'id' => 7,
            'kode' => 'GD-HAPUS',
            'nama' => 'Gudang Untuk Dihapus',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->deleteJson('/api/v1/master-data/warehouses/7');
        $response->assertStatus(204);

        // Check legacy row is soft deleted
        $legacyRow = DB::connection('legacy')->table('ref_gudang')->where('id', 7)->first();
        $this->assertNotNull($legacyRow);
        $this->assertEquals('0', (string) $legacyRow->status);
        $this->assertNotNull($legacyRow->deleted_at);
        $this->assertEquals(103, $legacyRow->id_user);

        // Verify it is excluded from listing
        $listResponse = $this->getJson('/api/v1/master-data/warehouses');
        $listResponse->assertJsonCount(0, 'data');
    }

    public function test_cannot_delete_warehouse_referenced_in_stock_logs(): void
    {
        DB::connection('legacy')->table('ref_gudang')->insert([
            'id' => 1,
            'kode' => 'GDF',
            'nama' => 'Gudang Utama Berstok',
            'status' => '1',
            'created_at' => now(),
        ]);

        DB::connection('legacy')->table('lgs_log_stok')->insert([
            'id' => 101,
            'id_gudang' => 1,
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->deleteJson('/api/v1/master-data/warehouses/1');
        $response->assertStatus(409);
        $response->assertJson([
            'message' => 'Gudang tidak dapat dihapus karena masih digunakan sebagai gudang default pada poliklinik atau memiliki riwayat logistik.',
        ]);
    }

    public function test_cannot_delete_warehouse_referenced_in_mutations(): void
    {
        DB::connection('legacy')->table('ref_gudang')->insert([
            'id' => 2,
            'kode' => 'GD2',
            'nama' => 'Gudang Mutasi',
            'status' => '1',
            'created_at' => now(),
        ]);

        DB::connection('legacy')->table('lgs_mutasi')->insert([
            'id' => 201,
            'id_gudang_asal' => 2,
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->deleteJson('/api/v1/master-data/warehouses/2');
        $response->assertStatus(409);
    }

    public function test_cannot_delete_warehouse_referenced_in_polyclinic(): void
    {
        DB::connection('legacy')->table('ref_gudang')->insert([
            'id' => 3,
            'kode' => 'GD3',
            'nama' => 'Gudang Apotek Rajal',
            'status' => '1',
            'created_at' => now(),
        ]);

        DB::connection('legacy')->table('ref_poliklinik')->insert([
            'id' => 50,
            'kode' => 'POLI-UMUM',
            'nama' => 'Poliklinik Umum',
            'id_gudang' => 3,
            'status' => '1',
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->deleteJson('/api/v1/master-data/warehouses/3');
        $response->assertStatus(409);
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
