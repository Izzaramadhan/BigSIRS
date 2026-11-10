<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Legacy\LegacyIcd10;
use App\Models\Legacy\LegacySupplier;
use App\Models\MasterData\Supplier;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacySupplierCrudTest extends TestCase
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

        // 1. Create legacy ref_supplier table
        DB::connection('legacy')->statement('
            CREATE TABLE ref_supplier (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(50) NULL,
                telp VARCHAR(15) NULL,
                alamat VARCHAR(255) NULL,
                status SMALLINT DEFAULT 1,
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME,
                id_user SMALLINT NULL
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

        // 3. Create legacy purchase invoice table for referential protection
        DB::connection('legacy')->statement('
            CREATE TABLE lgs_faktur (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_supplier INTEGER NOT NULL,
                no_faktur VARCHAR(50) NULL,
                created_at DATETIME,
                id_user INTEGER NULL
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
        $response = $this->getJson('/api/v1/master-data/suppliers');
        $response->assertStatus(401);
    }

    public function test_user_without_legacy_tbl_user_mapping_is_rejected_with_403(): void
    {
        $unmappedUser = User::factory()->create([
            'username' => 'user_belum_terdaftar',
            'email' => 'unmapped@simrs.test',
        ]);

        Sanctum::actingAs($unmappedUser);

        $payload = [
            'name' => 'PT Farma Sejahtera',
            'phone' => '021-123456',
            'address' => 'Jakarta',
        ];

        $response = $this->postJson('/api/v1/master-data/suppliers', $payload);
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
        ]);
    }

    public function test_can_list_suppliers_from_legacy_ref_supplier(): void
    {
        DB::connection('legacy')->table('ref_supplier')->insert([
            ['id' => 1, 'nama' => 'KIMIA FARMA', 'telp' => '021-729011', 'alamat' => 'JL. SALEMBA', 'status' => 1, 'created_at' => now(), 'deleted_at' => null],
            ['id' => 2, 'nama' => 'INDO FARMA', 'telp' => '021-729012', 'alamat' => 'JL. MANGGA', 'status' => 1, 'created_at' => now(), 'deleted_at' => null],
            ['id' => 3, 'nama' => 'SUPPLIER HAPUS', 'telp' => '021-000000', 'alamat' => 'JL. HAPUS', 'status' => 0, 'created_at' => now(), 'deleted_at' => now()],
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->getJson('/api/v1/master-data/suppliers');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.name', 'INDO FARMA');
        $response->assertJsonPath('data.1.name', 'KIMIA FARMA');
    }

    public function test_can_search_suppliers_in_legacy_database(): void
    {
        DB::connection('legacy')->table('ref_supplier')->insert([
            ['id' => 1, 'nama' => 'KALBE FARMA', 'telp' => '021-487219', 'alamat' => 'JAKTIM', 'status' => 1, 'created_at' => now()],
            ['id' => 2, 'nama' => 'MERCK INDONESIA', 'telp' => '021-840008', 'alamat' => 'PASAR REBO', 'status' => 1, 'created_at' => now()],
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->getJson('/api/v1/master-data/suppliers?search=MERCK');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'MERCK INDONESIA');
    }

    public function test_can_show_single_supplier_from_legacy_ref_supplier(): void
    {
        DB::connection('legacy')->table('ref_supplier')->insert([
            'id' => 25,
            'nama' => 'PT PHAPROS TBK',
            'telp' => '024-760563',
            'alamat' => 'SEMARANG',
            'status' => 1,
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->getJson('/api/v1/master-data/suppliers/25');

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', 25);
        $response->assertJsonPath('data.name', 'PT PHAPROS TBK');
        $response->assertJsonPath('data.phone', '024-760563');
        $response->assertJsonPath('data.address', 'SEMARANG');
    }

    public function test_can_create_supplier_directly_in_legacy_ref_supplier(): void
    {
        Sanctum::actingAs($this->authUser);

        $payload = [
            'name' => 'PT BIO FARMA PERSERO',
            'phone' => '022-2033755',
            'address' => 'Jl Pasteur No 28 Bandung',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/master-data/suppliers', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'PT BIO FARMA PERSERO');
        $response->assertJsonPath('data.phone', '022-2033755');

        $newId = $response->json('data.id');

        // 1. Verify saved directly in simrs_legacy.ref_supplier
        $legacyRow = DB::connection('legacy')->table('ref_supplier')->where('id', $newId)->first();
        $this->assertNotNull($legacyRow);
        $this->assertEquals('PT BIO FARMA PERSERO', $legacyRow->nama);
        $this->assertEquals('022-2033755', $legacyRow->telp);
        $this->assertEquals('Jl Pasteur No 28 Bandung', $legacyRow->alamat);
        $this->assertEquals(1, (int) $legacyRow->status);
        $this->assertEquals(103, (int) $legacyRow->id_user);
        $this->assertNull($legacyRow->deleted_at);

        // 2. CRITICAL: Verify ZERO records duplicated to bigsirs_dev.suppliers
        $this->assertEquals(0, Supplier::count(), 'Zero records should exist in bigsirs_dev.suppliers');
    }

    public function test_can_update_supplier_in_legacy_ref_supplier(): void
    {
        DB::connection('legacy')->table('ref_supplier')->insert([
            'id' => 15,
            'nama' => 'PT ANUGRAH LAMA',
            'telp' => '021-111111',
            'alamat' => 'Alamat Lama',
            'status' => 1,
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $payload = [
            'name' => 'PT ANUGRAH BARU',
            'phone' => '021-222222',
            'address' => 'Alamat Baru',
            'is_active' => false,
        ];

        $response = $this->putJson('/api/v1/master-data/suppliers/15', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'PT ANUGRAH BARU');
        $response->assertJsonPath('data.phone', '021-222222');
        $response->assertJsonPath('data.is_active', false);

        // Check in legacy table
        $legacyRow = DB::connection('legacy')->table('ref_supplier')->where('id', 15)->first();
        $this->assertEquals('PT ANUGRAH BARU', $legacyRow->nama);
        $this->assertEquals('021-222222', $legacyRow->telp);
        $this->assertEquals('Alamat Baru', $legacyRow->alamat);
        $this->assertEquals(0, (int) $legacyRow->status);
        $this->assertEquals(103, (int) $legacyRow->id_user);
        $this->assertNotNull($legacyRow->updated_at);

        // Verify zero writes to bigsirs_dev
        $this->assertEquals(0, Supplier::count());
    }

    public function test_can_soft_delete_supplier_in_legacy_ref_supplier(): void
    {
        DB::connection('legacy')->table('ref_supplier')->insert([
            'id' => 88,
            'nama' => 'SUPPLIER AKAN DIHAPUS',
            'status' => 1,
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->deleteJson('/api/v1/master-data/suppliers/88');
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Supplier deleted successfully']);

        // Check legacy row is soft deleted
        $legacyRow = DB::connection('legacy')->table('ref_supplier')->where('id', 88)->first();
        $this->assertNotNull($legacyRow);
        $this->assertEquals(0, (int) $legacyRow->status);
        $this->assertNotNull($legacyRow->deleted_at);
        $this->assertEquals(103, (int) $legacyRow->id_user);

        // Verify it is excluded from listing
        $listResponse = $this->getJson('/api/v1/master-data/suppliers');
        $listResponse->assertJsonCount(0, 'data');
    }

    public function test_cannot_delete_supplier_referenced_in_purchase_invoices(): void
    {
        DB::connection('legacy')->table('ref_supplier')->insert([
            'id' => 99,
            'nama' => 'SUPPLIER BERFAKTUR',
            'status' => 1,
            'created_at' => now(),
        ]);

        DB::connection('legacy')->table('lgs_faktur')->insert([
            'id' => 501,
            'id_supplier' => 99,
            'no_faktur' => 'FAK-2026-001',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->deleteJson('/api/v1/master-data/suppliers/99');
        $response->assertStatus(409);
        $response->assertJson([
            'message' => 'Supplier tidak dapat dihapus karena sudah memiliki riwayat faktur pembelian.',
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
