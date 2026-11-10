<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Legacy\LegacyDoctorSchedule;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacyDoctorScheduleCrudTest extends TestCase
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

        // 1. Create legacy trx_jadwal_dokter table
        DB::connection('legacy')->statement('
            CREATE TABLE trx_jadwal_dokter (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_dokter INTEGER NULL,
                hari_praktik VARCHAR(50) NOT NULL,
                jam_mulai TIME NULL,
                jam_selesai TIME NULL,
                id_poliklinik INTEGER NULL,
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                id_user INTEGER NULL,
                index_hari SMALLINT NULL,
                kuota_online INTEGER DEFAULT 0,
                libur INTEGER DEFAULT 0,
                order_by_antrean INTEGER NULL,
                tanggal_mulai_libur DATE NULL,
                tanggal_selesai_libur DATE NULL
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
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                id_user INTEGER NULL,
                is_active INTEGER DEFAULT 1
            )
        ');

        // 3. Create reference tables for relations
        DB::connection('legacy')->statement('
            CREATE TABLE ref_dokter (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_mpi INTEGER NULL,
                id_spesialisasi INTEGER NULL,
                no_str VARCHAR(50) NULL,
                sip VARCHAR(255) NULL,
                kode_dpjp VARCHAR(50) NULL,
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_poliklinik (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kd_poliklinik VARCHAR(50) NULL,
                nama VARCHAR(100) NOT NULL,
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_spesialisasi (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama_spesialisasi VARCHAR(100) NOT NULL,
                kode VARCHAR(50) NULL,
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE master_person_index (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(255) NOT NULL,
                no_ktp VARCHAR(50) NULL,
                no_hp VARCHAR(50) NULL,
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_pegawai (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_mpi INTEGER NULL,
                nama VARCHAR(255) NULL,
                nip VARCHAR(50) NULL,
                id_jabatan INTEGER NULL,
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        // Create standard test references
        DB::connection('legacy')->table('ref_poliklinik')->insert([
            ['id' => 10, 'kd_poliklinik' => 'POLI-UMUM', 'nama' => 'Poli Umum', 'status' => '1'],
            ['id' => 20, 'kd_poliklinik' => 'POLI-GIGI', 'nama' => 'Poli Gigi', 'status' => '1'],
        ]);

        DB::connection('legacy')->table('ref_spesialisasi')->insert([
            ['id' => 1, 'nama_spesialisasi' => 'Penyakit Dalam', 'kode' => 'PD', 'status' => '1'],
        ]);

        DB::connection('legacy')->table('master_person_index')->insert([
            ['id' => 100, 'nama' => 'dr. Andika Pratama, Sp.PD', 'no_ktp' => '3201123456780001', 'status' => '1'],
            ['id' => 200, 'nama' => 'drg. Maya Sari', 'no_ktp' => '3201123456780002', 'status' => '1'],
        ]);

        DB::connection('legacy')->table('ref_pegawai')->insert([
            ['id' => 100, 'id_mpi' => 100, 'nama' => 'dr. Andika Pratama, Sp.PD', 'nip' => 'PEG-001', 'id_jabatan' => 1, 'status' => '1'],
            ['id' => 200, 'id_mpi' => 200, 'nama' => 'drg. Maya Sari', 'nip' => 'PEG-002', 'id_jabatan' => 1, 'status' => '1'],
        ]);

        DB::connection('legacy')->table('ref_dokter')->insert([
            ['id' => 5, 'id_mpi' => 100, 'id_spesialisasi' => 1, 'no_str' => 'STR-12345', 'sip' => 'SIP-12345', 'status' => '1'],
            ['id' => 6, 'id_mpi' => 200, 'id_spesialisasi' => 1, 'no_str' => 'STR-67890', 'sip' => 'SIP-67890', 'status' => '1'],
        ]);

        // Create user with valid mapping
        $this->authUser = User::factory()->create([
            'username' => 'admin.legacy',
            'email' => 'admin.legacy@simrs.test',
            'name' => 'Admin Legacy',
        ]);

        DB::connection('legacy')->table('tbl_user')->insert([
            'id' => 103,
            'username' => 'admin.legacy',
            'nama' => 'Admin Legacy',
            'status' => '1',
            'created_at' => now(),
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/master-data/doctor-schedules');
        $response->assertStatus(401);
    }

    public function test_unmapped_legacy_user_is_forbidden_on_write(): void
    {
        $unmappedUser = User::factory()->create([
            'username' => 'unmapped_user',
            'email' => 'unmapped@simrs.test',
        ]);

        Sanctum::actingAs($unmappedUser);

        $payload = [
            'doctor_id' => 5,
            'polyclinic_id' => 10,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'is_holiday' => false,
            'online_quota' => 15,
        ];

        $response = $this->postJson('/api/v1/master-data/doctor-schedules', $payload);
        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.');
    }

    public function test_can_list_and_search_schedules_directly_from_legacy_db(): void
    {
        DB::connection('legacy')->table('trx_jadwal_dokter')->insert([
            [
                'id' => 1,
                'id_dokter' => 5,
                'id_poliklinik' => 10,
                'hari_praktik' => 'Senin',
                'index_hari' => 1,
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '12:00:00',
                'status' => '1',
                'libur' => 0,
                'kuota_online' => 10,
                'created_at' => now(),
            ],
            [
                'id' => 2,
                'id_dokter' => 6,
                'id_poliklinik' => 20,
                'hari_praktik' => 'Rabu',
                'index_hari' => 3,
                'jam_mulai' => '13:00:00',
                'jam_selesai' => '16:00:00',
                'status' => '1',
                'libur' => 1,
                'kuota_online' => 0,
                'created_at' => now(),
            ],
        ]);

        Sanctum::actingAs($this->authUser);

        // List all
        $response = $this->getJson('/api/v1/master-data/doctor-schedules');
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', 2);
        $response->assertJsonPath('data.0.day_of_week', 3);
        $response->assertJsonPath('data.0.is_holiday', true);

        // Filter by doctor
        $resDoc = $this->getJson('/api/v1/master-data/doctor-schedules?doctor_id=5');
        $resDoc->assertStatus(200);
        $resDoc->assertJsonCount(1, 'data');
        $resDoc->assertJsonPath('data.0.doctor_id', 5);

        // Filter by polyclinic
        $resPoly = $this->getJson('/api/v1/master-data/doctor-schedules?polyclinic_id=20');
        $resPoly->assertStatus(200);
        $resPoly->assertJsonCount(1, 'data');
        $resPoly->assertJsonPath('data.0.polyclinic_id', 20);

        // Filter by day_of_week
        $resDay = $this->getJson('/api/v1/master-data/doctor-schedules?day_of_week=1');
        $resDay->assertStatus(200);
        $resDay->assertJsonCount(1, 'data');
        $resDay->assertJsonPath('data.0.day_of_week', 1);

        // Filter by is_holiday
        $resHol = $this->getJson('/api/v1/master-data/doctor-schedules?is_holiday=1');
        $resHol->assertStatus(200);
        $resHol->assertJsonCount(1, 'data');
        $resHol->assertJsonPath('data.0.id', 2);
    }

    public function test_can_get_unpaginated_recap_schedules(): void
    {
        DB::connection('legacy')->table('trx_jadwal_dokter')->insert([
            [
                'id' => 1,
                'id_dokter' => 5,
                'id_poliklinik' => 10,
                'hari_praktik' => 'Senin',
                'index_hari' => 1,
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '12:00:00',
                'status' => '1',
                'created_at' => now(),
            ],
            [
                'id' => 2,
                'id_dokter' => 5,
                'id_poliklinik' => 10,
                'hari_praktik' => 'Kamis',
                'index_hari' => 4,
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '12:00:00',
                'status' => '1',
                'created_at' => now(),
            ],
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->getJson('/api/v1/master-data/doctor-schedules/recap');
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.day_of_week', 1);
        $response->assertJsonPath('data.1.day_of_week', 4);
    }

    public function test_can_show_single_schedule_directly_from_legacy_db(): void
    {
        DB::connection('legacy')->table('trx_jadwal_dokter')->insert([
            'id' => 99,
            'id_dokter' => 5,
            'id_poliklinik' => 10,
            'hari_praktik' => 'Selasa',
            'index_hari' => 2,
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '14:00:00',
            'status' => '1',
            'libur' => 0,
            'kuota_online' => 25,
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->getJson('/api/v1/master-data/doctor-schedules/99');
        $response->assertStatus(200);
        $response->assertJsonPath('data.id', 99);
        $response->assertJsonPath('data.doctor_id', 5);
        $response->assertJsonPath('data.doctor.name', 'dr. Andika Pratama, Sp.PD');
        $response->assertJsonPath('data.polyclinic.name', 'Poli Umum');
        $response->assertJsonPath('data.day_of_week', 2);
        $response->assertJsonPath('data.start_time', '09:00');
        $response->assertJsonPath('data.end_time', '14:00');
        $response->assertJsonPath('data.online_quota', 25);
    }

    public function test_can_create_schedule_directly_in_legacy_database(): void
    {
        Sanctum::actingAs($this->authUser);

        $payload = [
            'doctor_id' => 5,
            'polyclinic_id' => 10,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'is_holiday' => false,
            'online_quota' => 20,
        ];

        $response = $this->postJson('/api/v1/master-data/doctor-schedules', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.doctor_id', 5);
        $response->assertJsonPath('data.day_of_week', 1);
        $response->assertJsonPath('data.start_time', '08:00');
        $response->assertJsonPath('data.end_time', '12:00');
        $response->assertJsonPath('data.online_quota', 20);

        $newId = $response->json('data.id');
        $this->assertNotNull($newId);

        // Verify written to legacy database
        $legacyRow = DB::connection('legacy')->table('trx_jadwal_dokter')->where('id', $newId)->first();
        $this->assertNotNull($legacyRow);
        $this->assertEquals(5, $legacyRow->id_dokter);
        $this->assertEquals(10, $legacyRow->id_poliklinik);
        $this->assertEquals('Senin', $legacyRow->hari_praktik);
        $this->assertEquals(1, $legacyRow->index_hari);
        $this->assertEquals('08:00', substr($legacyRow->jam_mulai, 0, 5));
        $this->assertEquals('12:00', substr($legacyRow->jam_selesai, 0, 5));
        $this->assertEquals(103, $legacyRow->id_user);
        $this->assertEquals('1', $legacyRow->status);

        // Verify ZERO records written to framework table
        $this->assertEquals(0, DB::table('doctor_schedules')->count());
    }

    public function test_cannot_create_overlapping_schedule(): void
    {
        DB::connection('legacy')->table('trx_jadwal_dokter')->insert([
            'id' => 1,
            'id_dokter' => 5,
            'id_poliklinik' => 10,
            'hari_praktik' => 'Senin',
            'index_hari' => 1,
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '12:00:00',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $payload = [
            'doctor_id' => 5,
            'polyclinic_id' => 20,
            'day_of_week' => 1,
            'start_time' => '11:00',
            'end_time' => '15:00',
            'is_holiday' => false,
        ];

        $response = $this->postJson('/api/v1/master-data/doctor-schedules', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_time', 'end_time']);
    }

    public function test_can_create_consecutive_schedule(): void
    {
        DB::connection('legacy')->table('trx_jadwal_dokter')->insert([
            'id' => 1,
            'id_dokter' => 5,
            'id_poliklinik' => 10,
            'hari_praktik' => 'Senin',
            'index_hari' => 1,
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '12:00:00',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $payload = [
            'doctor_id' => 5,
            'polyclinic_id' => 20,
            'day_of_week' => 1,
            'start_time' => '12:00',
            'end_time' => '16:00',
            'is_holiday' => false,
            'online_quota' => 10,
        ];

        $response = $this->postJson('/api/v1/master-data/doctor-schedules', $payload);
        $response->assertStatus(201);
    }

    public function test_can_update_schedule_directly_in_legacy_database(): void
    {
        DB::connection('legacy')->table('trx_jadwal_dokter')->insert([
            'id' => 50,
            'id_dokter' => 5,
            'id_poliklinik' => 10,
            'hari_praktik' => 'Senin',
            'index_hari' => 1,
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '12:00:00',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $payload = [
            'doctor_id' => 5,
            'polyclinic_id' => 10,
            'day_of_week' => 2, // Changed to Selasa
            'start_time' => '09:00',
            'end_time' => '14:00',
            'is_holiday' => true,
            'online_quota' => 30,
        ];

        $response = $this->putJson('/api/v1/master-data/doctor-schedules/50', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('data.day_of_week', 2);
        $response->assertJsonPath('data.start_time', '09:00');
        $response->assertJsonPath('data.end_time', '14:00');
        $response->assertJsonPath('data.is_holiday', true);
        $response->assertJsonPath('data.online_quota', 30);

        // Verify updated in legacy database
        $legacyRow = DB::connection('legacy')->table('trx_jadwal_dokter')->where('id', 50)->first();
        $this->assertEquals('Selasa', $legacyRow->hari_praktik);
        $this->assertEquals(2, $legacyRow->index_hari);
        $this->assertEquals('09:00', substr($legacyRow->jam_mulai, 0, 5));
        $this->assertEquals('14:00', substr($legacyRow->jam_selesai, 0, 5));
        $this->assertEquals(1, $legacyRow->libur);
        $this->assertEquals(30, $legacyRow->kuota_online);
        $this->assertEquals(103, $legacyRow->id_user);
    }

    public function test_update_ignores_own_overlap(): void
    {
        DB::connection('legacy')->table('trx_jadwal_dokter')->insert([
            'id' => 77,
            'id_dokter' => 5,
            'id_poliklinik' => 10,
            'hari_praktik' => 'Senin',
            'index_hari' => 1,
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '12:00:00',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $payload = [
            'doctor_id' => 5,
            'polyclinic_id' => 10,
            'day_of_week' => 1,
            'start_time' => '07:00',
            'end_time' => '13:00',
            'is_holiday' => false,
            'online_quota' => 15,
        ];

        $response = $this->putJson('/api/v1/master-data/doctor-schedules/77', $payload);
        $response->assertStatus(200);
        $response->assertJsonPath('data.start_time', '07:00');
        $response->assertJsonPath('data.end_time', '13:00');
    }

    public function test_can_soft_delete_schedule(): void
    {
        DB::connection('legacy')->table('trx_jadwal_dokter')->insert([
            'id' => 88,
            'id_dokter' => 5,
            'id_poliklinik' => 10,
            'hari_praktik' => 'Rabu',
            'index_hari' => 3,
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '12:00:00',
            'status' => '1',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->authUser);

        $response = $this->deleteJson('/api/v1/master-data/doctor-schedules/88');
        $response->assertStatus(204);

        // Verify status changed to 0 and id_user updated in legacy db
        $legacyRow = DB::connection('legacy')->table('trx_jadwal_dokter')->where('id', 88)->first();
        $this->assertEquals('0', $legacyRow->status);
        $this->assertEquals(103, $legacyRow->id_user);
        $this->assertNotNull($legacyRow->deleted_at);

        // Verify no longer returned in list
        $listResponse = $this->getJson('/api/v1/master-data/doctor-schedules');
        $listResponse->assertStatus(200);
        $listResponse->assertJsonCount(0, 'data');
    }
}
