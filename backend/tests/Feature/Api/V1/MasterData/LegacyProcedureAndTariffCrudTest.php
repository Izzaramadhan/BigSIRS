<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacyProcedureAndTariffCrudTest extends TestCase
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
            CREATE TABLE ref_kategori_tindakan (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(255) NOT NULL,
                deskripsi TEXT NULL,
                kode VARCHAR(10) NULL,
                status INTEGER DEFAULT 1,
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_komponen (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kode VARCHAR(50) NULL,
                nama VARCHAR(255) NULL,
                deskripsi VARCHAR(255) NULL,
                status CHAR(1) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_jenis_tarif (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(255) NOT NULL,
                deskripsi VARCHAR(255) NULL,
                kode VARCHAR(10) NULL,
                status INTEGER DEFAULT 1,
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE map_jenis_tarif_komponen (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_jenis_tarif INTEGER NULL,
                persen REAL NULL,
                id_komponen INTEGER NULL,
                status CHAR(1) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_poliklinik (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kode VARCHAR(20) NULL,
                nama VARCHAR(100) NULL,
                status CHAR(1) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_report_group (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(255) NULL,
                status CHAR(1) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_tarif_tindakan (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_jenis_tarif INTEGER NULL,
                id_kategori INTEGER NULL,
                nama VARCHAR(255) NULL,
                kode VARCHAR(255) NULL,
                harga INTEGER NULL,
                satuan VARCHAR(255) NULL,
                status CHAR(1) DEFAULT "1",
                id_user INTEGER NULL,
                icd9_code VARCHAR(255) NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE map_tarif_tindakan_poliklinik (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_poliklinik INTEGER NULL,
                id_ref_tarif_tindakan INTEGER NULL,
                status CHAR(1) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE map_tindakan_kelompok_laporan (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_kelompok_laporan INTEGER NULL,
                id_ref_tarif_tindakan INTEGER NULL,
                status CHAR(1) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE tbl_user (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) NULL,
                status CHAR(1) DEFAULT "1"
            )
        ');

        DB::connection('legacy')->table('tbl_user')->insert([
            'id' => 1,
            'username' => 'admin.legacy',
            'status' => '1',
        ]);

        $this->authUser = User::factory()->create([
            'username' => 'admin.legacy',
            'email' => 'admin.legacy@example.com',
        ]);
        Sanctum::actingAs($this->authUser);
    }

    protected function tearDown(): void
    {
        LegacyAccess::clearCache();
        parent::tearDown();
    }

    public function test_procedure_category_and_tariff_component_direct_crud(): void
    {
        // 1. Procedure Category CRUD
        $createCat = $this->postJson('/api/v1/master-data/procedure-categories', [
            'name' => 'Tindakan Gigi & Mulut',
            'description' => 'Kategori prosedur pelayanan gigi',
            'is_active' => true,
        ]);
        $createCat->assertStatus(201);
        $catId = $createCat->json('data.id');
        $this->assertNotNull($catId);
        $this->assertDatabaseHas('ref_kategori_tindakan', ['id' => $catId, 'nama' => 'Tindakan Gigi & Mulut'], 'legacy');

        $updateCat = $this->putJson("/api/v1/master-data/procedure-categories/{$catId}", [
            'name' => 'Tindakan Gigi & Bedah Mulut',
            'description' => 'Kategori prosedur dental dan bedah mulut',
            'is_active' => true,
        ]);
        $updateCat->assertStatus(200);
        $this->assertDatabaseHas('ref_kategori_tindakan', ['id' => $catId, 'nama' => 'Tindakan Gigi & Bedah Mulut'], 'legacy');

        $statusCat = $this->patchJson("/api/v1/master-data/procedure-categories/{$catId}/status", [
            'is_active' => false,
        ]);
        $statusCat->assertStatus(200);
        $this->assertDatabaseHas('ref_kategori_tindakan', ['id' => $catId, 'status' => 0], 'legacy');

        // 2. Tariff Component CRUD
        $createComp = $this->postJson('/api/v1/master-data/tariff-components', [
            'name' => 'Jasa Medis Dokter Spesialis',
            'description' => 'Komponen honorarium dokter spesialis',
            'is_active' => true,
        ]);
        $createComp->assertStatus(201);
        $compId = $createComp->json('data.id');
        $this->assertNotNull($compId);
        $this->assertDatabaseHas('ref_komponen', ['id' => $compId, 'nama' => 'Jasa Medis Dokter Spesialis'], 'legacy');

        $updateComp = $this->putJson("/api/v1/master-data/tariff-components/{$compId}", [
            'name' => 'Jasa Medis Spesialis Konsultan',
            'description' => 'Komponen honorarium konsultan',
            'is_active' => true,
        ]);
        $updateComp->assertStatus(200);
        $this->assertDatabaseHas('ref_komponen', ['id' => $compId, 'nama' => 'Jasa Medis Spesialis Konsultan'], 'legacy');

        $statusComp = $this->patchJson("/api/v1/master-data/tariff-components/{$compId}/status", [
            'is_active' => false,
        ]);
        $statusComp->assertStatus(200);
        $this->assertDatabaseHas('ref_komponen', ['id' => $compId, 'status' => '0'], 'legacy');

        // Clean up
        $this->deleteJson("/api/v1/master-data/tariff-components/{$compId}")->assertStatus(204);
        $this->deleteJson("/api/v1/master-data/procedure-categories/{$catId}")->assertStatus(204);
    }

    public function test_tariff_type_and_medical_procedure_direct_crud(): void
    {
        // Seed category, component, polyclinic, report group
        $catId = DB::connection('legacy')->table('ref_kategori_tindakan')->insertGetId([
            'nama' => 'Tindakan Rawat Jalan',
            'status' => 1,
            'id_user' => 1,
        ]);

        $compId = DB::connection('legacy')->table('ref_komponen')->insertGetId([
            'nama' => 'Jasa Pelayanan',
            'status' => '1',
            'id_user' => 1,
        ]);

        $poliId = DB::connection('legacy')->table('ref_poliklinik')->insertGetId([
            'nama' => 'Poli Penyakit Dalam',
            'kode' => 'PD-01',
            'status' => '1',
            'id_user' => 1,
        ]);

        $reportGroupId = DB::connection('legacy')->table('ref_report_group')->insertGetId([
            'nama' => 'Kelompok Laporan Rawat Jalan',
            'status' => '1',
            'id_user' => 1,
        ]);

        // 1. Tariff Type CRUD
        $createTariffType = $this->postJson('/api/v1/master-data/tariff-types', [
            'name' => 'Tarif Rawat Jalan Reguler',
            'code' => 'TRF-RJ',
            'description' => 'Tarif poli reguler',
            'is_active' => true,
            'components' => [
                [
                    'tariff_component_id' => $compId,
                    'percentage' => 100,
                ],
            ],
        ]);
        $createTariffType->assertStatus(201);
        $tariffTypeId = $createTariffType->json('data.id');
        $this->assertNotNull($tariffTypeId);
        $this->assertDatabaseHas('ref_jenis_tarif', ['id' => $tariffTypeId, 'kode' => 'TRF-RJ'], 'legacy');
        $this->assertDatabaseHas('map_jenis_tarif_komponen', ['id_jenis_tarif' => $tariffTypeId, 'id_komponen' => $compId], 'legacy');

        // 2. Medical Procedure CRUD
        $createProc = $this->postJson('/api/v1/master-data/procedures', [
            'code' => 'TND-001',
            'name' => 'Konsultasi Dokter Spesialis',
            'procedure_category_id' => $catId,
            'is_visible' => true,
            'polyclinics' => [$poliId],
            'report_groups' => [$reportGroupId],
            'tariffs' => [
                [
                    'tariff_type_id' => $tariffTypeId,
                    'components' => [
                        [
                            'tariff_component_id' => $compId,
                            'amount' => 150000,
                        ],
                    ],
                ],
            ],
        ]);
        $createProc->assertStatus(201);
        $procId = $createProc->json('data.id');
        $this->assertNotNull($procId);
        $this->assertDatabaseHas('ref_tarif_tindakan', [
            'id' => $procId,
            'kode' => 'TND-001',
            'nama' => 'Konsultasi Dokter Spesialis',
            'harga' => 150000,
        ], 'legacy');
        $this->assertDatabaseHas('map_tarif_tindakan_poliklinik', [
            'id_ref_tarif_tindakan' => $procId,
            'id_poliklinik' => $poliId,
        ], 'legacy');
        $this->assertDatabaseHas('map_tindakan_kelompok_laporan', [
            'id_ref_tarif_tindakan' => $procId,
            'id_kelompok_laporan' => $reportGroupId,
        ], 'legacy');

        // 3. Update Medical Procedure
        $updateProc = $this->putJson("/api/v1/master-data/procedures/{$procId}", [
            'code' => 'TND-001-REV',
            'name' => 'Konsultasi Dokter Spesialis Paripurna',
            'procedure_category_id' => $catId,
            'is_visible' => true,
            'polyclinics' => [$poliId],
            'report_groups' => [$reportGroupId],
            'tariffs' => [
                [
                    'tariff_type_id' => $tariffTypeId,
                    'components' => [
                        [
                            'tariff_component_id' => $compId,
                            'amount' => 175000,
                        ],
                    ],
                ],
            ],
        ]);
        $updateProc->assertStatus(200);
        $this->assertDatabaseHas('ref_tarif_tindakan', [
            'id' => $procId,
            'kode' => 'TND-001-REV',
            'nama' => 'Konsultasi Dokter Spesialis Paripurna',
            'harga' => 175000,
        ], 'legacy');

        // 4. Update visibility
        $visResponse = $this->patchJson("/api/v1/master-data/procedures/{$procId}/visibility", [
            'is_visible' => false,
        ]);
        $visResponse->assertStatus(200);
        $this->assertDatabaseHas('ref_tarif_tindakan', [
            'id' => $procId,
            'status' => '0',
        ], 'legacy');

        // 5. Delete procedure
        $this->deleteJson("/api/v1/master-data/procedures/{$procId}")->assertStatus(204);
        $this->assertDatabaseMissing('map_tarif_tindakan_poliklinik', ['id_ref_tarif_tindakan' => $procId], 'legacy');
        $this->assertDatabaseMissing('map_tindakan_kelompok_laporan', ['id_ref_tarif_tindakan' => $procId], 'legacy');

        // 6. Delete tariff type
        $this->deleteJson("/api/v1/master-data/tariff-types/{$tariffTypeId}")->assertStatus(204);
        $this->assertDatabaseMissing('map_jenis_tarif_komponen', ['id_jenis_tarif' => $tariffTypeId], 'legacy');
    }
}
