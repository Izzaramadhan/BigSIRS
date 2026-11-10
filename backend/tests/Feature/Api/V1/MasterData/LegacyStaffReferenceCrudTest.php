<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacyStaffReferenceCrudTest extends TestCase
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
            CREATE TABLE ref_jabatan (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                jabatan VARCHAR(50) NULL,
                deskripsi VARCHAR(50) NULL,
                kode VARCHAR(20) NULL,
                status VARCHAR(10) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_pendidikan (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                pendidikan VARCHAR(50) NULL,
                status VARCHAR(10) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_pekerjaan (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                pekerjaan VARCHAR(50) NULL,
                status VARCHAR(10) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_pegawai (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_jabatan INTEGER NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE master_person_index (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_pendidikan INTEGER NULL,
                id_pekerjaan INTEGER NULL
            )
        ');

        $this->authUser = User::factory()->create();
        Sanctum::actingAs($this->authUser);
    }

    /* -------------------------------------------------------------
     * 1. POSITION / JABATAN (ref_jabatan)
     * ------------------------------------------------------------- */
    public function test_position_full_legacy_crud_lifecycle(): void
    {
        // 1. Create Position
        $res = $this->postJson('/api/v1/master-data/positions', [
            'name' => 'Fisioterapis Ahli',
            'description' => 'Tenaga Fisioterapi',
            'code' => 'FISIO',
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.name', 'Fisioterapis Ahli');

        $id = $res->json('data.id');
        $this->assertDatabaseHas('ref_jabatan', [
            'id' => $id,
            'jabatan' => 'Fisioterapis Ahli',
            'deskripsi' => 'Tenaga Fisioterapi',
            'kode' => 'FISIO',
            'status' => '1',
        ], 'legacy');

        // Prevent duplicate
        $dupRes = $this->postJson('/api/v1/master-data/positions', [
            'name' => 'Fisioterapis Ahli',
        ]);
        $dupRes->assertStatus(422);

        // 2. Index with search and empty search query
        $emptySearchRes = $this->getJson('/api/v1/master-data/positions?search=');
        $emptySearchRes->assertOk();

        $listRes = $this->getJson('/api/v1/master-data/positions?search=Fisio');
        $listRes->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id);

        // 3. Show
        $showRes = $this->getJson("/api/v1/master-data/positions/{$id}");
        $showRes->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', 'Fisioterapis Ahli');

        // 4. Update
        $updRes = $this->putJson("/api/v1/master-data/positions/{$id}", [
            'name' => 'Fisioterapis Utama',
            'description' => 'Tenaga Fisioterapi Utama',
            'code' => 'FISIO-U',
        ]);
        $updRes->assertOk()
            ->assertJsonPath('data.name', 'Fisioterapis Utama');

        $this->assertDatabaseHas('ref_jabatan', [
            'id' => $id,
            'jabatan' => 'Fisioterapis Utama',
            'kode' => 'FISIO-U',
        ], 'legacy');

        // 5. Delete prevention when used by Employee
        DB::connection('legacy')->table('ref_pegawai')->insert([
            'id_jabatan' => $id,
        ]);

        $delBlocked = $this->deleteJson("/api/v1/master-data/positions/{$id}");
        $delBlocked->assertStatus(422)
            ->assertJsonPath('message', 'Data jabatan tidak dapat dihapus karena sedang digunakan oleh data pegawai.');

        // Clean up reference & delete
        DB::connection('legacy')->table('ref_pegawai')->truncate();

        $delRes = $this->deleteJson("/api/v1/master-data/positions/{$id}");
        $delRes->assertOk()
            ->assertJsonPath('message', 'Data jabatan berhasil dihapus');

        $this->assertSoftDeleted('ref_jabatan', ['id' => $id], 'legacy');
    }

    /* -------------------------------------------------------------
     * 2. EDUCATION / PENDIDIKAN (ref_pendidikan)
     * ------------------------------------------------------------- */
    public function test_education_full_legacy_crud_lifecycle(): void
    {
        // 1. Create Education
        $res = $this->postJson('/api/v1/master-data/educations', [
            'name' => 'Spesialis 2 (Sp-2)',
            'is_active' => true,
        ]);
        $res->assertStatus(201)
            ->assertJsonPath('data.name', 'Spesialis 2 (Sp-2)');

        $id = $res->json('data.id');
        $this->assertDatabaseHas('ref_pendidikan', [
            'id' => $id,
            'pendidikan' => 'Spesialis 2 (Sp-2)',
            'status' => '1',
        ], 'legacy');

        // Case-insensitive duplicate check
        $dupRes = $this->postJson('/api/v1/master-data/educations', [
            'name' => 'spesialis 2 (sp-2)',
        ]);
        $dupRes->assertStatus(422);

        // 2. Index with search and empty search query
        $emptySearchRes = $this->getJson('/api/v1/master-data/educations?search=');
        $emptySearchRes->assertOk();

        $listRes = $this->getJson('/api/v1/master-data/educations?search=Spesialis');
        $listRes->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id);

        // 3. Show
        $showRes = $this->getJson("/api/v1/master-data/educations/{$id}");
        $showRes->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', 'Spesialis 2 (Sp-2)');

        // 4. Update
        $updRes = $this->putJson("/api/v1/master-data/educations/{$id}", [
            'name' => 'Subspesialis / Sp-2',
            'is_active' => true,
        ]);
        $updRes->assertOk()
            ->assertJsonPath('data.name', 'Subspesialis / Sp-2');

        $this->assertDatabaseHas('ref_pendidikan', [
            'id' => $id,
            'pendidikan' => 'Subspesialis / Sp-2',
        ], 'legacy');

        // 5. Delete prevention when used by MPI
        DB::connection('legacy')->table('master_person_index')->insert([
            'id_pendidikan' => $id,
        ]);

        $delBlocked = $this->deleteJson("/api/v1/master-data/educations/{$id}");
        $delBlocked->assertStatus(409);

        // Clean up MPI & delete
        DB::connection('legacy')->table('master_person_index')->truncate();

        $delRes = $this->deleteJson("/api/v1/master-data/educations/{$id}");
        $delRes->assertNoContent();

        $this->assertSoftDeleted('ref_pendidikan', ['id' => $id], 'legacy');
    }

    /* -------------------------------------------------------------
     * 3. OCCUPATION / PEKERJAAN (ref_pekerjaan)
     * ------------------------------------------------------------- */
    public function test_occupation_full_legacy_crud_lifecycle(): void
    {
        // 1. Create Occupation
        $res = $this->postJson('/api/v1/master-data/occupations', [
            'name' => 'Arsitek Perangkat Lunak',
            'is_active' => true,
        ]);
        $res->assertStatus(201)
            ->assertJsonPath('data.name', 'Arsitek Perangkat Lunak');

        $id = $res->json('data.id');
        $this->assertDatabaseHas('ref_pekerjaan', [
            'id' => $id,
            'pekerjaan' => 'Arsitek Perangkat Lunak',
            'status' => '1',
        ], 'legacy');

        // Case-insensitive duplicate check
        $dupRes = $this->postJson('/api/v1/master-data/occupations', [
            'name' => 'arsitek perangkat lunak',
        ]);
        $dupRes->assertStatus(422);

        // 2. Index with search and empty search query
        $emptySearchRes = $this->getJson('/api/v1/master-data/occupations?search=');
        $emptySearchRes->assertOk();

        $listRes = $this->getJson('/api/v1/master-data/occupations?search=Arsitek');
        $listRes->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id);

        // 3. Show
        $showRes = $this->getJson("/api/v1/master-data/occupations/{$id}");
        $showRes->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', 'Arsitek Perangkat Lunak');

        // 4. Update
        $updRes = $this->putJson("/api/v1/master-data/occupations/{$id}", [
            'name' => 'Software Architect Specialist',
            'is_active' => true,
        ]);
        $updRes->assertOk()
            ->assertJsonPath('data.name', 'Software Architect Specialist');

        $this->assertDatabaseHas('ref_pekerjaan', [
            'id' => $id,
            'pekerjaan' => 'Software Architect Specialist',
        ], 'legacy');

        // 5. Delete prevention when used by MPI
        DB::connection('legacy')->table('master_person_index')->insert([
            'id_pekerjaan' => $id,
        ]);

        $delBlocked = $this->deleteJson("/api/v1/master-data/occupations/{$id}");
        $delBlocked->assertStatus(409);

        // Clean up MPI & delete
        DB::connection('legacy')->table('master_person_index')->truncate();

        $delRes = $this->deleteJson("/api/v1/master-data/occupations/{$id}");
        $delRes->assertNoContent();

        $this->assertSoftDeleted('ref_pekerjaan', ['id' => $id], 'legacy');
    }
}

