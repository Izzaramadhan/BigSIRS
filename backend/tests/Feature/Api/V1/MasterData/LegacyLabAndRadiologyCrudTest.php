<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacyLabAndRadiologyCrudTest extends TestCase
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
            CREATE TABLE ref_kategori_lab (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(255) NULL,
                deskripsi TEXT NULL,
                tipe VARCHAR(50) NULL,
                status CHAR(1) DEFAULT "1",
                id_user INTEGER NULL,
                loinc_code TEXT NULL,
                loinc_url TEXT NULL,
                snomed_code TEXT NULL,
                snomed_url TEXT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_item_lab (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(255) NULL,
                standar_normal VARCHAR(255) NULL,
                satuan VARCHAR(255) NULL,
                status CHAR(1) DEFAULT "1",
                id_user VARCHAR(25) NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_grup_lab (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(50) NULL,
                id_kategori_lab INTEGER NULL,
                deskripsi TEXT NULL,
                harga REAL NULL,
                diskon REAL NULL,
                status CHAR(1) DEFAULT "1",
                id_user INTEGER NULL,
                harga_jaspel REAL NULL,
                loinc_code TEXT NULL,
                loinc_url TEXT NULL,
                snomed_code TEXT NULL,
                snomed_url TEXT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE map_grup_item_lab (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_grup INTEGER NULL,
                id_item INTEGER NULL,
                status CHAR(1) DEFAULT "1",
                created_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_kategori_rad (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(255) NULL,
                deskripsi TEXT NULL,
                status CHAR(1) DEFAULT "1",
                id_user INTEGER NULL,
                loinc_code TEXT NULL,
                loinc_url TEXT NULL,
                snomed_code TEXT NULL,
                snomed_url TEXT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_tipe_rad (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(255) NULL,
                deskripsi VARCHAR(255) NULL,
                status INTEGER DEFAULT 1,
                id_user INTEGER DEFAULT 1,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_kelompok_item_rad (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(255) NULL,
                deskripsi VARCHAR(255) NULL,
                status CHAR(1) DEFAULT "1",
                id_user INTEGER DEFAULT 1,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_jenis_kegiatan (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(255) NULL,
                status CHAR(1) DEFAULT "1",
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_grup_rad (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_kategori_rad INTEGER NULL,
                id_tipe_rad INTEGER NULL,
                nama VARCHAR(255) NULL,
                harga INTEGER NULL,
                harga_interpretasi INTEGER NULL,
                deskripsi VARCHAR(255) NULL,
                status INTEGER DEFAULT 1,
                id_user INTEGER DEFAULT 1,
                id_ref_jenis_kegiatan INTEGER NULL,
                loinc_code TEXT NULL,
                loinc_url TEXT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE map_grup_kelompok_rad (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_grup_rad INTEGER NULL,
                id_kelompok_rad INTEGER NULL,
                status INTEGER DEFAULT 1,
                id_user INTEGER DEFAULT 1,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_item_rad (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_kelompok_item_rad INTEGER NULL,
                nama VARCHAR(255) NULL,
                status INTEGER DEFAULT 1,
                id_user INTEGER DEFAULT 1,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        $this->authUser = User::factory()->create();
        Sanctum::actingAs($this->authUser);
    }

    public function test_can_perform_direct_crud_on_laboratory_category(): void
    {
        // 1. Create
        $response = $this->postJson('/api/v1/master-data/laboratory-categories', [
            'name' => 'Hematologi Khusus',
            'description' => 'Pemeriksaan hematologi lengkap',
            'type' => 'lab klinik',
            'is_active' => true,
        ]);
        $response->assertCreated();
        $id = $response->json('data.id');

        $this->assertDatabaseHas('ref_kategori_lab', [
            'id' => $id,
            'nama' => 'Hematologi Khusus',
            'tipe' => 'lab klinik',
            'status' => '1',
        ], 'legacy');

        // 2. Read
        $showRes = $this->getJson("/api/v1/master-data/laboratory-categories/{$id}");
        $showRes->assertOk()
            ->assertJsonPath('data.name', 'Hematologi Khusus')
            ->assertJsonPath('data.type', 'lab klinik');

        // 3. Update
        $updateRes = $this->putJson("/api/v1/master-data/laboratory-categories/{$id}", [
            'name' => 'Hematologi Rutin & Khusus',
            'description' => 'Deskripsi diperbarui',
            'type' => 'lab klinik',
        ]);
        $updateRes->assertOk()
            ->assertJsonPath('data.name', 'Hematologi Rutin & Khusus');

        $this->assertDatabaseHas('ref_kategori_lab', [
            'id' => $id,
            'nama' => 'Hematologi Rutin & Khusus',
        ], 'legacy');

        // 4. Update status
        $statusRes = $this->patchJson("/api/v1/master-data/laboratory-categories/{$id}/status", [
            'is_active' => false,
        ]);
        $statusRes->assertOk();
        $this->assertDatabaseHas('ref_kategori_lab', [
            'id' => $id,
            'status' => '0',
        ], 'legacy');

        // 5. Delete (soft-delete)
        $delRes = $this->deleteJson("/api/v1/master-data/laboratory-categories/{$id}");
        $delRes->assertNoContent();
        $this->assertSoftDeleted('ref_kategori_lab', ['id' => $id], 'legacy');
    }

    public function test_can_perform_direct_crud_on_laboratory_item_and_group(): void
    {
        // 1. Create Lab Category
        $catId = DB::connection('legacy')->table('ref_kategori_lab')->insertGetId([
            'nama' => 'Kimia Darah',
            'status' => '1',
            'created_at' => now(),
        ]);

        // 2. Create Lab Item
        $itemRes = $this->postJson('/api/v1/master-data/laboratory-items', [
            'name' => 'Glukosa Sewaktu',
            'reference_value' => '70 - 140',
            'unit' => 'mg/dL',
            'is_active' => true,
        ]);
        $itemRes->assertCreated();
        $itemId = $itemRes->json('data.id');

        $this->assertDatabaseHas('ref_item_lab', [
            'id' => $itemId,
            'nama' => 'Glukosa Sewaktu',
            'standar_normal' => '70 - 140',
            'satuan' => 'mg/dL',
        ], 'legacy');

        // 3. Create Lab Group with item mapping
        $groupRes = $this->postJson('/api/v1/master-data/laboratory-groups', [
            'laboratory_category_id' => $catId,
            'name' => 'Paket Diabetes Melitus',
            'price' => 50000,
            'harga_jaspel' => 15000,
            'is_active' => true,
            'laboratory_item_ids' => [$itemId],
        ]);
        $groupRes->assertCreated();
        $groupId = $groupRes->json('data.id');

        $this->assertDatabaseHas('ref_grup_lab', [
            'id' => $groupId,
            'nama' => 'Paket Diabetes Melitus',
            'harga' => 50000,
        ], 'legacy');

        $this->assertDatabaseHas('map_grup_item_lab', [
            'id_grup' => $groupId,
            'id_item' => $itemId,
            'status' => '1',
        ], 'legacy');

        // 4. Update Lab Group
        $updateGroupRes = $this->putJson("/api/v1/master-data/laboratory-groups/{$groupId}", [
            'laboratory_category_id' => $catId,
            'name' => 'Paket Diabetes Lengkap',
            'price' => 75000,
            'laboratory_item_ids' => [$itemId],
        ]);
        $updateGroupRes->assertOk();
        $this->assertDatabaseHas('ref_grup_lab', [
            'id' => $groupId,
            'nama' => 'Paket Diabetes Lengkap',
            'harga' => 75000,
        ], 'legacy');

        // 5. Delete Lab Group
        $delGroupRes = $this->deleteJson("/api/v1/master-data/laboratory-groups/{$groupId}");
        $delGroupRes->assertNoContent();
        $this->assertSoftDeleted('ref_grup_lab', ['id' => $groupId], 'legacy');

        // 6. Update & Delete Lab Item
        $updateItemRes = $this->putJson("/api/v1/master-data/laboratory-items/{$itemId}", [
            'name' => 'Glukosa Puasa & Sewaktu',
            'reference_value' => '70 - 110',
            'unit' => 'mg/dL',
            'is_active' => true,
        ]);
        $updateItemRes->assertOk();
        $this->assertDatabaseHas('ref_item_lab', [
            'id' => $itemId,
            'nama' => 'Glukosa Puasa & Sewaktu',
            'standar_normal' => '70 - 110',
        ], 'legacy');

        $delItemRes = $this->deleteJson("/api/v1/master-data/laboratory-items/{$itemId}");
        $delItemRes->assertOk();
        $this->assertSoftDeleted('ref_item_lab', ['id' => $itemId], 'legacy');
    }

    public function test_can_perform_direct_crud_on_radiology_modules(): void
    {
        // 1. Radiology Category CRUD
        $catRes = $this->postJson('/api/v1/master-data/radiology-categories', [
            'name' => 'Foto Thorax',
            'description' => 'Radiologi dada',
            'is_active' => true,
        ]);
        $catRes->assertCreated();
        $catId = $catRes->json('data.id');

        $this->assertDatabaseHas('ref_kategori_rad', [
            'id' => $catId,
            'nama' => 'Foto Thorax',
            'status' => '1',
        ], 'legacy');

        // 2. Radiology Type CRUD
        $typeRes = $this->postJson('/api/v1/master-data/radiology-types', [
            'name' => 'Digital X-Ray',
            'description' => 'X-Ray digital resolusi tinggi',
            'is_active' => true,
        ]);
        $typeRes->assertCreated();
        $typeId = $typeRes->json('data.id');

        $this->assertDatabaseHas('ref_tipe_rad', [
            'id' => $typeId,
            'nama' => 'Digital X-Ray',
            'status' => 1,
        ], 'legacy');

        // 3. Radiology Item Group CRUD
        $itemGroupRes = $this->postJson('/api/v1/master-data/radiology-item-groups', [
            'name' => 'Thorax PA/AP',
            'description' => 'Proyeksi PA dan AP',
            'is_active' => true,
        ]);
        $itemGroupRes->assertCreated();
        $itemGroupId = $itemGroupRes->json('data.id');

        $this->assertDatabaseHas('ref_kelompok_item_rad', [
            'id' => $itemGroupId,
            'nama' => 'Thorax PA/AP',
        ], 'legacy');

        // 4. Radiology Item CRUD
        $itemRes = $this->postJson('/api/v1/master-data/radiology-items', [
            'name' => 'Film 35x43 Thorax',
            'radiology_item_group_id' => $itemGroupId,
            'is_active' => true,
        ]);
        $itemRes->assertCreated();
        $itemId = $itemRes->json('data.id');

        $this->assertDatabaseHas('ref_item_rad', [
            'id' => $itemId,
            'nama' => 'Film 35x43 Thorax',
            'id_kelompok_item_rad' => $itemGroupId,
            'status' => 1,
        ], 'legacy');

        // Insert dummy jenis kegiatan in legacy
        $actId = DB::connection('legacy')->table('ref_jenis_kegiatan')->insertGetId([
            'nama' => 'Pelayanan Radiologi Konvensional',
            'status' => '1',
        ]);

        // 5. Radiology Group CRUD
        $groupRes = $this->postJson('/api/v1/master-data/radiology-groups', [
            'name' => 'Pemeriksaan Rontgen Thorax PA Dewasa',
            'radiology_category_id' => $catId,
            'radiology_type_id' => $typeId,
            'activity_type_id' => $actId,
            'price' => 125000,
            'interpretation_price' => 35000,
            'is_active' => true,
            'radiology_item_group_ids' => [$itemGroupId],
        ]);
        $groupRes->assertCreated();
        $groupId = $groupRes->json('data.id');

        $this->assertDatabaseHas('ref_grup_rad', [
            'id' => $groupId,
            'nama' => 'Pemeriksaan Rontgen Thorax PA Dewasa',
            'harga' => 125000,
            'harga_interpretasi' => 35000,
        ], 'legacy');

        $this->assertDatabaseHas('map_grup_kelompok_rad', [
            'id_grup_rad' => $groupId,
            'id_kelompok_rad' => $itemGroupId,
            'status' => 1,
        ], 'legacy');

        // Update Radiology Group
        $updateGroupRes = $this->putJson("/api/v1/master-data/radiology-groups/{$groupId}", [
            'name' => 'Pemeriksaan Rontgen Thorax PA/AP Dewasa Update',
            'radiology_category_id' => $catId,
            'radiology_type_id' => $typeId,
            'activity_type_id' => $actId,
            'price' => 150000,
            'interpretation_price' => 40000,
            'radiology_item_group_ids' => [$itemGroupId],
        ]);
        $updateGroupRes->assertOk();
        $this->assertDatabaseHas('ref_grup_rad', [
            'id' => $groupId,
            'nama' => 'Pemeriksaan Rontgen Thorax PA/AP Dewasa Update',
            'harga' => 150000,
        ], 'legacy');

        // Delete Radiology Group
        $delGroupRes = $this->deleteJson("/api/v1/master-data/radiology-groups/{$groupId}");
        $delGroupRes->assertNoContent();
        $this->assertSoftDeleted('ref_grup_rad', ['id' => $groupId], 'legacy');

        // 6. Update & Delete Radiology Item
        $updateItemRes = $this->putJson("/api/v1/master-data/radiology-items/{$itemId}", [
            'name' => 'Film 35x43 Thorax Digital Update',
            'radiology_item_group_id' => $itemGroupId,
        ]);
        $updateItemRes->assertOk();
        $this->assertDatabaseHas('ref_item_rad', [
            'id' => $itemId,
            'nama' => 'Film 35x43 Thorax Digital Update',
        ], 'legacy');

        $delItemRes = $this->deleteJson("/api/v1/master-data/radiology-items/{$itemId}");
        $delItemRes->assertNoContent();
        $this->assertSoftDeleted('ref_item_rad', ['id' => $itemId], 'legacy');

        // 7. Update & Delete Radiology Item Group
        $updateItemGroupRes = $this->putJson("/api/v1/master-data/radiology-item-groups/{$itemGroupId}", [
            'name' => 'Thorax PA/AP/Lateral',
        ]);
        $updateItemGroupRes->assertOk();
        $this->assertDatabaseHas('ref_kelompok_item_rad', [
            'id' => $itemGroupId,
            'nama' => 'Thorax PA/AP/Lateral',
        ], 'legacy');

        $delItemGroupRes = $this->deleteJson("/api/v1/master-data/radiology-item-groups/{$itemGroupId}");
        $delItemGroupRes->assertOk();
        $this->assertSoftDeleted('ref_kelompok_item_rad', ['id' => $itemGroupId], 'legacy');

        // 8. Update & Delete Radiology Type
        $updateTypeRes = $this->putJson("/api/v1/master-data/radiology-types/{$typeId}", [
            'name' => 'Digital X-Ray DR Update',
        ]);
        $updateTypeRes->assertOk();
        $this->assertDatabaseHas('ref_tipe_rad', [
            'id' => $typeId,
            'nama' => 'Digital X-Ray DR Update',
        ], 'legacy');

        $delTypeRes = $this->deleteJson("/api/v1/master-data/radiology-types/{$typeId}");
        $delTypeRes->assertNoContent();
        $this->assertSoftDeleted('ref_tipe_rad', ['id' => $typeId], 'legacy');

        // 9. Update & Delete Radiology Category
        $updateCatRes = $this->putJson("/api/v1/master-data/radiology-categories/{$catId}", [
            'name' => 'Foto Thorax & Abdomen',
        ]);
        $updateCatRes->assertOk();
        $this->assertDatabaseHas('ref_kategori_rad', [
            'id' => $catId,
            'nama' => 'Foto Thorax & Abdomen',
        ], 'legacy');

        $delCatRes = $this->deleteJson("/api/v1/master-data/radiology-categories/{$catId}");
        $delCatRes->assertNoContent();
        $this->assertSoftDeleted('ref_kategori_rad', ['id' => $catId], 'legacy');
    }
}
