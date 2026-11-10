<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacyProcedurePackagesAndMappingsCrudTest extends TestCase
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

        // 1. ref_report_group
        DB::connection('legacy')->statement('
            CREATE TABLE ref_report_group (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(255) NULL,
                deskripsi VARCHAR(255) NULL,
                tipe VARCHAR(255) NULL,
                type VARCHAR(30) NULL,
                status CHAR(1) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        // 2. ref_paket_tindakan & map_paket_tindakan
        DB::connection('legacy')->statement('
            CREATE TABLE ref_paket_tindakan (
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
            CREATE TABLE map_paket_tindakan (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_ref_paket_tindakan INTEGER NULL,
                id_ref_tarif_tindakan INTEGER NULL,
                status CHAR(1) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        // 3. ref_tarif_tindakan
        DB::connection('legacy')->statement('
            CREATE TABLE ref_tarif_tindakan (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kode VARCHAR(255) NULL,
                nama VARCHAR(255) NULL,
                harga INTEGER DEFAULT 0,
                satuan VARCHAR(255) NULL,
                id_jenis_tarif INTEGER NULL,
                id_kategori INTEGER NULL,
                status CHAR(1) DEFAULT "1",
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                id_user INTEGER NULL
            )
        ');

        // 4. map_ref_tindakan_user
        DB::connection('legacy')->statement('
            CREATE TABLE map_ref_tindakan_user (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_ref_tindakan INTEGER NOT NULL,
                id_aktor INTEGER NOT NULL,
                status INTEGER DEFAULT 1,
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        // 5. ref_pegawai & master_person_index & tbl_user
        DB::connection('legacy')->statement('
            CREATE TABLE master_person_index (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(150) NOT NULL,
                no_ktp VARCHAR(50) NULL,
                no_hp VARCHAR(50) NULL,
                status INTEGER DEFAULT 1,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_pegawai (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_mpi INTEGER NULL,
                nip VARCHAR(50) NULL,
                nama VARCHAR(150) NULL,
                id_jabatan INTEGER NULL,
                status INTEGER DEFAULT 1,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE tbl_user (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) NOT NULL,
                id_pegawai INTEGER NULL,
                status VARCHAR(1) DEFAULT "1",
                deleted_at DATETIME NULL
            )
        ');

        // Seed sample master data in legacy
        DB::connection('legacy')->table('master_person_index')->insert([
            ['id' => 1, 'nama' => 'dr. Budi Santoso, Sp.A', 'status' => 1],
            ['id' => 2, 'nama' => 'dr. Siti Rahma, Sp.PD', 'status' => 1],
        ]);

        DB::connection('legacy')->table('ref_pegawai')->insert([
            ['id' => 1, 'id_mpi' => 1, 'nip' => 'PEG-001', 'nama' => 'dr. Budi Santoso, Sp.A', 'status' => 1],
            ['id' => 2, 'id_mpi' => 2, 'nip' => 'PEG-002', 'nama' => 'dr. Siti Rahma, Sp.PD', 'status' => 1],
        ]);

        DB::connection('legacy')->table('ref_tarif_tindakan')->insert([
            ['id' => 1, 'kode' => 'TND01', 'nama' => 'Pemeriksaan Dokter Spesialis', 'harga' => 150000, 'status' => '1'],
            ['id' => 2, 'kode' => 'TND02', 'nama' => 'Nebulisasi Anak', 'harga' => 75000, 'status' => '1'],
        ]);

        DB::connection('legacy')->table('tbl_user')->insert([
            'id' => 101,
            'username' => 'admin.legacy',
            'id_pegawai' => 1,
            'status' => '1',
        ]);

        $this->authUser = User::factory()->create([
            'username' => 'admin.legacy',
            'email' => 'admin.legacy@example.com',
        ]);

        Sanctum::actingAs($this->authUser);
    }

    /**
     * Test 1: Direct CRUD for ReportGroup (ref_report_group).
     */
    public function test_can_crud_report_groups_directly_in_legacy(): void
    {
        // 1. Create
        $response = $this->postJson('/api/v1/master-data/report-groups', [
            'name' => 'Laporan Gigi RL3.3',
            'description' => 'Grup laporan tindakan gigi',
            'type' => 'RL3.3',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Laporan Gigi RL3.3');

        $rgId = $response->json('data.id');

        $this->assertDatabaseHas('ref_report_group', [
            'id' => $rgId,
            'nama' => 'Laporan Gigi RL3.3',
            'type' => 'RL3.3',
            'status' => '1',
        ], 'legacy');

        // 2. Index
        $indexRes = $this->getJson('/api/v1/master-data/report-groups?search=Gigi');
        $indexRes->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // 3. Show
        $showRes = $this->getJson("/api/v1/master-data/report-groups/{$rgId}");
        $showRes->assertStatus(200)
            ->assertJsonPath('data.name', 'Laporan Gigi RL3.3');

        // 4. Update
        $updateRes = $this->putJson("/api/v1/master-data/report-groups/{$rgId}", [
            'name' => 'Laporan Gigi RL3.3 Revisi',
            'type' => 'RL3.3',
            'is_active' => true,
        ]);
        $updateRes->assertStatus(200)
            ->assertJsonPath('data.name', 'Laporan Gigi RL3.3 Revisi');

        // 5. Update status
        $statusRes = $this->patchJson("/api/v1/master-data/report-groups/{$rgId}/status", [
            'is_active' => false,
        ]);
        $statusRes->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        // 6. Delete
        $deleteRes = $this->deleteJson("/api/v1/master-data/report-groups/{$rgId}");
        $deleteRes->assertStatus(204);

        $this->assertDatabaseMissing('ref_report_group', [
            'id' => $rgId,
            'deleted_at' => null,
        ], 'legacy');
    }

    /**
     * Test 2: Direct CRUD for ProcedurePackage (ref_paket_tindakan & map_paket_tindakan).
     */
    public function test_can_crud_procedure_packages_directly_in_legacy(): void
    {
        // 1. Create package with items
        $payload = [
            'name' => 'Paket Pemeriksaan Pediatrik',
            'is_active' => true,
            'items' => [
                [
                    'medical_procedure_id' => 1,
                    'quantity' => 1,
                    'unit_amount' => 150000,
                ],
                [
                    'medical_procedure_id' => 2,
                    'quantity' => 1,
                    'unit_amount' => 75000,
                ],
            ],
        ];

        $createRes = $this->postJson('/api/v1/master-data/procedure-packages', $payload);
        $createRes->assertStatus(201)
            ->assertJsonPath('data.name', 'Paket Pemeriksaan Pediatrik')
            ->assertJsonPath('data.total_amount', 225000);

        $pkgId = $createRes->json('data.id');

        $this->assertDatabaseHas('ref_paket_tindakan', [
            'id' => $pkgId,
            'nama' => 'Paket Pemeriksaan Pediatrik',
            'status' => '1',
        ], 'legacy');

        $this->assertDatabaseHas('map_paket_tindakan', [
            'id_ref_paket_tindakan' => $pkgId,
            'id_ref_tarif_tindakan' => 1,
        ], 'legacy');

        $this->assertDatabaseHas('map_paket_tindakan', [
            'id_ref_paket_tindakan' => $pkgId,
            'id_ref_tarif_tindakan' => 2,
        ], 'legacy');

        // 2. Index
        $indexRes = $this->getJson('/api/v1/master-data/procedure-packages');
        $indexRes->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Paket Pemeriksaan Pediatrik');

        // 3. Show
        $showRes = $this->getJson("/api/v1/master-data/procedure-packages/{$pkgId}");
        $showRes->assertStatus(200)
            ->assertJsonPath('data.name', 'Paket Pemeriksaan Pediatrik')
            ->assertJsonCount(2, 'data.items');

        // 4. Update
        $updateRes = $this->putJson("/api/v1/master-data/procedure-packages/{$pkgId}", [
            'name' => 'Paket Pediatrik Updated',
            'is_active' => true,
            'items' => [
                [
                    'medical_procedure_id' => 1,
                    'quantity' => 1,
                    'unit_amount' => 150000,
                ],
            ],
        ]);
        $updateRes->assertStatus(200)
            ->assertJsonPath('data.name', 'Paket Pediatrik Updated')
            ->assertJsonPath('data.total_amount', 150000);

        // 5. Update status
        $statusRes = $this->patchJson("/api/v1/master-data/procedure-packages/{$pkgId}/status", [
            'is_active' => false,
        ]);
        $statusRes->assertStatus(200);

        // 6. Delete
        $deleteRes = $this->deleteJson("/api/v1/master-data/procedure-packages/{$pkgId}");
        $deleteRes->assertStatus(200);

        $this->assertDatabaseMissing('ref_paket_tindakan', [
            'id' => $pkgId,
            'deleted_at' => null,
        ], 'legacy');
    }

    /**
     * Test 3: Direct CRUD for Procedure User Mapping (map_ref_tindakan_user).
     */
    public function test_can_crud_procedure_user_mappings_directly_in_legacy(): void
    {
        // 1. Create mapping
        $createRes = $this->postJson('/api/v1/master-data/procedure-user-mappings', [
            'procedure_id' => 1,
            'employee_ids' => [1, 2],
        ]);

        $createRes->assertStatus(200)
            ->assertJsonPath('message', 'Mapping berhasil dibuat');

        $this->assertDatabaseHas('map_ref_tindakan_user', [
            'id_ref_tindakan' => 1,
            'id_aktor' => 1,
            'status' => 1,
        ], 'legacy');

        $this->assertDatabaseHas('map_ref_tindakan_user', [
            'id_ref_tindakan' => 1,
            'id_aktor' => 2,
            'status' => 1,
        ], 'legacy');

        // 2. Index
        $indexRes = $this->getJson('/api/v1/master-data/procedure-user-mappings');
        $indexRes->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.procedure.id', 1)
            ->assertJsonCount(2, 'data.0.employees');

        // 3. Show
        $showRes = $this->getJson('/api/v1/master-data/procedure-user-mappings/1');
        $showRes->assertStatus(200)
            ->assertJsonPath('data.procedure.id', 1)
            ->assertJsonCount(2, 'data.employees');

        // 4. Update (change employees to only employee 1)
        $updateRes = $this->putJson('/api/v1/master-data/procedure-user-mappings/1', [
            'procedure_id' => 1,
            'employee_ids' => [1],
        ]);
        $updateRes->assertStatus(200)
            ->assertJsonPath('message', 'Mapping berhasil diperbarui');

        $this->assertDatabaseHas('map_ref_tindakan_user', [
            'id_ref_tindakan' => 1,
            'id_aktor' => 1,
        ], 'legacy');

        $this->assertDatabaseMissing('map_ref_tindakan_user', [
            'id_ref_tindakan' => 1,
            'id_aktor' => 2,
            'deleted_at' => null,
        ], 'legacy');

        // 5. Delete
        $deleteRes = $this->deleteJson('/api/v1/master-data/procedure-user-mappings/1');
        $deleteRes->assertStatus(200)
            ->assertJsonPath('message', 'Mapping berhasil dihapus');

        $this->assertDatabaseMissing('map_ref_tindakan_user', [
            'id_ref_tindakan' => 1,
            'deleted_at' => null,
        ], 'legacy');
    }
}

