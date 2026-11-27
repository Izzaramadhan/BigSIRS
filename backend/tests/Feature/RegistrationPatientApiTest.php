<?php

namespace Tests\Feature;

use App\Models\Legacy\LegacyAdmission;
use App\Models\Legacy\LegacyPatient;
use App\Models\Legacy\LegacyVisit;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RegistrationPatientApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::factory()->create([
            'username' => 'pendaftaran_ro_test',
            'email' => 'pendaftaran_ro@simrs.test',
        ]);

        $this->createLegacySchema();
        $this->seedLegacyData();
        LegacyAccess::clearCache();
    }

    protected function createLegacySchema(): void
    {
        $schema = Schema::connection('legacy');

        foreach ([
            'trx_billing', 'trx_bed', 'trx_rad', 'trx_lab', 'trx_resep',
            'trx_visit', 'trx_admisi', 'ref_pasien', 'ref_dokter', 'ref_poliklinik',
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
            $table->string('kode_kelurahan', 10)->nullable();
            $table->string('id_kecamatan', 7);
            $table->string('nama', 100);
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('ref_pendidikan', function (Blueprint $table) {
            $table->increments('id');
            $table->string('pendidikan', 100);
            $table->string('status', 1)->default('1');
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('ref_pekerjaan', function (Blueprint $table) {
            $table->increments('id');
            $table->string('pekerjaan', 100);
            $table->string('status', 1)->default('1');
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('ref_jenis_asuransi', function (Blueprint $table) {
            $table->increments('id');
            $table->string('jenis_asuransi', 100);
            $table->string('type', 20)->nullable();
            $table->integer('pemerintah')->default(0);
            $table->string('status', 1)->default('1');
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('ref_poliklinik', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama', 100);
            $table->string('status', 1)->default('1');
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
            $table->string('no_hp', 50);
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

        $schema->create('ref_dokter', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_mpi')->nullable();
            $table->string('status', 1)->default('1');
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

        $schema->create('trx_admisi', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_pasien');
            $table->dateTime('waktu_admisi')->nullable();
            $table->dateTime('waktu_keluar')->nullable();
            $table->text('alasan_datang')->nullable();
            $table->integer('id_jenis_asuransi')->nullable();
            $table->string('no_bpjs', 30)->nullable();
            $table->string('cara_masuk', 30)->nullable();
            $table->string('status', 1)->default('1');
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('trx_visit', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_admisi');
            $table->integer('id_poliklinik')->nullable();
            $table->integer('id_dokter')->default(0);
            $table->string('kasus', 50)->nullable();
            $table->string('status_dilayani', 20)->default('Tidak');
            $table->string('cek_lunas_visit', 20)->default('belum bayar');
            $table->dateTime('waktu_mulai_periksa')->nullable();
            $table->dateTime('waktu_selesai_periksa')->nullable();
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('trx_resep', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_visit');
            $table->integer('dokter_pengirim')->nullable();
            $table->text('keterangan')->nullable();
            $table->text('naratif')->nullable();
            $table->string('status_resep', 50)->default('selesai');
            $table->string('penyerahan_obat', 20)->default('Sudah');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('trx_lab', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_visit');
            $table->integer('dokter_pengirim')->nullable();
            $table->string('no_register', 100)->nullable();
            $table->string('jenis', 50)->nullable();
            $table->string('status_pemeriksaan', 50)->default('selesai');
            $table->text('resume')->nullable();
            $table->dateTime('waktu_ambil_sample')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('trx_rad', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_visit');
            $table->integer('dokter_pengirim')->nullable();
            $table->string('accession_number', 100)->nullable();
            $table->string('status_pemeriksaan', 50)->default('selesai');
            $table->text('catatan')->nullable();
            $table->dateTime('waktu_periksa')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('trx_bed', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_visit');
            $table->integer('id_bed')->default(1);
            $table->string('cek_ranap', 50)->default('Ranap');
            $table->string('cara_tiba', 100)->nullable();
            $table->dateTime('waktu_masuk_ranap')->nullable();
            $table->dateTime('waktu_keluar_ranap')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('trx_billing', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_admisi');
            $table->integer('id_visit')->nullable();
            $table->string('no_invoice', 100)->nullable();
            $table->string('cara_bayar', 50)->default('Tunai');
            $table->bigInteger('total_bayar')->default(150000);
            $table->bigInteger('bayar')->default(150000);
            $table->string('status_lunas', 50)->default('sudah bayar');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    protected function seedLegacyData(): void
    {
        $db = DB::connection('legacy');

        $db->table('ref_provinsi')->insert([
            ['id' => '33', 'nama' => 'JAWA TENGAH', 'status' => 1],
            ['id' => '34', 'nama' => 'DI YOGYAKARTA', 'status' => 1],
        ]);
        $db->table('ref_kabupaten')->insert([
            ['id' => '3305', 'id_provinsi' => '33', 'nama' => 'KABUPATEN KEBUMEN', 'status' => 1],
            ['id' => '3404', 'id_provinsi' => '34', 'nama' => 'KABUPATEN SLEMAN', 'status' => 1],
        ]);
        $db->table('ref_kecamatan')->insert([
            ['id' => '3305081', 'id_kabupaten' => '3305', 'nama' => 'BONOROWO', 'status' => 1],
            ['id' => '3404020', 'id_kabupaten' => '3404', 'nama' => 'MINGGIR', 'status' => 1],
        ]);
        $db->table('ref_kelurahan')->insert([
            ['id' => 32743, 'kode_kelurahan' => '3305081001', 'id_kecamatan' => '3305081', 'nama' => 'TLOGOREJO', 'status' => 1],
            ['id' => 39774, 'kode_kelurahan' => '3404020002', 'id_kecamatan' => '3404020', 'nama' => 'SENDANG MULYO', 'status' => 1],
        ]);

        $db->table('ref_pendidikan')->insert(['id' => 7, 'pendidikan' => 'S1', 'status' => '1']);
        $db->table('ref_pekerjaan')->insert(['id' => 11, 'pekerjaan' => 'Karyawan', 'status' => '1']);
        $db->table('ref_jenis_asuransi')->insert([
            ['id' => 2, 'jenis_asuransi' => 'UMUM', 'type' => 'UMUM', 'status' => '1'],
            ['id' => 3, 'jenis_asuransi' => 'JKN', 'type' => 'BPJS', 'status' => '1'],
        ]);
        $db->table('ref_poliklinik')->insert(['id' => 14, 'nama' => 'Poliklinik Umum', 'status' => '1']);

        $db->table('master_person_index')->insert([
            [
                'id' => 204577,
                'no_ktp' => 47871018,
                'nama' => 'Person 49507634',
                'gender' => 'L',
                'tgl_lahir' => '2000-12-12',
                'tempat_lahir' => 'Jepara',
                'alamat' => 'Desa Tlogorejo',
                'id_provinsi' => '33',
                'id_kabupaten' => '3305',
                'id_kecamatan' => '3305081',
                'id_kelurahan' => '32743',
                'kode_pos' => '54395',
                'no_hp' => '081234567890',
                'agama' => 'Islam',
                'kebangsaan' => 'WNI',
                'gol_darah' => 'O',
                'status_perkawinan' => 'Belum Kawin',
                'id_pendidikan' => 7,
                'id_pekerjaan' => 11,
                'nama_keluarga_dekat' => 'Person Wali',
                'hubungan_keluarga_dekat' => 'Orang Tua',
                'nohp_keluarga_dekat' => '08111111111',
                'alamat_keluarga_dekat' => 'Jepara',
                'status' => '1',
                'deleted_at' => null,
            ],
            [
                'id' => 204591,
                'no_ktp' => 55667788,
                'nama' => 'Person 49511022',
                'gender' => 'P',
                'tgl_lahir' => '1995-08-20',
                'tempat_lahir' => 'Kudus',
                'alamat' => 'Kudus',
                'id_provinsi' => '33',
                'id_kabupaten' => '3305',
                'id_kecamatan' => '3305081',
                'id_kelurahan' => '32743',
                'kode_pos' => null,
                'no_hp' => '082233445566',
                'agama' => 'Islam',
                'kebangsaan' => 'WNI',
                'gol_darah' => '-',
                'status_perkawinan' => 'Kawin',
                'id_pendidikan' => 7,
                'id_pekerjaan' => 11,
                'nama_keluarga_dekat' => null,
                'hubungan_keluarga_dekat' => null,
                'nohp_keluarga_dekat' => null,
                'alamat_keluarga_dekat' => null,
                'status' => '1',
                'deleted_at' => '0000-00-00 00:00:00',
            ],
            [
                'id' => 999,
                'no_ktp' => 11112222,
                'nama' => 'Person Terhapus',
                'gender' => 'L',
                'tgl_lahir' => '1990-01-01',
                'tempat_lahir' => 'Pati',
                'alamat' => 'Pati',
                'id_provinsi' => null,
                'id_kabupaten' => null,
                'id_kecamatan' => null,
                'id_kelurahan' => null,
                'kode_pos' => null,
                'no_hp' => '-',
                'agama' => null,
                'kebangsaan' => 'WNI',
                'gol_darah' => '-',
                'status_perkawinan' => null,
                'id_pendidikan' => null,
                'id_pekerjaan' => null,
                'nama_keluarga_dekat' => null,
                'hubungan_keluarga_dekat' => null,
                'nohp_keluarga_dekat' => null,
                'alamat_keluarga_dekat' => null,
                'status' => '1',
                'deleted_at' => null,
            ],
            [
                'id' => 62,
                'no_ktp' => null,
                'nama' => 'dr. Dokter Umum',
                'gender' => 'L',
                'tgl_lahir' => null,
                'tempat_lahir' => null,
                'alamat' => null,
                'id_provinsi' => null,
                'id_kabupaten' => null,
                'id_kecamatan' => null,
                'id_kelurahan' => null,
                'kode_pos' => null,
                'no_hp' => '-',
                'agama' => null,
                'kebangsaan' => 'WNI',
                'gol_darah' => '-',
                'status_perkawinan' => null,
                'id_pendidikan' => null,
                'id_pekerjaan' => null,
                'nama_keluarga_dekat' => null,
                'hubungan_keluarga_dekat' => null,
                'nohp_keluarga_dekat' => null,
                'alamat_keluarga_dekat' => null,
                'status' => '1',
                'deleted_at' => null,
            ],
        ]);

        $db->table('ref_dokter')->insert(['id' => 62, 'id_mpi' => 62, 'status' => '1']);

        $db->table('ref_pasien')->insert([
            [
                'id' => 777,
                'id_mpi' => 204577,
                'id_jenis_asuransi' => 2,
                'no_bpjs' => null,
                'no_rm' => '77-16-16',
                'alergi' => 'tidak',
                'ket_alergi' => null,
                'fast_track' => 'tidak',
                'status' => '1',
                'deleted_at' => null,
            ],
            [
                'id' => 789,
                'id_mpi' => 204591,
                'id_jenis_asuransi' => 3,
                'no_bpjs' => '000999888777',
                'no_rm' => '000175',
                'alergi' => 'ada',
                'ket_alergi' => 'Debu',
                'fast_track' => 'ya',
                'status' => '1',
                'deleted_at' => '0000-00-00 00:00:00',
            ],
            [
                'id' => 788,
                'id_mpi' => 999,
                'id_jenis_asuransi' => 2,
                'no_bpjs' => null,
                'no_rm' => '000174',
                'alergi' => 'tidak',
                'ket_alergi' => null,
                'fast_track' => 'tidak',
                'status' => '0',
                'deleted_at' => '2026-09-10 12:31:49',
            ],
        ]);

        $db->table('trx_admisi')->insert([
            'id' => 464,
            'id_pasien' => 777,
            'waktu_admisi' => '2024-11-14 08:33:00',
            'alasan_datang' => 'mual muntah',
            'id_jenis_asuransi' => 2,
            'cara_masuk' => 'poliklinik',
            'status' => '1',
            'deleted_at' => null,
        ]);

        $db->table('trx_visit')->insert([
            'id' => 695,
            'id_admisi' => 464,
            'id_poliklinik' => 14,
            'id_dokter' => 62,
            'kasus' => 'pemeriksaan kasus baru',
            'status_dilayani' => 'Proses',
            'cek_lunas_visit' => 'belum bayar',
            'waktu_mulai_periksa' => '2024-11-14 08:45:00',
            'status' => 1,
            'deleted_at' => '0000-00-00 00:00:00',
        ]);

        $db->table('trx_resep')->insert([
            'id' => 101,
            'id_visit' => 695,
            'dokter_pengirim' => 62,
            'keterangan' => 'Paracetamol 500mg tab',
            'status_resep' => 'selesai',
            'penyerahan_obat' => 'Sudah',
            'created_at' => '2024-11-14 09:15:00',
        ]);

        $db->table('trx_lab')->insert([
            'id' => 201,
            'id_visit' => 695,
            'dokter_pengirim' => 62,
            'no_register' => 'LAB-2024-001',
            'jenis' => 'Hematologi Lengkap',
            'status_pemeriksaan' => 'selesai',
            'resume' => 'Hb normal, leukosit normal',
            'waktu_ambil_sample' => '2024-11-14 09:30:00',
            'created_at' => '2024-11-14 09:30:00',
        ]);

        $db->table('trx_billing')->insert([
            'id' => 301,
            'id_admisi' => 464,
            'id_visit' => 695,
            'no_invoice' => 'INV-2024-11-001',
            'cara_bayar' => 'Tunai',
            'total_bayar' => 175000,
            'bayar' => 175000,
            'status_lunas' => 'sudah bayar',
            'created_at' => '2024-11-14 10:00:00',
        ]);
    }

    public function test_unauthenticated_and_read_only_guards(): void
    {
        $this->getJson('/api/v1/patients/search?q=77-16-16')->assertStatus(401);
        $this->getJson('/api/v1/patients/777')->assertStatus(401);
        $this->getJson('/api/v1/patients/777/admissions')->assertStatus(401);
        $this->postJson('/api/v1/patients', [])->assertStatus(401);

        foreach ([new LegacyAdmission(), new LegacyVisit()] as $model) {
            $threw = false;
            try {
                $model->save();
            } catch (\RuntimeException) {
                $threw = true;
            }
            $this->assertTrue($threw, get_class($model) . ' harus tetap read-only');
        }
    }

    public function test_store_patient_successfully_creates_record_and_generates_rm(): void
    {
        Sanctum::actingAs($this->user);

        \Illuminate\Support\Facades\DB::connection('legacy')->beginTransaction();

        try {
            $payload = [
                'nik' => '3201998877665544',
                'name' => 'Pasien Baru Uji Integrasi',
                'birth_place' => 'Sleman',
                'birth_date' => '1998-08-17',
                'gender' => 'L',
                'nationality' => 'WNI',
                'address' => 'Jl. Kaliurang KM 5',
                'phone' => '081234567899',
                'guarantor' => 'UMUM (Bayar Mandiri)',
                'blood_type' => 'O',
                'religion' => 'ISLAM',
                'marital_status' => 'BELUM KAWIN',
                'occupation' => 'WIRASWASTA',
                'education' => 'SARJANA (D4 / S1)',
                'has_allergy' => 'Ada',
                'allergy_notes' => 'Alergi Dingin',
                'is_fast_track' => 'Tidak',
                'emergency_contact' => [
                    'name' => 'Wali Uji',
                    'relationship' => 'Orang Tua',
                    'phone' => '081299998888',
                    'address' => 'Jl. Kaliurang KM 5',
                ],
            ];

            $response = $this->postJson('/api/v1/patients', $payload);

            $response->assertStatus(201)
                ->assertJsonPath('data.name', 'Pasien Baru Uji Integrasi')
                ->assertJsonPath('data.nik', '3201998877665544')
                ->assertJsonPath('data.medical_record_number', '000176')
                ->assertJsonPath('data.allergies.status', 'ada')
                ->assertJsonPath('data.allergies.notes', 'Alergi Dingin');

            // Verifikasi pasien baru dapat dicari langsung via search
            $searchRes = $this->getJson('/api/v1/patients/search?q=000176');
            $searchRes->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.name', 'Pasien Baru Uji Integrasi');
        } finally {
            \Illuminate\Support\Facades\DB::connection('legacy')->rollBack();
        }
    }

    public function test_search_supports_rm_with_and_without_hyphen_name_nik_dob_and_pagination(): void
    {
        Sanctum::actingAs($this->user);

        // 77-16-16 & 771616 keduanya menemukan pasien 777
        $this->getJson('/api/v1/patients/search?q=77-16-16')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 777)
            ->assertJsonPath('data.0.nik_masked', '4787****')
            ->assertJsonMissing(['no_bpjs', 'ket_alergi']);

        $this->getJson('/api/v1/patients/search?q=771616')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 777);

        // 000175 menemukan pasien 789
        $this->getJson('/api/v1/patients/search?q=000175')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 789);

        // Nama parsial case-insensitive & NIK, hanya pasien aktif
        $this->getJson('/api/v1/patients/search?q=person')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/v1/patients/search?q=47871018')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 777);

        // AYU kosong pada data masking
        $this->getJson('/api/v1/patients/search?q=AYU')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // Filter tanggal lahir & pagination withQueryString
        $resPage = $this->getJson('/api/v1/patients/search?q=Person&per_page=1');
        $resPage->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1);
        $this->assertStringContainsString('q=Person', (string) $resPage->json('links.next'));
        $this->assertStringContainsString('per_page=1', (string) $resPage->json('links.next'));

        $this->getJson('/api/v1/patients/search?q=Person&birth_date=2000-12-12')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 777);

        // Filter by Guarantor (Master ID & Legacy String fallback)
        $this->getJson('/api/v1/patients/search?guarantor=2')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', 777);

        $this->getJson('/api/v1/patients/search?guarantor=3')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', 789);

        // Guarantor ID with no matching patients returns clean empty list
        $this->getJson('/api/v1/patients/search?guarantor=4')
            ->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonCount(0, 'data');

        // Text fallback
        $this->getJson('/api/v1/patients/search?guarantor=UMUM')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', 777);

        // Filter 'all'
        $this->getJson('/api/v1/patients/search?guarantor=all')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        // Validation
        $this->getJson('/api/v1/patients/search?q=a')->assertStatus(422);
        $this->getJson('/api/v1/patients/search?per_page=51')->assertStatus(422);
    }

    public function test_patient_detail_admissions_history_and_wilayah_lookup(): void
    {
        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/patients/777')
            ->assertOk()
            ->assertJsonPath('data.id', 777)
            ->assertJsonPath('data.person_id', 204577)
            ->assertJsonPath('data.medical_record_number', '77-16-16')
            ->assertJsonPath('data.region.province.name', 'JAWA TENGAH')
            ->assertJsonPath('data.region.village.name', 'TLOGOREJO')
            ->assertJsonPath('data.education.name', 'S1')
            ->assertJsonPath('data.occupation.name', 'Karyawan')
            ->assertJsonPath('data.emergency_contact.name', 'Person Wali');

        $this->getJson('/api/v1/patients/999999')->assertStatus(404);

        $this->getJson('/api/v1/patients/777/admissions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 464)
            ->assertJsonPath('data.0.visits.0.id', 695)
            ->assertJsonPath('data.0.visits.0.polyclinic.name', 'Poliklinik Umum')
            ->assertJsonPath('data.0.visits.0.doctor.name', 'dr. Dokter Umum');

        $this->getJson('/api/v1/lookups/villages?search=Tlogorejo')
            ->assertOk()
            ->assertJsonCount(1, null)
            ->assertJsonPath('0.name', 'TLOGOREJO')
            ->assertJsonPath('0.district_name', 'BONOROWO')
            ->assertJsonPath('0.regency_name', 'KABUPATEN KEBUMEN');
    }

    public function test_update_patient_updates_existing_record_without_changing_no_rm(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'name' => 'Nama Pasien Diperbarui',
            'phone' => '089912345678',
            'address' => 'Jl. Kaliurang KM 9 Sleman',
            'guarantor_id' => 2,
            'has_allergy' => 'ada',
            'allergy_notes' => 'Alergi Dingin & Debu',
            'is_fast_track' => 'ya',
        ];

        $response = $this->putJson('/api/v1/patients/789', $payload);
        $response->assertOk()
            ->assertJsonPath('data.id', 789)
            ->assertJsonPath('data.medical_record_number', '000175')
            ->assertJsonPath('data.name', 'Nama Pasien Diperbarui')
            ->assertJsonPath('data.phone', '089912345678')
            ->assertJsonPath('data.address', 'Jl. Kaliurang KM 9 Sleman')
            ->assertJsonPath('data.allergies.status', 'ada')
            ->assertJsonPath('data.allergies.notes', 'Alergi Dingin & Debu');
    }

    public function test_delete_guards_prevent_deleting_patient_with_admission_history_and_allow_soft_delete_for_patient_without_history(): void
    {
        Sanctum::actingAs($this->user);

        // 1. Pasien 777 memiliki admisi 464 -> tidak boleh dihapus
        $blockedRes = $this->deleteJson('/api/v1/patients/777');
        $blockedRes->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertStringContainsString('memiliki riwayat kunjungan/admisi', (string) $blockedRes->json('message'));

        // 2. Pasien 789 tidak memiliki admisi -> berhasil dinonaktifkan (soft delete)
        $successRes = $this->deleteJson('/api/v1/patients/789', [
            'reason' => 'Salah registrasi data',
        ]);
        $successRes->assertOk()
            ->assertJsonPath('success', true);
        $this->assertStringContainsString('berhasil dinonaktifkan', (string) $successRes->json('message'));

        // Verifikasi pasien 789 tidak lagi muncul di pencarian aktif
        $this->getJson('/api/v1/patients/search?q=000175')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_patient_timeline_returns_chronological_events_and_respects_patient_isolation(): void
    {
        Sanctum::actingAs($this->user);

        // Pasien 777 memiliki transaksi admisi, visit, resep, lab, dan billing
        $response = $this->getJson('/api/v1/patients/777/timeline');
        $response->assertOk();

        $events = $response->json('data');
        $this->assertIsArray($events);
        $this->assertCount(5, $events);

        // Verifikasi tipe event yang ada
        $types = array_column($events, 'type');
        $this->assertContains('admisi', $types);
        $this->assertContains('pemeriksaan', $types);
        $this->assertContains('farmasi', $types);
        $this->assertContains('laboratorium', $types);
        $this->assertContains('billing', $types);

        // Verifikasi event terurut menurun berdasarkan timestamp
        for ($i = 0; $i < count($events) - 1; $i++) {
            $tCurrent = strtotime($events[$i]['timestamp']);
            $tNext = strtotime($events[$i + 1]['timestamp']);
            $this->assertGreaterThanOrEqual($tNext, $tCurrent, "Event index $i harus lebih baru atau sama dengan index " . ($i + 1));
        }

        // Filter tipe event timeline
        $labOnly = $this->getJson('/api/v1/patients/777/timeline?type=laboratorium');
        $labOnly->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'laboratorium')
            ->assertJsonPath('data.0.unit', 'Laboratorium');

        // Pasien 789 tidak memiliki transaksi -> timeline kosong (bukan 404)
        $emptyTimeline = $this->getJson('/api/v1/patients/789/timeline');
        $emptyTimeline->assertOk()
            ->assertJsonCount(0, 'data');

        // Pasien tidak ada -> 404
        $this->getJson('/api/v1/patients/999999/timeline')->assertStatus(404);
    }

    public function test_village_lookup_returns_full_hierarchy_including_province(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/lookups/villages?search=Sendang+Mulyo');
        $response->assertOk()
            ->assertJsonCount(1, null)
            ->assertJsonPath('0.id', '39774')
            ->assertJsonPath('0.name', 'SENDANG MULYO')
            ->assertJsonPath('0.district_id', '3404020')
            ->assertJsonPath('0.district_name', 'MINGGIR')
            ->assertJsonPath('0.regency_id', '3404')
            ->assertJsonPath('0.regency_name', 'KABUPATEN SLEMAN')
            ->assertJsonPath('0.province_id', '34')
            ->assertJsonPath('0.province_name', 'DI YOGYAKARTA');
    }

    public function test_store_patient_validates_and_stores_complete_region_hierarchy(): void
    {
        Sanctum::actingAs($this->user);

        // 1. Success with Sendang Mulyo - Minggir - Sleman - DIY
        $payload = [
            'nik' => '3404021205980001',
            'name' => 'Pasien Sendang Mulyo',
            'birth_place' => 'Sleman',
            'birth_date' => '1998-05-12',
            'gender' => 'L',
            'address' => 'Sendang Mulyo RT 01 RW 02',
            'region' => 'SENDANG MULYO — Kec. MINGGIR — KABUPATEN SLEMAN — DI YOGYAKARTA',
            'province_id' => '34',
            'regency_id' => '3404',
            'district_id' => '3404020',
            'village_id' => '39774',
            'phone' => '081234567899',
            'guarantor_id' => 2,
        ];

        $res = $this->postJson('/api/v1/patients', $payload);
        $res->assertStatus(201)
            ->assertJsonPath('data.name', 'Pasien Sendang Mulyo')
            ->assertJsonPath('data.region.province.id', '34')
            ->assertJsonPath('data.region.province.name', 'DI YOGYAKARTA')
            ->assertJsonPath('data.region.regency.id', '3404')
            ->assertJsonPath('data.region.regency.name', 'KABUPATEN SLEMAN')
            ->assertJsonPath('data.region.district.id', '3404020')
            ->assertJsonPath('data.region.district.name', 'MINGGIR')
            ->assertJsonPath('data.region.village.id', '39774')
            ->assertJsonPath('data.region.village.name', 'SENDANG MULYO');

        // 2. Inconsistent hierarchy: Regency does not belong to Province
        $badHierarchyPayload = array_merge($payload, [
            'nik' => '3404021205980002',
            'province_id' => '33', // Jawa Tengah
            'regency_id' => '3404', // Sleman (belongs to DIY 34)
        ]);

        $badRes = $this->postJson('/api/v1/patients', $badHierarchyPayload);
        $badRes->assertStatus(422)
            ->assertJsonValidationErrors(['regency_id']);
        $this->assertStringContainsString('Kabupaten/Kota yang dipilih tidak berada di bawah Provinsi', $badRes->json('errors.regency_id.0'));

        // 3. Regency provided without Province
        $missingProvincePayload = array_merge($payload, [
            'nik' => '3404021205980003',
            'province_id' => null,
            'regency_id' => '3404',
        ]);
        $missingProvRes = $this->postJson('/api/v1/patients', $missingProvincePayload);
        $missingProvRes->assertStatus(422)
            ->assertJsonValidationErrors(['regency_id']);
        $this->assertStringContainsString('Kabupaten/Kota memerlukan pilihan Provinsi yang valid', $missingProvRes->json('errors.regency_id.0'));

        // 4. District provided without Regency
        $missingRegencyPayload = array_merge($payload, [
            'nik' => '3404021205980004',
            'regency_id' => null,
            'district_id' => '3404020',
        ]);
        $missingRegRes = $this->postJson('/api/v1/patients', $missingRegencyPayload);
        $missingRegRes->assertStatus(422)
            ->assertJsonValidationErrors(['district_id']);
        $this->assertStringContainsString('Kecamatan memerlukan pilihan Kabupaten/Kota yang valid', $missingRegRes->json('errors.district_id.0'));
    }

    public function test_patient_registration_and_update_validate_master_guarantor_and_preserve_historical_record(): void
    {
        Sanctum::actingAs($this->user);

        DB::connection('legacy')->table('ref_jenis_asuransi')->insert([
            ['id' => 10, 'jenis_asuransi' => 'Asuransi Perusahaan XYZ', 'type' => 'PRIVATE', 'status' => '1'],
            ['id' => 11, 'jenis_asuransi' => 'Asuransi Nonaktif', 'type' => 'PRIVATE', 'status' => '0'],
        ]);

        // 1. Rejects inactive master guarantor on create
        $this->postJson('/api/v1/patients', [
            'nik' => '3404021205980010',
            'name' => 'Pasien Jaminan Nonaktif',
            'birth_place' => 'Sleman',
            'birth_date' => '1995-01-10',
            'gender' => 'L',
            'address' => 'Jl. Magelang',
            'phone' => '081200011122',
            'guarantor_id' => 11,
        ])->assertStatus(422)->assertJsonValidationErrors(['guarantor_id']);

        // 2. Creates patient with active newly added master guarantor
        $createRes = $this->postJson('/api/v1/patients', [
            'nik' => '3404021205980011',
            'name' => 'Pasien Jaminan Aktif',
            'birth_place' => 'Sleman',
            'birth_date' => '1995-01-10',
            'gender' => 'P',
            'address' => 'Jl. Kaliurang',
            'phone' => '081200011133',
            'guarantor_id' => 10,
            'insurance_number' => 'POL-XYZ-001',
        ]);
        $createRes->assertCreated()
            ->assertJsonPath('data.guarantor.id', 10)
            ->assertJsonPath('data.guarantor.name', 'Asuransi Perusahaan XYZ')
            ->assertJsonPath('data.insurance_number', 'POL-XYZ-001');

        $patientId = (int) $createRes->json('data.id');

        // 3. Deactivate master guarantor after registration; guarantor delete is blocked by ref_pasien
        DB::connection('legacy')->table('ref_jenis_asuransi')->where('id', 10)->update(['status' => '0']);
        $this->deleteJson('/api/v1/master-data/guarantors/10')
            ->assertStatus(422);

        // 4. Updating patient keeping unchanged historical guarantor 10 succeeds, while changing to another inactive guarantor fails
        $this->putJson("/api/v1/patients/{$patientId}", [
            'name' => 'Pasien Jaminan Diperbarui',
            'guarantor_id' => 10,
            'insurance_number' => 'POL-XYZ-001',
        ])->assertOk()
            ->assertJsonPath('data.guarantor.id', 10)
            ->assertJsonPath('data.guarantor.name', 'Asuransi Perusahaan XYZ');

        $this->putJson("/api/v1/patients/{$patientId}", [
            'guarantor_id' => 11,
        ])->assertStatus(422)->assertJsonValidationErrors(['guarantor_id']);
    }

    public function test_partial_name_search_ayu_is_case_insensitive_whitespace_normalized_and_finds_newly_registered_patient(): void
    {
        // Tanpa autentikasi wajib ditolak 401
        $this->getJson('/api/v1/patients/search?q=AYU')->assertStatus(401);
        $this->postJson('/api/v1/patients', [])->assertStatus(401);

        Sanctum::actingAs($this->user);

        // Daftarkan pasien baru dengan nama mengandung AYU dan wilayah lengkap
        $createRes = $this->postJson('/api/v1/patients', [
            'nik' => '3404021205980099',
            'name' => 'SRI AYU LESTARI',
            'birth_place' => 'Sleman',
            'birth_date' => '1997-04-12',
            'gender' => 'P',
            'address' => 'Jl. Kebon Agung No. 12',
            'region' => 'SENDANG MULYO — Kec. MINGGIR — KABUPATEN SLEMAN — DI YOGYAKARTA',
            'province_id' => '34',
            'regency_id' => '3404',
            'district_id' => '3404020',
            'village_id' => '39774',
            'phone' => '081299988877',
            'guarantor_id' => 3,
            'insurance_number' => '0009988776655',
            'hak_kelas' => 'Kelas 1',
        ]);

        $createRes->assertCreated();
        $createdId = (int) $createRes->json('data.id');
        $createdRm = (string) $createRes->json('data.medical_record_number');
        $this->assertGreaterThan(0, $createdId);
        $this->assertNotEmpty($createdRm);

        // Pencarian partial name AYU (huruf besar, kecil, dan spasi ekstra) menemukan pasien baru yang sama tanpa duplikasi
        foreach (['AYU', 'ayu', '  sRi   aYu  '] as $term) {
            $searchRes = $this->getJson('/api/v1/patients/search?q=' . urlencode($term));
            $searchRes->assertOk();
            $rows = collect($searchRes->json('data'));
            $matchingIds = $rows->where('id', $createdId)->pluck('id')->all();
            $this->assertCount(1, $matchingIds, "Query '{$term}' harus mengembalikan tepat 1 baris pasien baru");
            $this->assertSame('SRI AYU LESTARI', $rows->firstWhere('id', $createdId)['name']);
        }

        // Pembacaan ulang detail mempertahankan ID, No. RM, penjamin, dan wilayah
        $this->getJson("/api/v1/patients/{$createdId}")
            ->assertOk()
            ->assertJsonPath('data.id', $createdId)
            ->assertJsonPath('data.medical_record_number', $createdRm)
            ->assertJsonPath('data.guarantor.id', 3)
            ->assertJsonPath('data.region.village.id', '39774');

        // Pencarian dengan kata kunci tidak cocok mengembalikan array kosong (bukan error)
        $this->getJson('/api/v1/patients/search?q=ZZZZTIDAKADA999')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // Registrasi dengan NIK duplikat ditolak 422 dan tidak menambah baris pasien
        $countBefore = LegacyPatient::count();
        $this->postJson('/api/v1/patients', [
            'nik' => '3404021205980099',
            'name' => 'DUPLIKAT AYU',
            'birth_place' => 'Sleman',
            'birth_date' => '1997-04-12',
            'gender' => 'P',
            'address' => 'Jl. Kebon Agung No. 12',
            'phone' => '081299988877',
            'guarantor_id' => 2,
        ])->assertStatus(422)->assertJsonValidationErrors(['nik']);
        $this->assertSame($countBefore, LegacyPatient::count());
    }
}

