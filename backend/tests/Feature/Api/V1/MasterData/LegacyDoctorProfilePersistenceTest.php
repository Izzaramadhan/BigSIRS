<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Employee;
use App\Models\Legacy\LegacyDoctor;
use App\Models\Legacy\LegacyPerson;
use App\Models\MasterData\Doctor;
use App\Models\MasterData\Specialization;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Support\EloquentGuardableColumnsReset;
use Tests\TestCase;

class LegacyDoctorProfilePersistenceTest extends TestCase
{
    use RefreshDatabase;

    private User $authUser;

    private function setupLegacySchema(): void
    {
        Config::set('database.connections.legacy', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        DB::purge('legacy');
        LegacyAccess::clearCache();
        EloquentGuardableColumnsReset::reset();

        DB::connection('legacy')->statement('
            CREATE TABLE master_person_index (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                no_ktp INTEGER NULL,
                nama VARCHAR(50) NULL,
                gender VARCHAR(1) NULL,
                tgl_lahir DATE NULL,
                tempat_lahir VARCHAR(50) NULL,
                alamat VARCHAR(255) NULL,
                id_provinsi VARCHAR(2) NULL,
                id_kabupaten VARCHAR(4) NULL,
                id_kecamatan VARCHAR(7) NULL,
                id_kelurahan VARCHAR(10) NULL,
                kode_pos VARCHAR(50) NULL,
                no_hp VARCHAR(50) NOT NULL DEFAULT "",
                email VARCHAR(50) NULL,
                agama VARCHAR(20) NULL,
                kebangsaan VARCHAR(10) NULL,
                gol_darah VARCHAR(5) NULL,
                status_perkawinan VARCHAR(30) NULL,
                alergi_lainnya VARCHAR(255) NULL,
                ihs_id VARCHAR(255) NULL,
                id_pendidikan INTEGER NULL,
                id_pekerjaan INTEGER NULL,
                jenis_person VARCHAR(20) NULL,
                status VARCHAR(1) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
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
                nip VARCHAR(30) NULL,
                id_jabatan INTEGER NULL,
                status VARCHAR(1) DEFAULT "1",
                ttd TEXT NULL,
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
                no_str VARCHAR(25) NULL,
                sip VARCHAR(255) NULL,
                masa_berlaku DATE NULL,
                status VARCHAR(1) DEFAULT "1",
                kode_dpjp VARCHAR(255) NULL,
                ttd TEXT NULL,
                ihs_id VARCHAR(255) NULL,
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
            'username' => 'admin_legacy',
            'nama' => 'Admin Legacy',
            'status' => '1',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->authUser = User::factory()->create();
        Sanctum::actingAs($this->authUser);
    }

    public function test_store_doctor_in_internal_database_mode_when_legacy_unavailable(): void
    {
        Config::set('database.connections.legacy', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        DB::purge('legacy');
        LegacyAccess::clearCache();
        EloquentGuardableColumnsReset::reset();

        $spec = Specialization::factory()->create();

        $response = $this->postJson('/api/v1/master-data/doctors', [
            'person' => [
                'national_id' => '3578010101850009',
                'name' => 'dr. Internal Mode, Sp.A',
                'gender' => 'P',
                'phone' => '081234567890',
                'allergies' => 'Seafood',
                'ihs_number' => 'IHS-INT-01',
                'province_id' => '35',
            ],
            'professional' => [
                'specialization_id' => $spec->id,
                'str_number' => 'STR-INT-1',
                'sip_number' => 'SIP-INT-1',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.employee.allergies', 'Seafood')
            ->assertJsonPath('data.employee.ihs_number', 'IHS-INT-01')
            ->assertJsonPath('data.employee.province_id', '35');

        $this->assertDatabaseHas('employees', [
            'name' => 'dr. Internal Mode, Sp.A',
            'allergies' => 'Seafood',
            'ihs_number' => 'IHS-INT-01',
            'province_id' => '35',
        ]);
        $this->assertDatabaseHas('doctors', [
            'specialization_id' => $spec->id,
            'sip_number' => 'SIP-INT-1',
        ]);
    }

    public function test_store_update_show_and_partial_update_preserve_all_legacy_biodata_and_signature(): void
    {
        $this->setupLegacySchema();
        Storage::fake('public');

        $specId = DB::connection('legacy')->table('ref_spesialisasi')->insertGetId([
            'nama' => 'Spesialis Anak',
            'kode' => 'SP-A',
            'status' => '1',
            'id_user' => 1,
        ]);

        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $signature = UploadedFile::fake()->createWithContent('ttd-dokter.png', $pngBytes);

        // 1. Store legacy doctor lengkap dengan wilayah, biodata, alergi, IHS, dan tanda tangan
        $storeResp = $this->post('/api/v1/master-data/doctors', [
            'person' => [
                'national_id' => '3578010101800001',
                'name' => 'dr. Legacy Lengkap, Sp.A',
                'gender' => 'L',
                'birth_place' => 'Surabaya',
                'birth_date' => '1980-01-01',
                'blood_type' => 'Unknown',
                'religion' => 'Buddha',
                'marital_status' => 'Kawin',
                'nationality' => 'WNI',
                'allergies' => 'Penisilin',
                'ihs_number' => '1000999888',
                'address' => 'Jl. Airlangga No. 1',
                'province_id' => '35',
                'regency_id' => '3578',
                'district_id' => '357801',
                'village_id' => '3578011001',
                'postal_code' => '60286',
                'phone' => '08123450001',
            ],
            'professional' => [
                'specialization_id' => $specId,
                'str_number' => 'STR-LGC-01',
                'sip_number' => 'SIP-LGC-01',
                'sip_valid_until' => '2029-12-31',
                'bpjs_dpjp_code' => 'DPJP-77',
                'is_active' => '1',
            ],
            'signature' => $signature,
        ]);

        $storeResp->assertCreated();
        $docId = $storeResp->json('data.id');
        $mpiRow = DB::connection('legacy')->table('master_person_index')->first();
        $docRow = DB::connection('legacy')->table('ref_dokter')->where('id', $docId)->first();
        $empRow = DB::connection('legacy')->table('ref_pegawai')->where('id_mpi', $mpiRow->id)->first();

        $this->assertNotNull($empRow);
        $this->assertEquals(1, DB::connection('legacy')->table('master_person_index')->count());
        $this->assertSame('35', $mpiRow->id_provinsi);
        $this->assertSame('3578', $mpiRow->id_kabupaten);
        $this->assertSame('357801', $mpiRow->id_kecamatan);
        $this->assertSame('3578011001', $mpiRow->id_kelurahan);
        $this->assertSame('60286', $mpiRow->kode_pos);
        $this->assertSame('-', $mpiRow->gol_darah);
        $this->assertSame('Budha', $mpiRow->agama);
        $this->assertSame('Kawin', $mpiRow->status_perkawinan);
        $this->assertSame('WNI', $mpiRow->kebangsaan);
        $this->assertSame('Penisilin', $mpiRow->alergi_lainnya);
        $this->assertSame('1000999888', $mpiRow->ihs_id);
        $this->assertSame('1000999888', $docRow->ihs_id);
        $this->assertNotEmpty($docRow->ttd);
        Storage::disk('public')->assertExists($docRow->ttd);

        // 2. Detail GET mengembalikan semua nilai tersimpan untuk Edit form
        $showResp = $this->getJson("/api/v1/master-data/doctors/{$docId}");
        $showResp->assertOk()
            ->assertJsonPath('data.employee_id', $empRow->id)
            ->assertJsonPath('data.employee.blood_type', 'Unknown')
            ->assertJsonPath('data.employee.marital_status', 'Kawin')
            ->assertJsonPath('data.employee.nationality', 'WNI')
            ->assertJsonPath('data.employee.allergies', 'Penisilin')
            ->assertJsonPath('data.employee.ihs_number', '1000999888')
            ->assertJsonPath('data.employee.province_id', '35')
            ->assertJsonPath('data.employee.regency_id', '3578')
            ->assertJsonPath('data.employee.district_id', '357801')
            ->assertJsonPath('data.employee.village_id', '3578011001')
            ->assertJsonPath('data.employee.postal_code', '60286')
            ->assertJsonPath('data.has_signature', true);

        // 3. Partial update (tidak mengirim alergi, wilayah, ihs_number, maupun file baru)
        $originalTtd = $docRow->ttd;
        $partialUpdateResp = $this->putJson("/api/v1/master-data/doctors/{$docId}", [
            'person' => [
                'name' => 'dr. Legacy Updated, Sp.A',
                'gender' => 'L',
                'phone' => '08199999999',
            ],
            'professional' => [
                'specialization_id' => $specId,
            ],
        ]);

        $partialUpdateResp->assertOk()
            ->assertJsonPath('data.employee.name', 'dr. Legacy Updated, Sp.A')
            ->assertJsonPath('data.employee.allergies', 'Penisilin')
            ->assertJsonPath('data.employee.ihs_number', '1000999888')
            ->assertJsonPath('data.employee.province_id', '35')
            ->assertJsonPath('data.employee.postal_code', '60286')
            ->assertJsonPath('data.sip_number', 'SIP-LGC-01')
            ->assertJsonPath('data.has_signature', true);

        $this->assertSame($originalTtd, DB::connection('legacy')->table('ref_dokter')->where('id', $docId)->value('ttd'));

        // 4. Validasi gagal tidak mengubah data tersimpan secara parsial
        $invalidResp = $this->putJson("/api/v1/master-data/doctors/{$docId}", [
            'person' => [
                'name' => 'Seharusnya Gagal',
                'gender' => 'X',
                'allergies' => 'Diubah Saat Gagal',
            ],
            'professional' => [
                'specialization_id' => 999999,
            ],
        ]);
        $invalidResp->assertStatus(422)
            ->assertJsonValidationErrors(['person.gender', 'professional.specialization_id']);

        $this->assertSame('dr. Legacy Updated, Sp.A', DB::connection('legacy')->table('master_person_index')->where('id', $mpiRow->id)->value('nama'));
        $this->assertSame('Penisilin', DB::connection('legacy')->table('master_person_index')->where('id', $mpiRow->id)->value('alergi_lainnya'));

        // 5. Hapus tanda tangan dengan remove_signature=1 dan ubah alergi/golongan darah
        $clearSigResp = $this->putJson("/api/v1/master-data/doctors/{$docId}", [
            'person' => [
                'name' => 'dr. Legacy Updated, Sp.A',
                'gender' => 'L',
                'blood_type' => 'AB',
                'allergies' => 'Debu',
            ],
            'professional' => [
                'specialization_id' => $specId,
            ],
            'remove_signature' => true,
        ]);
        $clearSigResp->assertOk()
            ->assertJsonPath('data.employee.blood_type', 'AB')
            ->assertJsonPath('data.employee.allergies', 'Debu')
            ->assertJsonPath('data.has_signature', false);
        Storage::disk('public')->assertMissing($originalTtd);

        // 6. Hapus dokter tidak menghapus data pegawai maupun master_person_index
        $this->deleteJson("/api/v1/master-data/doctors/{$docId}")->assertNoContent();
        $this->assertDatabaseHas('ref_pegawai', ['id' => $empRow->id, 'deleted_at' => null], 'legacy');
        $this->assertDatabaseHas('master_person_index', ['id' => $mpiRow->id, 'deleted_at' => null], 'legacy');
    }

    public function test_store_legacy_doctor_with_existing_employee_and_show_without_employee_row(): void
    {
        $this->setupLegacySchema();

        $specId = DB::connection('legacy')->table('ref_spesialisasi')->insertGetId([
            'nama' => 'Bedah Umum',
            'kode' => 'SP-B',
            'status' => '1',
            'id_user' => 1,
        ]);

        $mpiId = DB::connection('legacy')->table('master_person_index')->insertGetId([
            'nama' => 'dr. Pegawai Existing',
            'gender' => 'P',
            'no_hp' => '081111',
            'jenis_person' => 'karyawan',
            'status' => '1',
        ]);
        $empId = DB::connection('legacy')->table('ref_pegawai')->insertGetId([
            'id_mpi' => $mpiId,
            'nip' => 'EMP-77',
            'status' => '1',
        ]);

        // Store menautkan pegawai existing tanpa menggandakan master_person_index
        $resp = $this->postJson('/api/v1/master-data/doctors', [
            'employee_id' => $empId,
            'person' => [
                'name' => 'dr. Pegawai Existing, Sp.B',
                'gender' => 'P',
                'allergies' => 'Latex',
                'ihs_number' => 'IHS-EMP-77',
                'province_id' => '35',
            ],
            'professional' => [
                'specialization_id' => $specId,
            ],
        ]);
        $resp->assertCreated()
            ->assertJsonPath('data.employee_id', $empId)
            ->assertJsonPath('data.employee.allergies', 'Latex')
            ->assertJsonPath('data.employee.ihs_number', 'IHS-EMP-77');
        $this->assertEquals(1, DB::connection('legacy')->table('master_person_index')->count());

        // Legacy dokter tanpa record ref_pegawai tetap mengembalikan data person lengkap di show()
        $orphanMpiId = DB::connection('legacy')->table('master_person_index')->insertGetId([
            'nama' => 'dr. Tanpa Pegawai',
            'gender' => 'L',
            'no_hp' => '082222',
            'alergi_lainnya' => 'Kacang',
            'id_provinsi' => '31',
            'ihs_id' => 'IHS-ORPHAN',
            'jenis_person' => 'dokter',
            'status' => '1',
        ]);
        $orphanDocId = DB::connection('legacy')->table('ref_dokter')->insertGetId([
            'id_mpi' => $orphanMpiId,
            'id_spesialisasi' => $specId,
            'status' => '1',
            'created_at' => now(),
        ]);

        $this->getJson("/api/v1/master-data/doctors/{$orphanDocId}")
            ->assertOk()
            ->assertJsonPath('data.employee', null)
            ->assertJsonPath('data.person.name', 'dr. Tanpa Pegawai')
            ->assertJsonPath('data.person.allergies', 'Kacang')
            ->assertJsonPath('data.person.province_id', '31')
            ->assertJsonPath('data.person.ihs_number', 'IHS-ORPHAN');

        // Duplikasi pegawai yang sudah menjadi dokter ditolak saat store dan saat update dokter lain
        $this->postJson('/api/v1/master-data/doctors', [
            'employee_id' => $empId,
            'person' => [
                'name' => 'Duplikat Dokter',
                'gender' => 'P',
            ],
            'professional' => [
                'specialization_id' => $specId,
            ],
        ])->assertStatus(422)->assertJsonValidationErrors(['employee_id']);

        $this->putJson("/api/v1/master-data/doctors/{$orphanDocId}", [
            'employee_id' => $empId,
            'person' => [
                'name' => 'Coba Ambil Pegawai Lain',
                'gender' => 'L',
            ],
            'professional' => [
                'specialization_id' => $specId,
            ],
        ])->assertStatus(422)->assertJsonValidationErrors(['employee_id']);

        // Edit diri sendiri dengan employee_id sendiri tetap valid, dan pengosongan eksplisit alergi via FormData ("" -> null) berhasil
        $docId = $resp->json('data.id');
        $this->post("/api/v1/master-data/doctors/{$docId}", [
            '_method' => 'PUT',
            'employee_id' => $empId,
            'person' => [
                'name' => 'dr. Pegawai Existing, Sp.B',
                'gender' => 'P',
                'allergies' => '',
            ],
            'professional' => [
                'specialization_id' => $specId,
                'str_number' => '',
            ],
        ])->assertOk()
            ->assertJsonPath('data.employee.allergies', null)
            ->assertJsonPath('data.str_number', null);

        $this->assertNull(DB::connection('legacy')->table('master_person_index')->where('id', $mpiId)->value('alergi_lainnya'));
        $this->assertNull(DB::connection('legacy')->table('ref_dokter')->where('id', $docId)->value('no_str'));

        // Pastikan setelah reload GET show() nilai tetap null (bukan string "null")
        $this->getJson("/api/v1/master-data/doctors/{$docId}")
            ->assertOk()
            ->assertJsonPath('data.employee.allergies', null);
    }

    public function test_eloquent_guardable_columns_reset_helper_isolates_schema_transitions_safely(): void
    {
        Config::set('database.connections.legacy', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        DB::purge('legacy');
        LegacyAccess::clearCache();
        EloquentGuardableColumnsReset::reset();

        // Simulate an earlier test populating Eloquent::$guardableColumns with a minimal legacy schema
        DB::connection('legacy')->statement('
            CREATE TABLE master_person_index (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(50) NULL,
                gender VARCHAR(1) NULL,
                jenis_person VARCHAR(20) NULL,
                status VARCHAR(1) DEFAULT "1",
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
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        $minimalPerson = LegacyPerson::create([
            'id' => 999,
            'nama' => 'Seed Minimal Person',
            'gender' => 'L',
            'jenis_person' => 'dokter',
            'status' => 1,
        ]);
        $this->assertNotSame(999, (int) $minimalPerson->id);
        $this->assertFalse(LegacyPerson::isUnguarded());

        LegacyDoctor::create([
            'id_mpi' => $minimalPerson->id,
            'id_spesialisasi' => 1,
            'status' => 1,
        ]);

        // Recreate full legacy schema in the same PHP process; setupLegacySchema() invokes EloquentGuardableColumnsReset::reset()
        $this->setupLegacySchema();
        $specId = DB::connection('legacy')->table('ref_spesialisasi')->insertGetId([
            'nama' => 'Spesialis Penyakit Dalam',
            'kode' => 'SP-PD',
            'status' => '1',
            'id_user' => 1,
        ]);

        $resp = $this->postJson('/api/v1/master-data/doctors', [
            'person' => [
                'name' => 'dr. Reloaded Schema, Sp.PD',
                'gender' => 'L',
                'province_id' => '35',
                'allergies' => 'Latex',
                'ihs_number' => '1000888777',
            ],
            'professional' => [
                'specialization_id' => $specId,
                'is_active' => true,
            ],
        ])->assertCreated();

        $docId = $resp->json('data.id');
        $mpiRow = DB::connection('legacy')->table('master_person_index')->first();

        $this->assertSame('35', $mpiRow->id_provinsi);
        $this->assertSame('Latex', $mpiRow->alergi_lainnya);
        $this->assertSame('1000888777', $mpiRow->ihs_id);
        $this->assertSame('1000888777', DB::connection('legacy')->table('ref_dokter')->where('id', $docId)->value('ihs_id'));

        // Switch again back to minimal schema -> verify guardable columns stay protected (unknown column ignored & id still guarded)
        DB::purge('legacy');
        LegacyAccess::clearCache();
        EloquentGuardableColumnsReset::reset();
        DB::connection('legacy')->statement('
            CREATE TABLE master_person_index (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(50) NULL,
                gender VARCHAR(1) NULL,
                jenis_person VARCHAR(20) NULL,
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        $secondMinimal = LegacyPerson::create([
            'id' => 888,
            'nama' => 'Minimal Again',
            'gender' => 'P',
            'jenis_person' => 'dokter',
            'status' => 1,
        ]);
        $this->assertNotSame(888, (int) $secondMinimal->id);
        $this->assertFalse(LegacyPerson::isUnguarded());
        $this->assertFalse($secondMinimal->isFillable('id'));
        $this->assertFalse($secondMinimal->isFillable('alergi_lainnya'));
    }

    protected function tearDown(): void
    {
        DB::purge('legacy');
        LegacyAccess::clearCache();
        EloquentGuardableColumnsReset::reset();
        parent::tearDown();
    }
}
