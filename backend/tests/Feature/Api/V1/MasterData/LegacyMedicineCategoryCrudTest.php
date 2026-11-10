<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Legacy\LegacyIcd10;
use App\Models\Legacy\LegacyMedicineCategory;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacyMedicineCategoryCrudTest extends TestCase
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

        // 1. Create legacy ref_kategori_obat table
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
        $response = $this->getJson('/api/v1/master-data/medicine-categories');
        $response->assertStatus(401);
    }

    public function test_user_without_legacy_tbl_user_mapping_is_rejected_with_403(): void
    {
        $unmappedUser = User::factory()->create([
            'username' => 'unmapped_pharmacist',
            'email' => 'unmapped@simrs.test',
        ]);

        Sanctum::actingAs($unmappedUser);

        $payload = [
            'name' => 'Kategori Baru',
            'description' => 'Uji otorisasi',
        ];

        $response = $this->postJson('/api/v1/master-data/medicine-categories', $payload);
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
        ]);
    }

    public function test_can_list_categories_from_legacy_ref_kategori_obat(): void
    {
        DB::connection('legacy')->table('ref_kategori_obat')->insert([
            ['id' => 1, 'nama' => 'UMUM', 'deskripsi' => 'Obat umum', 'status' => '1', 'created_at' => now(), 'deleted_at' => null],
            ['id' => 2, 'nama' => 'ANTIBIOTIK', 'deskripsi' => 'Golongan antibiotik', 'status' => '1', 'created_at' => now(), 'deleted_at' => null],
            ['id' => 3, 'nama' => 'TERHAPUS', 'deskripsi' => 'Kategori terhapus', 'status' => '0', 'created_at' => now(), 'deleted_at' => now()],
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->getJson('/api/v1/master-data/medicine-categories');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.name', 'ANTIBIOTIK');
        $response->assertJsonPath('data.1.name', 'UMUM');
    }

    public function test_can_search_categories_in_legacy_database(): void
    {
        DB::connection('legacy')->table('ref_kategori_obat')->insert([
            ['id' => 1, 'nama' => 'ANALGETIK', 'deskripsi' => 'Pereda nyeri', 'status' => '1', 'created_at' => now(), 'deleted_at' => null],
            ['id' => 2, 'nama' => 'ANTIHISTAMIN', 'deskripsi' => 'Anti alergi', 'status' => '1', 'created_at' => now(), 'deleted_at' => null],
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->getJson('/api/v1/master-data/medicine-categories?search=alergi');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'ANTIHISTAMIN');
    }

    public function test_can_show_single_category_from_legacy_ref_kategori_obat(): void
    {
        DB::connection('legacy')->table('ref_kategori_obat')->insert([
            'id' => 12,
            'nama' => 'VITAMIN DAN SUPLEMEN',
            'deskripsi' => 'Penambah daya tahan tubuh',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->getJson('/api/v1/master-data/medicine-categories/12');

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', 12);
        $response->assertJsonPath('data.name', 'VITAMIN DAN SUPLEMEN');
    }

    public function test_can_create_category_directly_in_legacy_ref_kategori_obat(): void
    {
        Sanctum::actingAs($this->authUser);

        $payload = [
            'name' => 'KORTIKOSTEROID',
            'description' => 'Anti inflamasi kuat',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/master-data/medicine-categories', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'KORTIKOSTEROID');

        $newId = $response->json('data.id');

        // 1. Verify saved directly in simrs_legacy.ref_kategori_obat
        $legacyRow = DB::connection('legacy')->table('ref_kategori_obat')->where('id', $newId)->first();
        $this->assertNotNull($legacyRow);
        $this->assertEquals('KORTIKOSTEROID', $legacyRow->nama);
        $this->assertEquals('Anti inflamasi kuat', $legacyRow->deskripsi);
        $this->assertEquals('1', (string) $legacyRow->status);
        $this->assertEquals(103, $legacyRow->id_user);
        $this->assertNull($legacyRow->deleted_at);

        // 2. CRITICAL: Verify ZERO records duplicated to bigsirs_dev.medicine_categories
        $this->assertEquals(0, DB::table('medicine_categories')->count(), 'Zero records should exist in bigsirs_dev.medicine_categories');
    }

    public function test_prevents_duplicate_name_in_legacy_database(): void
    {
        DB::connection('legacy')->table('ref_kategori_obat')->insert([
            'id' => 1,
            'nama' => 'UMUM',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $payload = [
            'name' => 'UMUM',
            'description' => 'Duplikat',
        ];

        $response = $this->postJson('/api/v1/master-data/medicine-categories', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_category_in_legacy_ref_kategori_obat(): void
    {
        DB::connection('legacy')->table('ref_kategori_obat')->insert([
            'id' => 5,
            'nama' => 'HERBAL',
            'deskripsi' => 'Deskripsi lama',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $payload = [
            'name' => 'HERBAL TERSTANDAR',
            'description' => 'Deskripsi baru',
            'is_active' => true,
        ];

        $response = $this->putJson('/api/v1/master-data/medicine-categories/5', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'HERBAL TERSTANDAR');

        // Check in legacy table
        $legacyRow = DB::connection('legacy')->table('ref_kategori_obat')->where('id', 5)->first();
        $this->assertEquals('HERBAL TERSTANDAR', $legacyRow->nama);
        $this->assertEquals('Deskripsi baru', $legacyRow->deskripsi);
        $this->assertEquals(103, $legacyRow->id_user);
        $this->assertNotNull($legacyRow->updated_at);

        // Verify zero writes to bigsirs_dev
        $this->assertEquals(0, DB::table('medicine_categories')->count());
    }

    public function test_can_toggle_category_status_in_legacy_database(): void
    {
        DB::connection('legacy')->table('ref_kategori_obat')->insert([
            'id' => 8,
            'nama' => 'ANTIPIRITIK',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->patchJson('/api/v1/master-data/medicine-categories/8/status', [
            'is_active' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.is_active', false);

        $legacyRow = DB::connection('legacy')->table('ref_kategori_obat')->where('id', 8)->first();
        $this->assertEquals('0', (string) $legacyRow->status);
        $this->assertEquals(103, $legacyRow->id_user);
    }

    public function test_can_soft_delete_category_in_legacy_ref_kategori_obat(): void
    {
        DB::connection('legacy')->table('ref_kategori_obat')->insert([
            'id' => 9,
            'nama' => 'KATEGORI DIHAPUS',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->deleteJson('/api/v1/master-data/medicine-categories/9');
        $response->assertStatus(204);

        // Check legacy row is soft deleted
        $legacyRow = DB::connection('legacy')->table('ref_kategori_obat')->where('id', 9)->first();
        $this->assertNotNull($legacyRow);
        $this->assertEquals('0', (string) $legacyRow->status);
        $this->assertNotNull($legacyRow->deleted_at);
        $this->assertEquals(103, $legacyRow->id_user);

        // Verify it is excluded from listing
        $listResponse = $this->getJson('/api/v1/master-data/medicine-categories');
        $listResponse->assertJsonCount(0, 'data');
    }

    public function test_cannot_delete_category_referenced_in_medicines(): void
    {
        DB::connection('legacy')->table('ref_kategori_obat')->insert([
            'id' => 1,
            'nama' => 'ANTIBIOTIK BERISI OBAT',
            'status' => '1',
            'created_at' => now(),
        ]);

        DB::connection('legacy')->table('ref_obat')->insert([
            'id' => 101,
            'id_kategori_obat' => 1,
            'nama' => 'Amoxicillin 500mg',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->deleteJson('/api/v1/master-data/medicine-categories/1');
        $response->assertStatus(409);
        $response->assertJson([
            'message' => 'Kategori obat tidak dapat dihapus karena masih digunakan pada data obat.',
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

