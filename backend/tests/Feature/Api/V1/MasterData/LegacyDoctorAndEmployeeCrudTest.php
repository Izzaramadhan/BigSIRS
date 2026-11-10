<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacyDoctorAndEmployeeCrudTest extends TestCase
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
            CREATE TABLE master_person_index (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                no_ktp VARCHAR(20) NULL,
                nama VARCHAR(255) NULL,
                gender CHAR(1) NULL,
                tgl_lahir DATE NULL,
                tempat_lahir VARCHAR(255) NULL,
                alamat TEXT NULL,
                no_hp VARCHAR(20) NULL,
                email VARCHAR(255) NULL,
                agama VARCHAR(50) NULL,
                id_pendidikan INTEGER NULL,
                id_pekerjaan INTEGER NULL,
                jenis_person VARCHAR(20) NULL,
                status INTEGER DEFAULT 1,
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_jabatan (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                jabatan VARCHAR(50) NULL,
                deskripsi VARCHAR(50) NULL,
                status CHAR(1) DEFAULT "1",
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                id_user INTEGER NULL,
                kode VARCHAR(20) NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_spesialisasi (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(100) NULL,
                kode VARCHAR(20) NULL,
                status CHAR(1) DEFAULT "1",
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                id_user INTEGER NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_pegawai (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_mpi INTEGER NULL,
                nip VARCHAR(50) NULL,
                id_jabatan INTEGER NULL,
                status INTEGER DEFAULT 1,
                ttd VARCHAR(255) NULL,
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_dokter (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_mpi INTEGER NULL,
                id_spesialisasi INTEGER NULL,
                no_str VARCHAR(50) NULL,
                sip VARCHAR(255) NULL,
                masa_berlaku DATE NULL,
                status INTEGER DEFAULT 1,
                kode_dpjp VARCHAR(50) NULL,
                ttd VARCHAR(255) NULL,
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
                nama VARCHAR(100) NULL,
                id_pegawai INTEGER NULL,
                status CHAR(1) DEFAULT "1"
            )
        ');

        DB::connection('legacy')->table('tbl_user')->insert([
            'id' => 1,
            'username' => 'admin.legacy',
            'nama' => 'Administrator',
            'status' => '1',
        ]);

        $this->authUser = User::factory()->create([
            'username' => 'admin.legacy',
            'email' => 'admin@example.com',
        ]);
        Sanctum::actingAs($this->authUser);
    }

    protected function tearDown(): void
    {
        LegacyAccess::clearCache();
        parent::tearDown();
    }

    public function test_employee_direct_crud_lifecycle(): void
    {
        $jabatanId = DB::connection('legacy')->table('ref_jabatan')->insertGetId([
            'jabatan' => 'Perawat',
            'status' => '1',
            'id_user' => 1,
        ]);

        // 1. Create employee
        $createResponse = $this->postJson('/api/v1/master-data/employees', [
            'name' => 'Siti Aminah',
            'national_id' => '3201019901010001',
            'code' => 'EMP-001',
            'gender' => 'P',
            'birth_date' => '1990-01-01',
            'birth_place' => 'Bandung',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 10',
            'position_id' => $jabatanId,
            'is_active' => true,
        ]);

        $createResponse->assertStatus(201);
        $empId = $createResponse->json('data.id');
        $this->assertNotNull($empId);

        // Verify in legacy DB
        $this->assertDatabaseHas('ref_pegawai', [
            'id' => $empId,
            'nip' => 'EMP-001',
            'id_jabatan' => $jabatanId,
            'status' => 1,
        ], 'legacy');
        $this->assertDatabaseHas('master_person_index', [
            'nama' => 'Siti Aminah',
            'no_ktp' => '3201019901010001',
            'jenis_person' => 'karyawan',
        ], 'legacy');

        // 2. Read employee
        $showResponse = $this->getJson("/api/v1/master-data/employees/{$empId}");
        $showResponse->assertStatus(200);
        $this->assertEquals('Siti Aminah', $showResponse->json('name'));

        // 3. Update employee
        $updateResponse = $this->putJson("/api/v1/master-data/employees/{$empId}", [
            'name' => 'Siti Aminah S.Kep',
            'national_id' => '3201019901010001',
            'code' => 'EMP-001-REV',
            'gender' => 'P',
            'position_id' => $jabatanId,
            'is_active' => true,
        ]);
        $updateResponse->assertStatus(200);

        $this->assertDatabaseHas('ref_pegawai', [
            'id' => $empId,
            'nip' => 'EMP-001-REV',
        ], 'legacy');
        $this->assertDatabaseHas('master_person_index', [
            'nama' => 'Siti Aminah S.Kep',
        ], 'legacy');

        // 4. Update status
        $statusResponse = $this->patchJson("/api/v1/master-data/employees/{$empId}/status", [
            'is_active' => false,
        ]);
        $statusResponse->assertStatus(200);
        $this->assertDatabaseHas('ref_pegawai', [
            'id' => $empId,
            'status' => 0,
        ], 'legacy');

        // 5. Delete employee
        $deleteResponse = $this->deleteJson("/api/v1/master-data/employees/{$empId}");
        $deleteResponse->assertStatus(200);
    }

    public function test_doctor_direct_crud_lifecycle(): void
    {
        $specId = DB::connection('legacy')->table('ref_spesialisasi')->insertGetId([
            'nama' => 'Spesialis Penyakit Dalam',
            'kode' => 'SP-PD',
            'status' => '1',
            'id_user' => 1,
        ]);

        // 1. Create doctor (without existing employee)
        $createResponse = $this->postJson('/api/v1/master-data/doctors', [
            'person' => [
                'name' => 'dr. Budi Santoso, Sp.PD',
                'national_id' => '3301018501010002',
                'gender' => 'L',
                'birth_date' => '1985-05-15',
                'birth_place' => 'Jakarta',
                'phone' => '081298765432',
                'address' => 'Jl. Sudirman No. 45',
            ],
            'professional' => [
                'specialization_id' => $specId,
                'str_number' => 'STR-12345678',
                'sip_number' => 'SIP-87654321',
                'sip_valid_until' => '2028-12-31',
                'bpjs_dpjp_code' => 'DPJP-999',
                'is_active' => true,
            ],
        ]);

        $createResponse->assertStatus(201);
        $docId = $createResponse->json('data.id');
        $this->assertNotNull($docId);

        // Verify in legacy DB
        $this->assertDatabaseHas('ref_dokter', [
            'id' => $docId,
            'id_spesialisasi' => $specId,
            'no_str' => 'STR-12345678',
            'sip' => 'SIP-87654321',
            'kode_dpjp' => 'DPJP-999',
            'status' => 1,
        ], 'legacy');
        $this->assertDatabaseHas('master_person_index', [
            'nama' => 'dr. Budi Santoso, Sp.PD',
            'jenis_person' => 'dokter',
        ], 'legacy');

        // 2. Read doctor
        $showResponse = $this->getJson("/api/v1/master-data/doctors/{$docId}");
        $showResponse->assertStatus(200);
        $this->assertEquals('dr. Budi Santoso, Sp.PD', $showResponse->json('data.name'));
        $this->assertEquals('SIP-87654321', $showResponse->json('data.sip_number'));

        // 3. Update doctor
        $updateResponse = $this->putJson("/api/v1/master-data/doctors/{$docId}", [
            'person' => [
                'name' => 'dr. Budi Santoso, Sp.PD, K-GEH',
                'national_id' => '3301018501010002',
                'gender' => 'L',
                'address' => 'Jl. Gatot Subroto No. 50',
            ],
            'professional' => [
                'specialization_id' => $specId,
                'str_number' => 'STR-12345678-REV',
                'sip_number' => 'SIP-87654321',
                'sip_valid_until' => '2029-12-31',
                'bpjs_dpjp_code' => 'DPJP-999',
                'is_active' => true,
            ],
        ]);
        $updateResponse->assertStatus(200);

        $this->assertDatabaseHas('ref_dokter', [
            'id' => $docId,
            'no_str' => 'STR-12345678-REV',
        ], 'legacy');
        $this->assertDatabaseHas('master_person_index', [
            'nama' => 'dr. Budi Santoso, Sp.PD, K-GEH',
        ], 'legacy');

        // 4. Update status
        $statusResponse = $this->patchJson("/api/v1/master-data/doctors/{$docId}/status", [
            'is_active' => false,
        ]);
        $statusResponse->assertStatus(200);
        $this->assertDatabaseHas('ref_dokter', [
            'id' => $docId,
            'status' => 0,
        ], 'legacy');

        // 5. Lookup doctor
        $lookupResponse = $this->getJson('/api/v1/lookups/doctors?include_inactive=1');
        $lookupResponse->assertStatus(200);
        $this->assertTrue(collect($lookupResponse->json('data'))->contains('id', $docId));

        // 6. Delete doctor
        $deleteResponse = $this->deleteJson("/api/v1/master-data/doctors/{$docId}");
        $deleteResponse->assertStatus(204);
    }
}
