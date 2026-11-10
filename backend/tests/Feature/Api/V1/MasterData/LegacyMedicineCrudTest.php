<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Legacy\LegacyIcd10;
use App\Models\Legacy\LegacyMedicine;
use App\Models\MasterData\Medicine;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacyMedicineCrudTest extends TestCase
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

        // 1. Create legacy ref_obat table
        DB::connection('legacy')->statement('
            CREATE TABLE ref_obat (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_kategori_obat INTEGER NULL,
                kode VARCHAR(255) NULL,
                nama VARCHAR(255) NOT NULL,
                deskripsi TEXT NULL,
                expired_date DATE NULL,
                id_satuan_obat INTEGER NULL,
                harga_beli INTEGER NULL,
                harga_jual INTEGER NULL,
                stok INTEGER NULL,
                tipe VARCHAR(20) DEFAULT "obat",
                tipe_gen VARCHAR(20) NULL,
                tipe_for VARCHAR(20) NULL,
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME,
                id_user INTEGER NULL,
                id_obat_jalur_masuk INTEGER NULL,
                fungsi VARCHAR(255) NULL,
                id_generik INTEGER NULL,
                id_golongan INTEGER NULL,
                kode_bpjs VARCHAR(50) NULL,
                is_bpjs INTEGER DEFAULT -1,
                kode_kfa VARCHAR(255) NULL
            )
        ');

        // 2. Create legacy ref_kategori_obat
        DB::connection('legacy')->statement('
            CREATE TABLE ref_kategori_obat (
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

        // 3. Create legacy ref_satuan_obat
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

        // 4. Create legacy ref_obat_jalur_masuk
        DB::connection('legacy')->statement('
            CREATE TABLE ref_obat_jalur_masuk (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                rute_obat VARCHAR(100) NOT NULL,
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME,
                updated_at DATETIME,
                delete_time DATETIME,
                id_user INTEGER NULL
            )
        ');

        // 5. Create legacy tbl_user for identity mapping
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

        // 6. Create legacy transaction tables for referential checks
        DB::connection('legacy')->statement('
            CREATE TABLE lgs_faktur_detail (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_lgs_faktur INTEGER NULL,
                id_obat INTEGER NOT NULL,
                qty INTEGER DEFAULT 0,
                status VARCHAR(1) DEFAULT "1"
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE lgs_log_stok (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_lgs_faktur_detail INTEGER NULL,
                id_gudang INTEGER NULL,
                qty INTEGER DEFAULT 0
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE map_paket_obat (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_ref_obat INTEGER NOT NULL,
                id_ref_paket INTEGER NOT NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE trx_obat_masuk_detail (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_obat INTEGER NOT NULL,
                qty INTEGER DEFAULT 0
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

        // Insert some initial foreign reference data
        DB::connection('legacy')->table('ref_kategori_obat')->insert([
            'id' => 10,
            'nama' => 'Antibiotik',
            'status' => '1',
            'created_at' => now(),
        ]);

        DB::connection('legacy')->table('ref_satuan_obat')->insert([
            'id' => 20,
            'nama' => 'Tablet',
            'status' => '1',
            'created_at' => now(),
        ]);

        DB::connection('legacy')->table('ref_obat_jalur_masuk')->insert([
            'id' => 30,
            'rute_obat' => 'Oral',
            'status' => '1',
            'created_at' => now(),
        ]);

        // Create application user
        $this->authUser = User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@simrs.test',
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/master-data/medicines');
        $response->assertStatus(401);
    }

    public function test_unmapped_legacy_user_is_forbidden_on_write(): void
    {
        $unmappedUser = User::factory()->create([
            'username' => 'unmapped_user',
            'email' => 'unmapped@simrs.test',
        ]);
        Sanctum::actingAs($unmappedUser);

        $response = $this->postJson('/api/v1/master-data/medicines', [
            'name' => 'Paracetamol 500mg',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
            ]);
    }

    public function test_can_list_medicines_directly_from_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        DB::connection('legacy')->table('ref_obat')->insert([
            [
                'id' => 1,
                'kode' => 'OBAT-001',
                'nama' => 'Amoxicillin 500mg',
                'id_kategori_obat' => 10,
                'id_satuan_obat' => 20,
                'id_obat_jalur_masuk' => 30,
                'harga_jual' => 1500,
                'harga_beli' => 1000,
                'status' => '1',
                'created_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 2,
                'kode' => 'OBAT-002',
                'nama' => 'Paracetamol 500mg',
                'id_kategori_obat' => 10,
                'id_satuan_obat' => 20,
                'id_obat_jalur_masuk' => 30,
                'harga_jual' => 800,
                'harga_beli' => 500,
                'status' => '1',
                'created_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 3,
                'kode' => 'OBAT-003',
                'nama' => 'Obat Terhapus',
                'id_kategori_obat' => 10,
                'id_satuan_obat' => 20,
                'id_obat_jalur_masuk' => 30,
                'harga_jual' => null,
                'harga_beli' => null,
                'status' => '0',
                'created_at' => now(),
                'deleted_at' => now()->subDay(),
            ],
        ]);

        $response = $this->getJson('/api/v1/master-data/medicines');
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertCount(2, $json['data']);
        $this->assertEquals('Amoxicillin 500mg', $json['data'][0]['name']);
        $this->assertEquals('Paracetamol 500mg', $json['data'][1]['name']);
        $this->assertEquals(1500.0, $json['data'][0]['price']);
        $this->assertEquals('Tablet', $json['data'][0]['unit']['name']);
        $this->assertEquals('Antibiotik', $json['data'][0]['category']['name']);
        $this->assertEquals('Oral', $json['data'][0]['route']['name']);
    }

    public function test_can_search_and_filter_medicines_directly_from_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        DB::connection('legacy')->table('ref_obat')->insert([
            [
                'id' => 101,
                'kode' => 'KOD-AMO',
                'nama' => 'Amoxicillin Sirup 125mg',
                'id_kategori_obat' => 10,
                'id_satuan_obat' => 20,
                'status' => '1',
                'created_at' => now(),
            ],
            [
                'id' => 102,
                'kode' => 'KOD-CIP',
                'nama' => 'Ciprofloxacin 500mg',
                'id_kategori_obat' => 10,
                'id_satuan_obat' => 20,
                'status' => '1',
                'created_at' => now(),
            ],
        ]);

        // Search by keyword "Amox"
        $response = $this->getJson('/api/v1/master-data/medicines?search=Amox');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Amoxicillin Sirup 125mg', $response->json('data.0.name'));

        // Filter by category_id
        $response = $this->getJson('/api/v1/master-data/medicines?category_id=10');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));

        // Filter by unit_id
        $response = $this->getJson('/api/v1/master-data/medicines?unit_id=20');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_show_single_medicine_directly_from_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_obat')->insertGetId([
            'kode' => 'MED-SHOW-01',
            'nama' => 'Cefixime 100mg',
            'deskripsi' => 'Antibiotik sefalosporin generasi ketiga',
            'id_kategori_obat' => 10,
            'id_satuan_obat' => 20,
            'id_obat_jalur_masuk' => 30,
            'harga_beli' => 2500,
            'harga_jual' => 3500,
            'fungsi' => 'Antibakteri',
            'kode_kfa' => 'KFA-998877',
            'kode_bpjs' => 'BPJS-123',
            'status' => '1',
            'created_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/master-data/medicines/{$id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.code', 'MED-SHOW-01')
            ->assertJsonPath('data.name', 'Cefixime 100mg')
            ->assertJsonPath('data.function', 'Antibakteri')
            ->assertJsonPath('data.kfa_code', 'KFA-998877')
            ->assertJsonPath('data.selling_price_per_unit', 3500)
            ->assertJsonPath('data.price', 3500)
            ->assertJsonPath('data.unit.name', 'Tablet')
            ->assertJsonPath('data.category.name', 'Antibiotik')
            ->assertJsonPath('data.route.name', 'Oral')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_can_create_medicine_directly_in_legacy_ref_obat(): void
    {
        Sanctum::actingAs($this->authUser);

        $payload = [
            'code' => 'MED-NEW-01',
            'name' => 'Azithromycin 500mg',
            'description' => 'Makrolida oral',
            'function' => 'Antibiotik pernapasan',
            'kfa_code' => 'KFA-AZI-500',
            'bpjs_code' => 'BPJS-AZI',
            'medicine_unit_id' => 20,
            'medicine_category_id' => 10,
            'medicine_route_id' => 30,
            'selling_price_per_unit' => 12500,
            'purchase_price' => 10000,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/master-data/medicines', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Azithromycin 500mg')
            ->assertJsonPath('data.code', 'MED-NEW-01')
            ->assertJsonPath('data.selling_price_per_unit', 12500)
            ->assertJsonPath('data.unit.name', 'Tablet');

        $createdId = $response->json('data.id');
        $this->assertNotNull($createdId);

        // Verify stored in legacy ref_obat
        $legacyRow = DB::connection('legacy')->table('ref_obat')->where('id', $createdId)->first();
        $this->assertNotNull($legacyRow);
        $this->assertEquals('Azithromycin 500mg', $legacyRow->nama);
        $this->assertEquals('MED-NEW-01', $legacyRow->kode);
        $this->assertEquals('1', $legacyRow->status);
        $this->assertEquals(103, $legacyRow->id_user);
        $this->assertEquals(12500, $legacyRow->harga_jual);
        $this->assertEquals(10000, $legacyRow->harga_beli);
        $this->assertEquals(20, $legacyRow->id_satuan_obat);
        $this->assertEquals(10, $legacyRow->id_kategori_obat);
        $this->assertEquals(30, $legacyRow->id_obat_jalur_masuk);
        $this->assertNotNull($legacyRow->created_at);

        // Verify ZERO records created in bigsirs_dev.medicines
        $this->assertDatabaseCount('medicines', 0);

        // Verify ZERO modifications to stock logs
        $this->assertEquals(0, DB::connection('legacy')->table('lgs_log_stok')->count());
    }

    public function test_cannot_create_medicine_with_duplicate_code(): void
    {
        Sanctum::actingAs($this->authUser);

        DB::connection('legacy')->table('ref_obat')->insert([
            'kode' => 'KODE-SAMA',
            'nama' => 'Obat A',
            'status' => '1',
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/master-data/medicines', [
            'code' => 'KODE-SAMA',
            'name' => 'Obat B',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_can_update_medicine_directly_in_legacy_ref_obat(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_obat')->insertGetId([
            'kode' => 'MED-UPD-01',
            'nama' => 'Nama Sebelum Update',
            'deskripsi' => 'Deskripsi Lama',
            'harga_jual' => 5000,
            'harga_beli' => 4000,
            'id_satuan_obat' => 20,
            'id_kategori_obat' => 10,
            'status' => '1',
            'created_at' => now(),
        ]);

        $payload = [
            'code' => 'MED-UPD-01-REV',
            'name' => 'Nama Setelah Update',
            'description' => 'Deskripsi Baru',
            'selling_price_per_unit' => 6500,
            'purchase_price' => 5000,
            'medicine_unit_id' => 20,
            'medicine_category_id' => 10,
            'is_active' => true,
        ];

        $response = $this->putJson("/api/v1/master-data/medicines/{$id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Nama Setelah Update')
            ->assertJsonPath('data.code', 'MED-UPD-01-REV')
            ->assertJsonPath('data.selling_price_per_unit', 6500);

        $updated = DB::connection('legacy')->table('ref_obat')->where('id', $id)->first();
        $this->assertEquals('Nama Setelah Update', $updated->nama);
        $this->assertEquals('MED-UPD-01-REV', $updated->kode);
        $this->assertEquals('Deskripsi Baru', $updated->deskripsi);
        $this->assertEquals(6500, $updated->harga_jual);
        $this->assertEquals(5000, $updated->harga_beli);
        $this->assertEquals(103, $updated->id_user);
        $this->assertNotNull($updated->updated_at);

        // Verify ZERO records in bigsirs_dev.medicines
        $this->assertDatabaseCount('medicines', 0);
    }

    public function test_can_toggle_medicine_status_in_legacy_ref_obat(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_obat')->insertGetId([
            'kode' => 'MED-STATUS-01',
            'nama' => 'Obat Status Test',
            'status' => '1',
            'created_at' => now(),
        ]);

        $response = $this->patchJson("/api/v1/master-data/medicines/{$id}/status", [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $row = DB::connection('legacy')->table('ref_obat')->where('id', $id)->first();
        $this->assertEquals('0', $row->status);
        $this->assertEquals(103, $row->id_user);
    }

    public function test_can_soft_delete_unreferenced_medicine(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_obat')->insertGetId([
            'kode' => 'MED-DEL-01',
            'nama' => 'Obat Siap Hapus',
            'status' => '1',
            'created_at' => now(),
        ]);

        $response = $this->deleteJson("/api/v1/master-data/medicines/{$id}");
        $response->assertStatus(204);

        $deleted = DB::connection('legacy')->table('ref_obat')->where('id', $id)->first();
        $this->assertNotNull($deleted);
        $this->assertNotNull($deleted->deleted_at);
        $this->assertEquals('0', $deleted->status);
        $this->assertEquals(103, $deleted->id_user);

        // Assert excluded from normal index query
        $indexResponse = $this->getJson('/api/v1/master-data/medicines');
        $this->assertEmpty($indexResponse->json('data'));
    }

    public function test_cannot_delete_medicine_referenced_in_faktur_detail(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_obat')->insertGetId([
            'kode' => 'MED-REF-01',
            'nama' => 'Obat Terikat Faktur',
            'status' => '1',
            'created_at' => now(),
        ]);

        // Insert reference in lgs_faktur_detail
        DB::connection('legacy')->table('lgs_faktur_detail')->insert([
            'id_obat' => $id,
            'qty' => 10,
            'status' => '1',
        ]);

        $response = $this->deleteJson("/api/v1/master-data/medicines/{$id}");
        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Data obat tidak dapat dihapus karena masih digunakan pada transaksi atau data terkait.',
            ]);

        // Ensure row was not deleted
        $row = DB::connection('legacy')->table('ref_obat')->where('id', $id)->first();
        $this->assertNull($row->deleted_at);
        $this->assertEquals('1', $row->status);
    }

    public function test_cannot_delete_medicine_referenced_in_map_paket_obat(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_obat')->insertGetId([
            'kode' => 'MED-PAKET-01',
            'nama' => 'Obat Terikat Paket',
            'status' => '1',
            'created_at' => now(),
        ]);

        // Insert reference in map_paket_obat
        DB::connection('legacy')->table('map_paket_obat')->insert([
            'id_ref_obat' => $id,
            'id_ref_paket' => 99,
        ]);

        $response = $this->deleteJson("/api/v1/master-data/medicines/{$id}");
        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Data obat tidak dapat dihapus karena masih digunakan pada transaksi atau data terkait.',
            ]);
    }

    public function test_other_legacy_models_remain_read_only(): void
    {
        $spec = new \App\Models\Legacy\LegacySpecialization();
        $spec->nama_spesialisasi = 'Spesialis Test Write';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Operasi tulis ke database legacy dinonaktifkan untuk menjaga integritas data sumber.');
        $spec->save();
    }
}
