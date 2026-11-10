<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Legacy\LegacyIcd10;
use App\Models\Legacy\LegacyMedicinePackage;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacyMedicinePackageCrudTest extends TestCase
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

        // 1. Create legacy ref_paket_obat table (header)
        DB::connection('legacy')->statement('
            CREATE TABLE ref_paket_obat (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(100) NOT NULL,
                harga INTEGER DEFAULT 0,
                jumlah INTEGER NULL,
                deskripsi TEXT NULL,
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME,
                id_user VARCHAR(10) NULL
            )
        ');

        // 2. Create legacy map_paket_obat table (items)
        DB::connection('legacy')->statement('
            CREATE TABLE map_paket_obat (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_ref_paket_obat INTEGER NOT NULL,
                id_ref_obat INTEGER NOT NULL,
                jumlah INTEGER DEFAULT 1,
                sub_total INTEGER DEFAULT 0,
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME,
                id_user VARCHAR(10) NULL
            )
        ');

        // 3. Create legacy ref_obat table
        DB::connection('legacy')->statement('
            CREATE TABLE ref_obat (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(255) NOT NULL,
                harga_jual INTEGER DEFAULT 1000,
                status VARCHAR(1) DEFAULT "1",
                deleted_at DATETIME NULL
            )
        ');

        // 4. Create legacy lgs_log_stok table to verify zero stock touch
        DB::connection('legacy')->statement('
            CREATE TABLE lgs_log_stok (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_obat INTEGER NULL,
                stok_keluar INTEGER NULL,
                stok_masuk INTEGER NULL
            )
        ');

        // 5. Create legacy tbl_user table for identity mapping
        DB::connection('legacy')->statement('
            CREATE TABLE tbl_user (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(40) NOT NULL,
                nama VARCHAR(100),
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                id_user INTEGER NULL
            )
        ');

        // Insert mapped user in legacy tbl_user
        DB::connection('legacy')->table('tbl_user')->insert([
            'id' => 99,
            'username' => 'apoteker_user',
            'nama' => 'Apoteker User',
            'status' => '1',
            'created_at' => now(),
        ]);

        $this->authUser = User::factory()->create([
            'username' => 'apoteker_user',
            'name' => 'Apoteker User',
        ]);

        // Insert reference medicines
        DB::connection('legacy')->table('ref_obat')->insert([
            ['id' => 101, 'nama' => 'Amoxicillin 500mg', 'harga_jual' => 2000, 'status' => '1'],
            ['id' => 102, 'nama' => 'Paracetamol 500mg', 'harga_jual' => 1000, 'status' => '1'],
        ]);
    }

    protected function tearDown(): void
    {
        LegacyAccess::clearCache();
        parent::tearDown();
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/master-data/medicine-packages')->assertStatus(401);
        $this->postJson('/api/v1/master-data/medicine-packages', ['name' => 'Paket A'])->assertStatus(401);
        $this->putJson('/api/v1/master-data/medicine-packages/1', ['name' => 'Paket A'])->assertStatus(401);
        $this->deleteJson('/api/v1/master-data/medicine-packages/1')->assertStatus(401);
    }

    public function test_unmapped_legacy_user_is_forbidden_on_write(): void
    {
        $unmappedUser = User::factory()->create([
            'username' => 'unmapped_user',
        ]);
        Sanctum::actingAs($unmappedUser);

        $id = DB::connection('legacy')->table('ref_paket_obat')->insertGetId([
            'nama' => 'Existing Package',
            'harga' => 10000,
            'jumlah' => 1,
            'status' => '1',
            'created_at' => now(),
        ]);

        $payload = [
            'name' => 'New Package',
            'price' => 15000,
            'quantity' => 2,
            'items' => [
                [
                    'medicine_id' => 101,
                    'quantity' => 5,
                    'unit_price' => 2000,
                    'subtotal' => 10000,
                ],
            ],
        ];

        $this->postJson('/api/v1/master-data/medicine-packages', $payload)->assertStatus(403);
        $this->putJson("/api/v1/master-data/medicine-packages/{$id}", $payload)->assertStatus(403);
        $this->deleteJson("/api/v1/master-data/medicine-packages/{$id}")->assertStatus(403);
    }

    public function test_can_list_and_search_medicine_packages_directly_from_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        $p1 = DB::connection('legacy')->table('ref_paket_obat')->insertGetId([
            'nama' => 'Paket Flu Dewasa',
            'harga' => 25000,
            'jumlah' => 1,
            'status' => '1',
            'created_at' => now(),
        ]);
        $p2 = DB::connection('legacy')->table('ref_paket_obat')->insertGetId([
            'nama' => 'Paket Vitamin Imun',
            'harga' => 50000,
            'jumlah' => 1,
            'status' => '1',
            'created_at' => now(),
        ]);
        $p3 = DB::connection('legacy')->table('ref_paket_obat')->insertGetId([
            'nama' => 'Paket Anak Demam',
            'harga' => 15000,
            'jumlah' => 1,
            'status' => '0',
            'created_at' => now(),
        ]);

        DB::connection('legacy')->table('map_paket_obat')->insert([
            ['id_ref_paket_obat' => $p1, 'id_ref_obat' => 101, 'jumlah' => 10, 'sub_total' => 20000, 'status' => '1'],
            ['id_ref_paket_obat' => $p2, 'id_ref_obat' => 102, 'jumlah' => 10, 'sub_total' => 10000, 'status' => '1'],
        ]);

        $response = $this->getJson('/api/v1/master-data/medicine-packages');
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        $searchResponse = $this->getJson('/api/v1/master-data/medicine-packages?search=Vitamin');
        $searchResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Paket Vitamin Imun');

        $activeResponse = $this->getJson('/api/v1/master-data/medicine-packages?is_active=1');
        $activeResponse->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_show_single_medicine_package_directly_from_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_paket_obat')->insertGetId([
            'nama' => 'Paket Khusus ISPA',
            'harga' => 35000,
            'jumlah' => 2,
            'deskripsi' => 'Paket perawatan ISPA',
            'status' => '1',
            'created_at' => now(),
        ]);

        DB::connection('legacy')->table('map_paket_obat')->insert([
            'id_ref_paket_obat' => $id,
            'id_ref_obat' => 101,
            'jumlah' => 10,
            'sub_total' => 20000,
            'status' => '1',
        ]);

        $response = $this->getJson("/api/v1/master-data/medicine-packages/{$id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', 'Paket Khusus ISPA')
            ->assertJsonPath('data.price', 35000)
            ->assertJsonPath('data.quantity', 2)
            ->assertJsonPath('data.description', 'Paket perawatan ISPA')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.medicine_id', 101);
    }

    public function test_can_create_medicine_package_atomically_in_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        $payload = [
            'name' => 'Paket Antibiotik Lengkap',
            'price' => 30000,
            'quantity' => 1,
            'description' => 'Paket resep antibiotik',
            'is_active' => true,
            'items' => [
                [
                    'medicine_id' => 101,
                    'quantity' => 10,
                    'unit_price' => 2000,
                    'subtotal' => 20000,
                ],
                [
                    'medicine_id' => 102,
                    'quantity' => 10,
                    'unit_price' => 1000,
                    'subtotal' => 10000,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/master-data/medicine-packages', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Paket Antibiotik Lengkap')
            ->assertJsonPath('data.price', 30000)
            ->assertJsonCount(2, 'data.items');

        $createdId = $response->json('data.id');

        // Verify ref_paket_obat in legacy
        $header = DB::connection('legacy')->table('ref_paket_obat')->where('id', $createdId)->first();
        $this->assertNotNull($header);
        $this->assertEquals('Paket Antibiotik Lengkap', $header->nama);
        $this->assertEquals(30000, $header->harga);
        $this->assertEquals('1', $header->status);
        $this->assertEquals('99', $header->id_user);

        // Verify map_paket_obat in legacy
        $items = DB::connection('legacy')->table('map_paket_obat')->where('id_ref_paket_obat', $createdId)->get();
        $this->assertCount(2, $items);
        $this->assertEquals(101, $items[0]->id_ref_obat);
        $this->assertEquals(10, $items[0]->jumlah);
        $this->assertEquals(20000, $items[0]->sub_total);
        $this->assertEquals('99', $items[0]->id_user);

        // Ensure zero records in bigsirs_dev duplicate tables
        $this->assertDatabaseCount('medicine_packages', 0);
        $this->assertDatabaseCount('medicine_package_items', 0);

        // Ensure zero stock modifications (lgs_log_stok remains untouched)
        $this->assertEquals(0, DB::connection('legacy')->table('lgs_log_stok')->count());
    }

    public function test_can_update_medicine_package_atomically_in_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_paket_obat')->insertGetId([
            'nama' => 'Old Package',
            'harga' => 10000,
            'jumlah' => 1,
            'status' => '1',
            'created_at' => now(),
            'id_user' => '99',
        ]);

        DB::connection('legacy')->table('map_paket_obat')->insert([
            'id_ref_paket_obat' => $id,
            'id_ref_obat' => 102,
            'jumlah' => 10,
            'sub_total' => 10000,
            'status' => '1',
            'id_user' => '99',
        ]);

        $updatePayload = [
            'name' => 'Updated Package Title',
            'price' => 20000,
            'quantity' => 2,
            'description' => 'Updated description',
            'is_active' => false,
            'items' => [
                [
                    'medicine_id' => 101,
                    'quantity' => 10,
                    'unit_price' => 2000,
                    'subtotal' => 20000,
                ],
            ],
        ];

        $response = $this->putJson("/api/v1/master-data/medicine-packages/{$id}", $updatePayload);
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Package Title')
            ->assertJsonPath('data.price', 20000)
            ->assertJsonPath('data.is_active', false)
            ->assertJsonCount(1, 'data.items');

        $header = DB::connection('legacy')->table('ref_paket_obat')->where('id', $id)->first();
        $this->assertEquals('Updated Package Title', $header->nama);
        $this->assertEquals(20000, $header->harga);
        $this->assertEquals('0', $header->status);
        $this->assertEquals('99', $header->id_user);

        $items = DB::connection('legacy')->table('map_paket_obat')->where('id_ref_paket_obat', $id)->get();
        $this->assertCount(1, $items);
        $this->assertEquals(101, $items[0]->id_ref_obat);
        $this->assertEquals(20000, $items[0]->sub_total);

        // Ensure zero records in bigsirs_dev duplicate tables
        $this->assertDatabaseCount('medicine_packages', 0);
        $this->assertDatabaseCount('medicine_package_items', 0);

        // Ensure zero stock modifications
        $this->assertEquals(0, DB::connection('legacy')->table('lgs_log_stok')->count());
    }

    public function test_can_soft_delete_medicine_package_and_cascade_to_items(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_paket_obat')->insertGetId([
            'nama' => 'Deletable Package',
            'harga' => 10000,
            'status' => '1',
            'created_at' => now(),
            'id_user' => '99',
        ]);

        DB::connection('legacy')->table('map_paket_obat')->insert([
            'id_ref_paket_obat' => $id,
            'id_ref_obat' => 101,
            'jumlah' => 5,
            'sub_total' => 10000,
            'status' => '1',
            'id_user' => '99',
        ]);

        $response = $this->deleteJson("/api/v1/master-data/medicine-packages/{$id}");
        $response->assertStatus(204);

        $header = DB::connection('legacy')->table('ref_paket_obat')->where('id', $id)->first();
        $this->assertNotNull($header->deleted_at);
        $this->assertEquals('0', $header->status);
        $this->assertEquals('99', $header->id_user);

        $item = DB::connection('legacy')->table('map_paket_obat')->where('id_ref_paket_obat', $id)->first();
        $this->assertNotNull($item->deleted_at);
        $this->assertEquals('0', $item->status);
        $this->assertEquals('99', $item->id_user);

        // Ensure zero stock modifications
        $this->assertEquals(0, DB::connection('legacy')->table('lgs_log_stok')->count());
    }

    public function test_other_legacy_models_remain_read_only(): void
    {
        $spec = new \App\Models\Legacy\LegacySpecialization();
        $this->assertTrue($spec->isReadOnly());

        $this->expectException(\RuntimeException::class);
        $spec->save();
    }
}

