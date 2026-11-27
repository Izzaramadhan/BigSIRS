<?php

namespace Tests\Feature;

use App\Models\Legacy\LegacyBilling;
use App\Models\Legacy\LegacyBillingDetail;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BillingApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::factory()->create([
            'username' => 'kasir_billing_test',
            'email' => 'kasir@simrs.test',
        ]);

        $this->createLegacyBillingSchema();
        $this->seedLegacyBillingData();
        LegacyAccess::clearCache();
    }

    protected function createLegacyBillingSchema(): void
    {
        $schema = Schema::connection('legacy');

        foreach ([
            'trx_billing_detail', 'trx_billing',
            'trx_rad_detail_pemeriksaan', 'trx_rad',
            'trx_lab_detail_pemeriksaan', 'trx_lab',
            'trx_resep_detail', 'trx_resep',
            'trx_tindakan', 'ref_tarif_tindakan',
            'trx_visit', 'trx_admisi',
            'ref_pasien', 'master_person_index',
            'ref_poliklinik', 'ref_dokter', 'ref_jenis_asuransi',
        ] as $tbl) {
            $schema->dropIfExists($tbl);
        }

        $schema->create('ref_jenis_asuransi', function (Blueprint $table) {
            $table->increments('id');
            $table->string('jenis_asuransi', 100);
            $table->string('type', 20)->default('UMUM');
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('master_person_index', function (Blueprint $table) {
            $table->increments('id');
            $table->string('no_ktp', 30)->nullable();
            $table->string('nama', 100);
            $table->string('gender', 1)->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('tempat_lahir', 50)->nullable();
            $table->string('alamat', 255)->nullable();
            $table->string('status', 1)->default('1');
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
        });

        $schema->create('ref_pasien', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_mpi');
            $table->string('no_rm', 50);
            $table->unsignedInteger('id_jenis_asuransi')->nullable();
            $table->string('status', 1)->default('1');
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
        });

        $schema->create('ref_poliklinik', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama_poliklinik', 100);
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('ref_dokter', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_mpi')->nullable();
            $table->string('nama_lengkap', 150)->nullable();
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('trx_admisi', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_pasien');
            $table->unsignedInteger('id_jenis_asuransi')->default(2);
            $table->dateTime('waktu_admisi');
            $table->string('status', 10)->default('1');
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
        });

        $schema->create('trx_visit', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_admisi');
            $table->unsignedInteger('id_poliklinik');
            $table->unsignedInteger('id_dokter');
            $table->string('status_dilayani', 20)->default('Tidak');
            $table->string('cek_farmasi', 10)->default('Tidak');
            $table->string('cek_billing', 10)->default('Tidak');
            $table->string('cek_lunas_visit', 30)->default('belum bayar');
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
        });

        $schema->create('ref_tarif_tindakan', function (Blueprint $table) {
            $table->increments('id');
            $table->string('kode', 50)->nullable();
            $table->string('nama', 255);
            $table->integer('harga')->nullable();
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('trx_tindakan', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_visit');
            $table->unsignedInteger('id_tindakan');
            $table->text('nama_tindakan')->nullable();
            $table->integer('qty')->default(1);
            $table->integer('diskon')->default(0);
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
        });

        $schema->create('trx_resep', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_visit');
            $table->string('status_resep', 50)->default('order');
            $table->string('penyerahan_obat', 20)->default('Belum');
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
        });

        $schema->create('trx_resep_detail', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_resep');
            $table->string('nama_obat', 255);
            $table->integer('jml_item')->default(1);
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('trx_lab', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_visit');
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
        });

        $schema->create('trx_lab_detail_pemeriksaan', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_trx_lab');
            $table->unsignedInteger('id_item_lab')->default(0);
            $table->string('nama_grup_lab', 255)->nullable();
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('trx_rad', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_visit');
            $table->string('status', 10)->default('1');
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
        });

        $schema->create('trx_rad_detail_pemeriksaan', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_trx_rad');
            $table->unsignedInteger('id_item_rad')->default(0);
            $table->text('keterangan')->nullable();
            $table->string('status', 10)->default('1');
            $table->dateTime('deleted_at')->nullable();
        });

        $schema->create('trx_billing', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_admisi');
            $table->unsignedInteger('id_visit');
            $table->unsignedInteger('id_user')->nullable();
            $table->string('no_invoice', 255)->nullable();
            $table->date('tgl_transaksi')->nullable();
            $table->string('cara_bayar', 50)->nullable();
            $table->text('catatan')->nullable();
            $table->integer('kembali')->default(0);
            $table->integer('bayar')->default(0);
            $table->string('status_lunas', 50)->default('belum bayar');
            $table->integer('total_bayar')->default(0);
            $table->integer('diskon')->default(0);
            $table->string('status', 5)->default('1');
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
        });

        $schema->create('trx_billing_detail', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_billing');
            $table->unsignedInteger('id_visit');
            $table->string('jenis', 20)->default('TDK');
            $table->unsignedInteger('ref_id')->nullable();
            $table->unsignedInteger('ref_id_trx_tindakan')->nullable();
            $table->unsignedInteger('ref_id_trx_lab')->nullable();
            $table->unsignedInteger('id_trx_resep')->nullable();
            $table->string('kode', 100)->nullable();
            $table->string('nama', 255);
            $table->float('harga')->nullable();
            $table->integer('jumlah_item')->default(1);
            $table->float('total')->nullable();
            $table->integer('diskon')->default(0);
            $table->integer('sub_total')->nullable();
            $table->string('status_lunas', 50)->default('belum bayar');
            $table->integer('status')->default(1);
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    protected function seedLegacyBillingData(): void
    {
        $db = DB::connection('legacy');

        $db->table('ref_jenis_asuransi')->insert([
            ['id' => 1, 'jenis_asuransi' => 'BPJS Kesehatan', 'type' => 'BPJS', 'status' => 1, 'deleted_at' => null],
            ['id' => 2, 'jenis_asuransi' => 'UMUM', 'type' => 'UMUM', 'status' => 1, 'deleted_at' => null],
        ]);

        $mpi1 = $db->table('master_person_index')->insertGetId([
            'nama' => 'SITI RAHAYU',
            'no_ktp' => '3471015508900001',
            'gender' => 'P',
            'tgl_lahir' => '1990-08-15',
            'tempat_lahir' => 'Yogyakarta',
            'alamat' => 'Jl. Kaliurang KM 5',
            'deleted_at' => null,
        ]);

        $mpi2 = $db->table('master_person_index')->insertGetId([
            'nama' => 'BUDI SANTOSO',
            'no_ktp' => '3471011001800002',
            'gender' => 'L',
            'tgl_lahir' => '1980-01-10',
            'tempat_lahir' => 'Sleman',
            'alamat' => 'Jl. Magelang No. 12',
            'deleted_at' => null,
        ]);

        $pat1 = $db->table('ref_pasien')->insertGetId([
            'id_mpi' => $mpi1,
            'no_rm' => 'RM-100001',
            'id_jenis_asuransi' => 1,
            'status' => '1',
            'deleted_at' => null,
        ]);

        $pat2 = $db->table('ref_pasien')->insertGetId([
            'id_mpi' => $mpi2,
            'no_rm' => 'RM-100002',
            'id_jenis_asuransi' => 2,
            'status' => '1',
            'deleted_at' => null,
        ]);

        $db->table('ref_poliklinik')->insert([
            ['id' => 1, 'nama_poliklinik' => 'Poli Penyakit Dalam', 'status' => 1, 'deleted_at' => null],
            ['id' => 2, 'nama_poliklinik' => 'Poli Umum', 'status' => 1, 'deleted_at' => null],
        ]);

        $db->table('ref_dokter')->insert([
            ['id' => 1, 'nama_lengkap' => 'dr. Ahmad, Sp.PD', 'status' => 1, 'deleted_at' => null],
        ]);

        $db->table('ref_tarif_tindakan')->insert([
            ['id' => 1, 'kode' => 'TND-01', 'nama' => 'Konsultasi Dokter', 'harga' => 100000, 'status' => 1, 'deleted_at' => null],
            ['id' => 2, 'kode' => 'TND-02', 'nama' => 'EKG 12-Lead', 'harga' => 80000, 'status' => 1, 'deleted_at' => null],
        ]);

        $adm1 = $db->table('trx_admisi')->insertGetId([
            'id_pasien' => $pat1,
            'id_jenis_asuransi' => 1,
            'waktu_admisi' => '2026-11-26 09:00:00',
            'status' => '1',
        ]);

        $adm2 = $db->table('trx_admisi')->insertGetId([
            'id_pasien' => $pat2,
            'id_jenis_asuransi' => 2,
            'waktu_admisi' => '2026-11-26 10:00:00',
            'status' => '1',
        ]);

        $visit1 = $db->table('trx_visit')->insertGetId([
            'id_admisi' => $adm1,
            'id_poliklinik' => 1,
            'id_dokter' => 1,
            'status_dilayani' => 'Ya',
            'cek_farmasi' => 'Tidak',
            'cek_billing' => 'Tidak',
            'cek_lunas_visit' => 'belum bayar',
            'status' => 1,
        ]);

        $visit2 = $db->table('trx_visit')->insertGetId([
            'id_admisi' => $adm2,
            'id_poliklinik' => 2,
            'id_dokter' => 1,
            'status_dilayani' => 'Ya',
            'cek_farmasi' => 'Ya',
            'cek_billing' => 'Ya',
            'cek_lunas_visit' => 'sudah bayar',
            'status' => 1,
        ]);

        // Visit 1 has 2 procedures (one materialized in trx_billing_detail -> must NOT double-count, one new trx_tindakan),
        // one soft-deleted procedure (must NOT count), plus unpriced billing_detail & clinical orders.
        $proc1 = $db->table('trx_tindakan')->insertGetId([
            'id_visit' => $visit1,
            'id_tindakan' => 1,
            'qty' => 1,
            'diskon' => 0,
            'status' => 1,
        ]);

        $db->table('trx_tindakan')->insertGetId([
            'id_visit' => $visit1,
            'id_tindakan' => 2,
            'qty' => 2,
            'diskon' => 10000,
            'status' => 1,
        ]);

        // Soft-deleted procedure must be ignored
        $db->table('trx_tindakan')->insert([
            'id_visit' => $visit1,
            'id_tindakan' => 2,
            'qty' => 1,
            'diskon' => 0,
            'status' => 0,
            'deleted_at' => now(),
        ]);

        $bill1 = $db->table('trx_billing')->insertGetId([
            'id_admisi' => $adm1,
            'id_visit' => $visit1,
            'no_invoice' => 'INV-001',
            'tgl_transaksi' => '2026-11-26',
            'status_lunas' => 'belum bayar',
            'total_bayar' => 100000,
            'bayar' => 0,
            'diskon' => 0,
            'status' => '1',
        ]);

        $db->table('trx_billing_detail')->insert([
            [
                'id_billing' => $bill1,
                'id_visit' => $visit1,
                'jenis' => 'TDK',
                'ref_id_trx_tindakan' => $proc1,
                'kode' => 'TND-01',
                'nama' => 'Konsultasi Dokter',
                'harga' => 100000,
                'jumlah_item' => 1,
                'diskon' => 0,
                'total' => 100000,
                'sub_total' => 0,
                'status_lunas' => 'belum bayar',
                'status' => 1,
            ],
            [
                'id_billing' => $bill1,
                'id_visit' => $visit1,
                'jenis' => 'ADM',
                'ref_id_trx_tindakan' => null,
                'kode' => 'ADM-01',
                'nama' => 'Biaya Lab Tanpa Tarif Tercatat',
                'harga' => null,
                'jumlah_item' => 1,
                'diskon' => 0,
                'total' => null,
                'sub_total' => null,
                'status_lunas' => 'belum bayar',
                'status' => 1,
            ],
        ]);

        // Unpriced prescription & lab on visit 1
        $rx1 = $db->table('trx_resep')->insertGetId([
            'id_visit' => $visit1,
            'status' => 1,
        ]);
        $db->table('trx_resep_detail')->insert([
            'id_resep' => $rx1,
            'nama_obat' => 'Paracetamol 500 mg',
            'jml_item' => 10,
            'status' => 1,
        ]);

        // Visit 2 isolated data (must NOT leak to Visit 1)
        $db->table('trx_tindakan')->insert([
            'id_visit' => $visit2,
            'id_tindakan' => 2,
            'qty' => 1,
            'diskon' => 0,
            'status' => 1,
        ]);
    }

    public function test_billing_endpoints_require_authentication_and_reject_writes(): void
    {
        $this->getJson('/api/v1/billings')->assertStatus(401);
        $this->getJson('/api/v1/billings/1')->assertStatus(401);

        Sanctum::actingAs($this->user);

        // No payment/create endpoint exists in Fase 2B.19A
        $this->postJson('/api/v1/billings', [])->assertStatus(405);

        // Read-only legacy models block Eloquent writes
        $this->expectException(\RuntimeException::class);
        LegacyBilling::create([
            'id_admisi' => 1,
            'id_visit' => 1,
            'status' => '1',
        ]);
    }

    public function test_billing_detail_model_is_also_strictly_read_only(): void
    {
        $this->expectException(\RuntimeException::class);
        LegacyBillingDetail::create([
            'id_billing' => 1,
            'id_visit' => 1,
            'nama' => 'Test',
        ]);
    }

    public function test_billing_list_and_show_enforce_visit_isolation_anti_duplication_verified_vs_unpriced_and_read_only_db(): void
    {
        Sanctum::actingAs($this->user);

        // 1. Index filters by patient/invoice/polyclinic/guarantor/status
        $this->getJson('/api/v1/billings?q=SITI&polyclinic=' . urlencode('Poli Penyakit Dalam') . '&guarantor=' . urlencode('BPJS Kesehatan') . '&status=' . urlencode('Belum Bayar'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.visit_id', 1)
            ->assertJsonPath('data.0.admission_id', 1)
            ->assertJsonPath('data.0.patient_id', 1)
            ->assertJsonPath('data.0.guarantor', 'BPJS Kesehatan')
            ->assertJsonPath('meta.read_only_mode', true)
            ->assertJsonPath('meta.payment_enabled', false);

        // 2. Non-existent or orphan visit_id returns 404
        $this->getJson('/api/v1/billings/999999')->assertStatus(404);
        $orphanId = DB::connection('legacy')->table('trx_visit')->insertGetId([
            'id_admisi' => 0,
            'id_poliklinik' => 1,
            'id_dokter' => 1,
            'status' => 1,
        ]);
        $this->getJson("/api/v1/billings/{$orphanId}")->assertStatus(404);

        // 3. Detailed breakdown for Visit 1:
        // - BD #1 (proc #1): 100.000 (verified, NOT duplicated from trx_tindakan!)
        // - BD #2 (null harga): unpriced (NOT converted to 0)
        // - trx_tindakan #2: 80.000 * 2 - 10.000 = 150.000 (verified)
        // - trx_tindakan #3 (soft deleted): ignored
        // - trx_resep #1 (Paracetamol): unpriced (price: null)
        // Verified subtotal = 100.000 + 150.000 = 250.000, unpriced = 2
        $res = $this->getJson('/api/v1/billings/1')->assertOk();

        $res->assertJsonPath('data.visit_id', 1)
            ->assertJsonPath('data.admission_id', 1)
            ->assertJsonPath('data.patient_id', 1)
            ->assertJsonPath('data.guarantor', 'BPJS Kesehatan')
            ->assertJsonPath('data.status', 'Belum Bayar')
            ->assertJsonPath('data.raw_visit_cek_billing', 'Tidak')
            ->assertJsonPath('data.raw_visit_cek_lunas_visit', 'belum bayar')
            ->assertJsonPath('data.raw_billing_status_lunas', 'belum bayar')
            ->assertJsonPath('data.verified_subtotal', 250000)
            ->assertJsonPath('data.unpriced_item_count', 2)
            ->assertJsonPath('data.read_only_mode', true)
            ->assertJsonPath('data.payment_enabled', false)
            ->assertJsonCount(4, 'data.items');

        $items = collect($res->json('data.items'));
        $rxItem = $items->firstWhere('source', 'trx_resep');
        $this->assertNotNull($rxItem);
        $this->assertNull($rxItem['price']);
        $this->assertNull($rxItem['subtotal']);
        $this->assertSame('unpriced', $rxItem['pricing_status']);

        // Visit 2 procedure count stays isolated & Visit 2 status Lunas
        $this->getJson('/api/v1/billings/2')
            ->assertOk()
            ->assertJsonPath('data.visit_id', 2)
            ->assertJsonPath('data.status', 'Lunas')
            ->assertJsonPath('data.verified_subtotal', 80000)
            ->assertJsonCount(1, 'data.items');

        // Confirm read-only GET did NOT mutate trx_visit or trx_billing status
        $this->assertDatabaseHas('trx_visit', [
            'id' => 1,
            'cek_billing' => 'Tidak',
            'cek_lunas_visit' => 'belum bayar',
        ], 'legacy');
        $this->assertDatabaseHas('trx_billing', [
            'id_visit' => 1,
            'status_lunas' => 'belum bayar',
        ], 'legacy');
    }
}
