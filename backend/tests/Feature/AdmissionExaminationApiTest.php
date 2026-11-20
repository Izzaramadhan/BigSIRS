<?php

namespace Tests\Feature;

use App\Models\Legacy\LegacyAdmission;
use App\Models\Legacy\LegacyDoctor;
use App\Models\Legacy\LegacyEducation;
use App\Models\Legacy\LegacyGuarantor;
use App\Models\Legacy\LegacyPatient;
use App\Models\Legacy\LegacyPerson;
use App\Models\Legacy\LegacyPolyclinic;
use App\Models\Legacy\LegacyVisit;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdmissionExaminationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::factory()->create([
            'username' => 'test_doctor_admisi',
            'email' => 'doctor_admisi@simrs.test',
        ]);

        $this->createLegacySchema();
        $this->seedLegacyData();
        LegacyAccess::clearCache();
    }

    protected function createLegacySchema(): void
    {
        $schema = Schema::connection('legacy');

        foreach ([
            'log_user', 'ref_asal_rujukan', 'ref_pegawai', 'trx_jadwal_dokter', 'trx_visit', 'trx_admisi', 'ref_pasien', 'ref_dokter', 'ref_poliklinik',
            'ref_jenis_asuransi', 'ref_pendidikan', 'ref_pekerjaan',
            'ref_kelurahan', 'ref_kecamatan', 'ref_kabupaten', 'ref_provinsi',
            'master_person_index',
        ] as $tbl) {
            $schema->dropIfExists($tbl);
        }

        $schema->create('ref_provinsi', function (Blueprint $table) {
            $table->string('id', 2)->primary();
            $table->string('nama', 100);
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('ref_kabupaten', function (Blueprint $table) {
            $table->string('id', 4)->primary();
            $table->string('id_provinsi', 2);
            $table->string('nama', 100);
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('ref_kecamatan', function (Blueprint $table) {
            $table->string('id', 7)->primary();
            $table->string('id_kabupaten', 4);
            $table->string('nama', 100);
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('ref_kelurahan', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_kecamatan', 7);
            $table->string('nama', 100);
            $table->string('kode_pos', 10)->nullable();
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('ref_pekerjaan', function (Blueprint $table) {
            $table->increments('id');
            $table->string('pekerjaan', 100);
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('ref_pendidikan', function (Blueprint $table) {
            $table->increments('id');
            $table->string('pendidikan', 100);
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('ref_jenis_asuransi', function (Blueprint $table) {
            $table->increments('id');
            $table->string('jenis_asuransi', 100);
            $table->string('type', 20)->default('UMUM');
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('master_person_index', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('no_ktp')->nullable();
            $table->string('nama', 50)->nullable();
            $table->string('gender', 1)->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('tempat_lahir', 50)->nullable();
            $table->string('alamat', 255)->nullable();
            $table->string('id_provinsi', 2)->nullable();
            $table->string('id_kabupaten', 4)->nullable();
            $table->string('id_kecamatan', 7)->nullable();
            $table->string('id_kelurahan', 10)->nullable();
            $table->string('kode_pos', 50)->nullable();
            $table->string('no_hp', 50)->nullable();
            $table->string('email', 50)->nullable();
            $table->string('agama', 20)->nullable();
            $table->string('kebangsaan', 10)->nullable();
            $table->string('gol_darah', 5)->nullable();
            $table->string('status_perkawinan', 30)->nullable();
            $table->integer('id_pendidikan')->nullable();
            $table->integer('id_pekerjaan')->nullable();
            $table->string('ihs_id', 255)->nullable();
            $table->string('hak_kelas', 50)->nullable();
            $table->string('nama_keluarga_dekat', 255)->nullable();
            $table->string('hubungan_keluarga_dekat', 255)->nullable();
            $table->string('nohp_keluarga_dekat', 15)->nullable();
            $table->text('alamat_keluarga_dekat')->nullable();
            $table->string('status', 1)->default('1');
            $table->string('jenis_person', 20)->default('pasien');
            $table->integer('id_user')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('ref_pasien', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_mpi')->nullable();
            $table->integer('log_id_mpi')->nullable();
            $table->integer('id_penanggung_jawab')->nullable();
            $table->integer('id_jenis_asuransi')->nullable();
            $table->string('no_bpjs', 50)->nullable();
            $table->string('no_rm', 50)->nullable();
            $table->string('alergi', 20)->default('tidak');
            $table->text('ket_alergi')->nullable();
            $table->string('fast_track', 10)->default('tidak');
            $table->text('ket_fast_track')->nullable();
            $table->string('type', 20)->default('pasien_rs');
            $table->string('flag_print', 1)->default('0');
            $table->integer('id_user')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->string('status', 1)->default('1');
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('ref_poliklinik', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama_poliklinik', 100);
            $table->string('kode_bpjs', 50)->nullable();
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('ref_dokter', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_mpi')->nullable();
            $table->string('nama_lengkap', 150)->nullable();
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
        });

        $schema->create('ref_pegawai', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_mpi')->nullable();
            $table->string('nip', 50)->nullable();
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
        });

        $schema->create('ref_asal_rujukan', function (Blueprint $table) {
            $table->increments('id');
            $table->string('kode', 20)->nullable();
            $table->string('nama', 100);
            $table->integer('status')->default(1);
            $table->integer('is_inacbg')->default(0);
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
        });

        $schema->create('log_user', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('waktu')->nullable();
            $table->string('user', 100)->nullable();
            $table->string('role', 100)->nullable();
            $table->text('log')->nullable();
            $table->string('status', 10)->default('1');
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
        });

        $schema->create('trx_admisi', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_pasien');
            $table->unsignedInteger('id_jenis_asuransi')->default(2);
            $table->string('no_bpjs', 50)->nullable();
            $table->unsignedInteger('id_asal_rujukan')->nullable();
            $table->string('asal_rujukan', 255)->nullable();
            $table->dateTime('waktu_admisi');
            $table->text('alasan_datang')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('cara_masuk', 50)->default('poliklinik');
            $table->string('kode_booking', 50)->nullable();
            $table->string('nama_keluarga_dekat', 255)->nullable();
            $table->string('hubungan_keluarga_dekat', 255)->nullable();
            $table->string('nohp_keluarga_dekat', 50)->nullable();
            $table->text('alamat_keluarga_dekat')->nullable();
            $table->string('status', 10)->default('1');
            $table->unsignedInteger('id_user')->default(103);
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
        });

        $schema->create('trx_visit', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_admisi');
            $table->unsignedInteger('id_poliklinik');
            $table->unsignedInteger('id_dokter');
            $table->string('kasus', 100)->nullable();
            $table->string('status_dilayani', 20)->default('Tidak');
            $table->string('status_triase', 20)->default('Tidak');
            $table->string('status_janjian', 20)->default('Tidak');
            $table->string('status_surveilans', 20)->default('Tidak');
            $table->dateTime('waktu_mulai_periksa')->nullable();
            $table->integer('status')->default(1);
            $table->string('status_coding', 30)->default('belum_coding');
            $table->string('cek_farmasi', 10)->default('Tidak');
            $table->string('cek_billing', 10)->default('Tidak');
            $table->string('cek_lunas_visit', 30)->default('belum bayar');
            $table->unsignedInteger('id_user')->default(103);
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
        });

        $schema->create('trx_jadwal_dokter', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_dokter');
            $table->unsignedInteger('id_poliklinik');
            $table->string('hari_praktik', 20)->nullable();
            $table->integer('index_hari')->nullable();
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->integer('libur')->default(0);
            $table->string('status', 1)->default('1');
            $table->date('tanggal_mulai_libur')->nullable();
            $table->date('tanggal_selesai_libur')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    protected function seedLegacyData(): void
    {
        $db = DB::connection('legacy');

        $db->table('ref_provinsi')->insert([
            ['id' => '34', 'nama' => 'DI YOGYAKARTA', 'status' => 1, 'deleted_at' => null],
            ['id' => '33', 'nama' => 'JAWA TENGAH', 'status' => 1, 'deleted_at' => null],
        ]);

        $db->table('ref_kabupaten')->insert([
            ['id' => '3471', 'id_provinsi' => '34', 'nama' => 'KOTA YOGYAKARTA', 'status' => 1, 'deleted_at' => null],
            ['id' => '3404', 'id_provinsi' => '34', 'nama' => 'KABUPATEN SLEMAN', 'status' => 1, 'deleted_at' => null],
            ['id' => '3374', 'id_provinsi' => '33', 'nama' => 'KOTA SEMARANG', 'status' => 1, 'deleted_at' => null],
        ]);

        $db->table('ref_kecamatan')->insert([
            ['id' => '3471010', 'id_kabupaten' => '3471', 'nama' => 'TEGALREJO', 'status' => 1, 'deleted_at' => null],
            ['id' => '3471070', 'id_kabupaten' => '3471', 'nama' => 'DANUREJAN', 'status' => 1, 'deleted_at' => null],
        ]);

        $db->table('ref_kelurahan')->insert([
            ['id' => 39882, 'id_kecamatan' => '3471070', 'nama' => 'BAUSASRAN', 'kode_pos' => '55211', 'status' => 1, 'deleted_at' => null],
        ]);

        $db->table('ref_pendidikan')->insert([
            ['id' => 1, 'pendidikan' => 'SD', 'status' => 1, 'deleted_at' => null],
            ['id' => 6, 'pendidikan' => 'S1 / SARJANA', 'status' => 1, 'deleted_at' => null],
        ]);

        $db->table('ref_pekerjaan')->insert([
            ['id' => 1, 'pekerjaan' => 'PNS', 'status' => 1, 'deleted_at' => null],
        ]);

        $db->table('ref_jenis_asuransi')->insert([
            ['id' => 1, 'jenis_asuransi' => 'BPJS Kesehatan', 'type' => 'BPJS', 'status' => 1, 'deleted_at' => null],
            ['id' => 2, 'jenis_asuransi' => 'UMUM', 'type' => 'UMUM', 'status' => 1, 'deleted_at' => null],
        ]);

        $mpiId = $db->table('master_person_index')->insertGetId([
            'nama' => 'SURYO BROTO',
            'no_ktp' => '3471011205860001',
            'tgl_lahir' => '1986-05-12',
            'gender' => 'L',
            'alamat' => 'Jl. Malioboro No. 45',
            'no_hp' => '081234567890',
            'id_provinsi' => '34',
            'id_kabupaten' => '3471',
            'id_kecamatan' => '3471070',
            'id_kelurahan' => 39882,
            'id_pendidikan' => 6,
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $patientId = $db->table('ref_pasien')->insertGetId([
            'id_mpi' => $mpiId,
            'no_rm' => 'RM-000001',
            'id_jenis_asuransi' => 2,
            'status' => 1,
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $db->table('ref_asal_rujukan')->insert([
            ['id' => 1, 'kode' => 'FKTP', 'nama' => 'FKT Primer', 'status' => 1, 'is_inacbg' => 1, 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'kode' => 'DS', 'nama' => 'Datang Sendiri', 'status' => 1, 'is_inacbg' => 0, 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $poliId = $db->table('ref_poliklinik')->insertGetId([
            'nama_poliklinik' => 'Poli Penyakit Dalam',
            'status' => 1,
            'deleted_at' => null,
        ]);

        $poliAnakId = $db->table('ref_poliklinik')->insertGetId([
            'nama_poliklinik' => 'Poli Anak',
            'status' => 1,
            'deleted_at' => null,
        ]);

        $docMpiId = $db->table('master_person_index')->insertGetId([
            'nama' => 'dr. Bambang Santoso, Sp.PD',
            'gender' => 'L',
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $docId = $db->table('ref_dokter')->insertGetId([
            'id_mpi' => $docMpiId,
            'nama_lengkap' => 'dr. Bambang Santoso, Sp.PD',
            'status' => 1,
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $db->table('trx_jadwal_dokter')->insert([
            [
                'id_dokter' => $docId,
                'id_poliklinik' => $poliId,
                'hari_praktik' => 'Senin',
                'index_hari' => 1,
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '14:00:00',
                'libur' => 0,
                'status' => '1',
                'deleted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_dokter' => $docId,
                'id_poliklinik' => $poliAnakId,
                'hari_praktik' => 'Senin',
                'index_hari' => 1,
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '14:00:00',
                'libur' => 0,
                'status' => '1',
                'deleted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $admId = $db->table('trx_admisi')->insertGetId([
            'id_pasien' => $patientId,
            'id_jenis_asuransi' => 2,
            'waktu_admisi' => now()->format('Y-m-d H:i:s'),
            'alasan_datang' => 'Pemeriksaan rutin kontrol',
            'cara_masuk' => 'poliklinik',
            'status' => '1',
            'id_user' => 103,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $db->table('trx_visit')->insertGetId([
            'id_admisi' => $admId,
            'id_poliklinik' => $poliId,
            'id_dokter' => $docId,
            'status_dilayani' => 'Tidak',
            'waktu_mulai_periksa' => now()->format('Y-m-d H:i:s'),
            'status' => 1,
            'cek_lunas_visit' => 'sudah bayar',
            'id_user' => 103,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_can_get_admissions_list(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/admissions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'admission_date',
                        'admission_time',
                        'queue_number',
                        'patient_id',
                        'medical_record_number',
                        'patient_name',
                        'polyclinic',
                        'doctor',
                        'guarantor',
                        'status',
                    ],
                ],
                'links',
                'meta',
            ]);

        $this->assertSame('—', $response->json('data.0.queue_number'));
        $this->assertSame('SURYO BROTO', $response->json('data.0.patient_name'));
        $this->assertSame('Poli Penyakit Dalam', $response->json('data.0.polyclinic'));
    }

    public function test_can_store_new_admission_and_visit(): void
    {
        Sanctum::actingAs($this->user);

        $patient = LegacyPatient::first();
        $polyclinic = LegacyPolyclinic::first();
        $doctor = LegacyDoctor::first();

        $payload = [
            'patient_id' => $patient->id,
            'polyclinic_id' => $polyclinic->id,
            'doctor_id' => $doctor->id,
            'notes' => 'Keluhan pusing dan batuk 3 hari',
        ];

        $response = $this->postJson('/api/v1/admissions', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.patient_name', 'SURYO BROTO')
            ->assertJsonPath('data.queue_number', '—');

        $newAdmissionId = $response->json('data.id');
        $this->assertDatabaseHas('trx_admisi', [
            'id' => $newAdmissionId,
            'id_pasien' => $patient->id,
            'alasan_datang' => 'Keluhan pusing dan batuk 3 hari',
        ], 'legacy');

        $this->assertDatabaseHas('trx_visit', [
            'id_admisi' => $newAdmissionId,
            'id_poliklinik' => $polyclinic->id,
            'id_dokter' => $doctor->id,
            'status_dilayani' => 'Tidak',
        ], 'legacy');
    }

    public function test_cannot_store_admission_when_patient_has_unpaid_bills(): void
    {
        Sanctum::actingAs($this->user);

        $db = DB::connection('legacy');
        $patient = LegacyPatient::first();
        $polyclinic = LegacyPolyclinic::first();
        $doctor = LegacyDoctor::first();

        // Mark visit as unpaid
        $db->table('trx_visit')
            ->where('id_admisi', $db->table('trx_admisi')->where('id_pasien', $patient->id)->value('id'))
            ->update(['cek_lunas_visit' => 'belum bayar']);

        $payload = [
            'patient_id' => $patient->id,
            'polyclinic_id' => $polyclinic->id,
            'doctor_id' => $doctor->id,
            'notes' => 'Mencoba daftar admisi saat billing belum lunas',
        ];

        $response = $this->postJson('/api/v1/admissions', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Pasien ini belum lunas pembayaran. Silahkan lakukan pembayaran di Billing. Atau silahkan hubungi petugas jika mengalami kesulitan.')
            ->assertJsonValidationErrors(['patient_id']);
    }

    public function test_can_get_examinations_list_showing_stored_visit(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/examinations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'visit_id',
                        'admission_id',
                        'patient_id',
                        'medical_record_number',
                        'patient_name',
                        'polyclinic',
                        'doctor',
                        'guarantor',
                        'queue_number',
                        'status',
                    ],
                ],
                'links',
                'meta',
            ]);

        $firstExam = $response->json('data.0');
        $this->assertSame('SURYO BROTO', $firstExam['patient_name']);
        $this->assertSame('Menunggu', $firstExam['status']);
        $this->assertSame('—', $firstExam['queue_number']);
    }

    public function test_validates_hierarchical_wilayah_combinations_on_registration(): void
    {
        Sanctum::actingAs($this->user);

        // Kasus 1: Kabupaten Kota Semarang (3374) tidak cocok dengan Provinsi DI Yogyakarta (34)
        $invalidPayload = [
            'nik' => '3471012304950009',
            'name' => 'Wahyudi Pratama',
            'gender' => 'L',
            'birth_date' => '1995-04-23',
            'address' => 'Jl. Kaliurang KM 5',
            'province_id' => '34',
            'regency_id' => '3374', // Mismatch!
            'education_id' => 6,
        ];

        $response = $this->postJson('/api/v1/patients', $invalidPayload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['regency_id']);

        // Kasus 2: Pasangan wilayah hierarki valid (Provinsi 34 -> Kabupaten 3471 -> Kecamatan 3471070 -> Kelurahan 39882)
        $validPayload = [
            'nik' => '3471012304950009',
            'name' => 'Wahyudi Pratama',
            'birth_place' => 'YOGYAKARTA',
            'birth_date' => '1995-04-23',
            'gender' => 'L',
            'phone' => '081298765432',
            'address' => 'Jl. Kaliurang KM 5',
            'province_id' => '34',
            'regency_id' => '3471',
            'district_id' => '3471070',
            'village_id' => 39882,
            'education_id' => 6,
        ];

        $validResponse = $this->postJson('/api/v1/patients', $validPayload);
        $validResponse->assertStatus(201)
            ->assertJsonPath('data.name', 'Wahyudi Pratama');
    }

    public function test_validates_education_master_id(): void
    {
        Sanctum::actingAs($this->user);

        // ID pendidikan 999 tidak ada di ref_pendidikan
        $invalidPayload = [
            'nik' => '3471012304950010',
            'name' => 'Dwi Handayani',
            'gender' => 'P',
            'birth_date' => '1996-08-14',
            'address' => 'Jl. Gejayan No. 12',
            'education_id' => 999, // Tidak valid
        ];

        $response = $this->postJson('/api/v1/patients', $invalidPayload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['education_id']);
    }

    public function test_doctor_lookup_by_polyclinic_and_schedule_handles_all_conditions(): void
    {
        Sanctum::actingAs($this->user);

        // 1. Kondisi A: Poliklinik tanpa penugasan dokter (ID 999)
        $respA = $this->getJson('/api/v1/lookups/doctors?polyclinic_id=999');
        $respA->assertStatus(200)
            ->assertJsonPath('meta.total_assigned_doctors', 0)
            ->assertJsonPath('meta.has_assigned_doctors', false)
            ->assertJsonCount(0, 'data');

        // 2. Kondisi B: Poliklinik ada dokter tapi tidak ada jadwal di hari Selasa (2026-11-17)
        // Dokter hanya bertugas Senin
        $respB = $this->getJson('/api/v1/lookups/doctors?polyclinic_id=1&date=17-11-2026');
        $respB->assertStatus(200)
            ->assertJsonPath('meta.total_assigned_doctors', 1)
            ->assertJsonPath('meta.has_assigned_doctors', true)
            ->assertJsonPath('meta.available_on_schedule', 0)
            ->assertJsonCount(0, 'data');

        // 3. Tersedia pada hari jadwal aktif (Senin: 16-11-2026)
        $respAvailable = $this->getJson('/api/v1/lookups/doctors?polyclinic_id=1&date=16-11-2026');
        $respAvailable->assertStatus(200)
            ->assertJsonPath('meta.total_assigned_doctors', 1)
            ->assertJsonPath('meta.has_assigned_doctors', true)
            ->assertJsonPath('meta.available_on_schedule', 1)
            ->assertJsonCount(1, 'data');
    }

    public function test_admission_validation_rejects_doctor_not_assigned_to_polyclinic(): void
    {
        Sanctum::actingAs($this->user);

        // Dokter 1 ditugaskan ke Poliklinik 1, tapi admisi memilih Poliklinik 999
        $invalidPayload = [
            'patient_id' => 1,
            'polyclinic_id' => 999,
            'doctor_id' => 1,
            'guarantor_id' => 2,
            'admission_date' => '2026-11-16',
        ];

        $response = $this->postJson('/api/v1/admissions', $invalidPayload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['doctor_id']);
    }

    public function test_update_admission_successfully_modifies_existing_records_without_creating_new_ones(): void
    {
        Sanctum::actingAs($this->user);

        $initialAdmissionCount = DB::connection('legacy')->table('trx_admisi')->count();
        $initialVisitCount = DB::connection('legacy')->table('trx_visit')->count();

        $admissionId = 1;
        $poliAnakId = 2;
        $doctorId = 1;

        $updatePayload = [
            'guarantor_id' => 1,
            'insurance_number' => '0001234567890',
            'referral_source_id' => 1,
            'referral_name' => 'Puskesmas Mantrijeron',
            'polyclinic_id' => $poliAnakId,
            'doctor_id' => $doctorId,
            'case_type' => 'Pemeriksaan Kasus Baru',
            'notes' => 'Pindah ke poli anak atas rujukan fktp',
            'family_name' => 'Budi Santoso',
            'family_relationship' => 'Orang Tua',
            'family_phone' => '081298765432',
            'family_address' => 'Jl. Malioboro No. 45 Yogyakarta',
            'reason' => 'Perubahan poliklinik tujuan dan pemutakhiran asuransi BPJS',
        ];

        $response = $this->putJson("/api/v1/admissions/{$admissionId}", $updatePayload);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $admissionId)
            ->assertJsonPath('data.polyclinic_id', $poliAnakId)
            ->assertJsonPath('data.polyclinic', 'Poli Anak')
            ->assertJsonPath('data.guarantor_id', 1)
            ->assertJsonPath('data.guarantor', 'BPJS Kesehatan')
            ->assertJsonPath('data.insurance_number', '0001234567890')
            ->assertJsonPath('data.family_name', 'Budi Santoso')
            ->assertJsonPath('data.case_type', 'Pemeriksaan Kasus Baru');

        // Pastikan jumlah record tidak bertambah (operasi UPDATE, BUKAN INSERT)
        $this->assertSame($initialAdmissionCount, DB::connection('legacy')->table('trx_admisi')->count());
        $this->assertSame($initialVisitCount, DB::connection('legacy')->table('trx_visit')->count());

        // Verifikasi database langsung
        $this->assertDatabaseHas('trx_admisi', [
            'id' => $admissionId,
            'id_jenis_asuransi' => 1,
            'no_bpjs' => '0001234567890',
            'id_asal_rujukan' => 1,
            'nama_keluarga_dekat' => 'Budi Santoso',
        ], 'legacy');

        $this->assertDatabaseHas('trx_visit', [
            'id_admisi' => $admissionId,
            'id_poliklinik' => $poliAnakId,
            'id_dokter' => $doctorId,
            'kasus' => 'Pemeriksaan Kasus Baru',
        ], 'legacy');

        // Verifikasi audit log tercatat di log_user
        $this->assertDatabaseHas('log_user', [
            'role' => 'pendaftaran',
        ], 'legacy');
    }

    public function test_update_admission_blocks_polyclinic_change_when_examination_in_progress(): void
    {
        Sanctum::actingAs($this->user);

        $db = DB::connection('legacy');
        $admissionId = 1;

        // Pasien sedang diperiksa di poliklinik asal (status_dilayani = 'Proses')
        $db->table('trx_visit')->where('id_admisi', $admissionId)->update([
            'status_dilayani' => 'Proses',
        ]);

        $updatePayload = [
            'guarantor_id' => 2,
            'polyclinic_id' => 2, // Ingin ubah ke Poli Anak
            'doctor_id' => 1,
            'case_type' => 'Pemeriksaan Kasus Baru',
        ];

        $response = $this->putJson("/api/v1/admissions/{$admissionId}", $updatePayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['polyclinic_id']);

        $this->assertStringContainsString('proses pemeriksaan klinis', $response->json('message'));
    }

    public function test_update_admission_locks_changes_when_service_completed_or_billed(): void
    {
        Sanctum::actingAs($this->user);

        $db = DB::connection('legacy');
        $admissionId = 1;

        // Pasien sudah selesai diperiksa (status_dilayani = 'Ya')
        $db->table('trx_visit')->where('id_admisi', $admissionId)->update([
            'status_dilayani' => 'Ya',
        ]);

        $updatePayload = [
            'guarantor_id' => 2,
            'polyclinic_id' => 2,
            'doctor_id' => 1,
            'case_type' => 'Pemeriksaan Kasus Lama',
        ];

        $response = $this->putJson("/api/v1/admissions/{$admissionId}", $updatePayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['polyclinic_id']);

        $this->assertStringContainsString('sudah selesai', $response->json('message'));
    }

    public function test_update_admission_rejects_doctor_not_scheduled_for_new_clinic(): void
    {
        Sanctum::actingAs($this->user);

        $admissionId = 1;

        // Poliklinik ID 999 tidak memiliki jadwal untuk Dokter ID 1
        $updatePayload = [
            'guarantor_id' => 2,
            'polyclinic_id' => 999,
            'doctor_id' => 1,
        ];

        $response = $this->putJson("/api/v1/admissions/{$admissionId}", $updatePayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['doctor_id']);
    }

    public function test_store_admission_with_complete_fields_stores_all_attributes_accurately(): void
    {
        Sanctum::actingAs($this->user);

        $patient = LegacyPatient::first();
        $polyclinic = LegacyPolyclinic::first();
        $doctor = LegacyDoctor::first();

        $payload = [
            'patient_id' => $patient->id,
            'admission_datetime' => '2026-11-18 10:30:00',
            'guarantor_id' => 1, // BPJS
            'insurance_number' => '0001234567890',
            'referral_source_id' => 1,
            'referral_name' => 'Puskesmas Sleman',
            'polyclinic_id' => $polyclinic->id,
            'doctor_id' => $doctor->id,
            'case_type' => 'pemeriksaan kasus baru',
            'emergency_contact' => [
                'name' => 'Siti Aminah',
                'relationship' => 'Istri',
                'phone' => '081987654321',
                'address' => 'Jl. Magelang KM 5',
            ],
            'notes' => 'Pasien rujukan batuk kronis',
        ];

        $response = $this->postJson('/api/v1/admissions', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.patient_name', 'SURYO BROTO')
            ->assertJsonPath('data.polyclinic_id', $polyclinic->id)
            ->assertJsonPath('data.doctor_id', $doctor->id)
            ->assertJsonPath('data.guarantor_id', 1)
            ->assertJsonPath('data.insurance_number', '0001234567890')
            ->assertJsonPath('data.referral_source_id', 1)
            ->assertJsonPath('data.referral_name', 'Puskesmas Sleman')
            ->assertJsonPath('data.case_type', 'pemeriksaan kasus baru')
            ->assertJsonPath('data.family_name', 'Siti Aminah')
            ->assertJsonPath('data.family_relationship', 'Istri')
            ->assertJsonPath('data.family_phone', '081987654321')
            ->assertJsonPath('data.family_address', 'Jl. Magelang KM 5');

        $newAdmissionId = $response->json('data.id');
        $newVisitId = $response->json('data.visit_id');

        $this->assertNotNull($newAdmissionId);
        $this->assertNotNull($newVisitId);

        // Verify direct database storage in trx_admisi
        $this->assertDatabaseHas('trx_admisi', [
            'id' => $newAdmissionId,
            'id_pasien' => $patient->id,
            'id_jenis_asuransi' => 1,
            'no_bpjs' => '0001234567890',
            'id_asal_rujukan' => 1,
            'asal_rujukan' => 'Puskesmas Sleman',
            'alasan_datang' => 'Pasien rujukan batuk kronis',
            'nama_keluarga_dekat' => 'Siti Aminah',
            'hubungan_keluarga_dekat' => 'Istri',
            'nohp_keluarga_dekat' => '081987654321',
            'alamat_keluarga_dekat' => 'Jl. Magelang KM 5',
        ], 'legacy');

        // Verify direct database storage in trx_visit
        $this->assertDatabaseHas('trx_visit', [
            'id' => $newVisitId,
            'id_admisi' => $newAdmissionId,
            'id_poliklinik' => $polyclinic->id,
            'id_dokter' => $doctor->id,
            'kasus' => 'pemeriksaan kasus baru',
            'status_dilayani' => 'Tidak',
            'cek_farmasi' => 'Tidak',
            'cek_billing' => 'Tidak',
        ], 'legacy');

        // Verify reading back through GET /admissions/{id}
        $getResponse = $this->getJson("/api/v1/admissions/{$newAdmissionId}");
        $getResponse->assertStatus(200)
            ->assertJsonPath('data.id', $newAdmissionId)
            ->assertJsonPath('data.visit_id', $newVisitId)
            ->assertJsonPath('data.insurance_number', '0001234567890')
            ->assertJsonPath('data.referral_source_id', 1)
            ->assertJsonPath('data.referral_name', 'Puskesmas Sleman')
            ->assertJsonPath('data.case_type', 'pemeriksaan kasus baru')
            ->assertJsonPath('data.emergency_contact.name', 'Siti Aminah')
            ->assertJsonPath('data.emergency_contact.relationship', 'Istri');
    }

    public function test_store_admission_idempotency_prevents_duplicate_submission_on_retry(): void
    {
        Sanctum::actingAs($this->user);

        $patient = LegacyPatient::first();
        $polyclinic = LegacyPolyclinic::first();
        $doctor = LegacyDoctor::first();

        $idempotencyKey = 'IDEMP-' . uniqid();

        $payload = [
            'patient_id' => $patient->id,
            'polyclinic_id' => $polyclinic->id,
            'doctor_id' => $doctor->id,
            'guarantor_id' => 2,
            'idempotency_key' => $idempotencyKey,
            'notes' => 'Pemeriksaan tensi',
        ];

        $initialAdmissionCount = LegacyAdmission::count();
        $initialVisitCount = LegacyVisit::count();

        // First attempt (creates new records)
        $firstResponse = $this->postJson('/api/v1/admissions', $payload);
        $firstResponse->assertStatus(201);
        $admissionId = $firstResponse->json('data.id');

        $this->assertEquals($initialAdmissionCount + 1, LegacyAdmission::count());
        $this->assertEquals($initialVisitCount + 1, LegacyVisit::count());

        // Second attempt with same idempotency key (simulating retry after timeout/glitch)
        $secondResponse = $this->postJson('/api/v1/admissions', $payload);
        $secondResponse->assertStatus(200)
            ->assertJsonPath('data.id', $admissionId);

        // Verify count did not increase (NO DUPLICATE)
        $this->assertEquals($initialAdmissionCount + 1, LegacyAdmission::count());
        $this->assertEquals($initialVisitCount + 1, LegacyVisit::count());
    }

    public function test_store_admission_validates_required_patient_and_clinic(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/admissions', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['patient_id', 'polyclinic_id', 'doctor_id']);
    }

    public function test_store_admission_atomic_rollback_on_failure(): void
    {
        Sanctum::actingAs($this->user);

        $patient = LegacyPatient::first();
        $polyclinic = LegacyPolyclinic::first();
        $doctor = LegacyDoctor::first();

        $initialAdmissionCount = LegacyAdmission::count();
        $initialVisitCount = LegacyVisit::count();

        // Simulate an unexpected failure during LegacyVisit saving to trigger DB::transaction rollback
        LegacyVisit::saving(function () {
            throw new \RuntimeException('Simulated visit insert failure for rollback verification');
        });

        $payload = [
            'patient_id' => $patient->id,
            'polyclinic_id' => $polyclinic->id,
            'doctor_id' => $doctor->id,
            'guarantor_id' => 2,
            'notes' => 'Pemeriksaan yang gagal simpan visit',
        ];

        $response = $this->postJson('/api/v1/admissions', $payload);

        $response->assertStatus(500);

        // Assert atomic rollback: neither admission nor visit was saved
        $this->assertEquals($initialAdmissionCount, LegacyAdmission::count());
        $this->assertEquals($initialVisitCount, LegacyVisit::count());
    }
}
