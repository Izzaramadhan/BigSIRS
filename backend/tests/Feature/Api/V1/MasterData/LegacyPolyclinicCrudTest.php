<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Legacy\LegacyPolyclinic;
use App\Models\Polyclinic;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyPolyclinicCrudTest extends TestCase
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

        // 1. Create legacy ref_poliklinik table
        DB::connection('legacy')->statement('
            CREATE TABLE ref_poliklinik (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kode VARCHAR(11),
                parent_id VARCHAR(11) DEFAULT "",
                id_gudang INTEGER NULL,
                nama VARCHAR(50) NOT NULL,
                jenis VARCHAR(20) DEFAULT "rawat-jalan",
                deskripsi VARCHAR(100) NULL,
                tampil VARCHAR(1) DEFAULT "1",
                status_gudang VARCHAR(1) DEFAULT "0",
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME,
                id_user INTEGER NULL,
                kode_bpjs VARCHAR(255) NULL,
                fhir_resource_id VARCHAR(255) NULL,
                kuota INTEGER DEFAULT 0,
                kuota_jkn INTEGER DEFAULT 0,
                ihs_id_location VARCHAR(255) NULL,
                ihs_id_organization VARCHAR(255) NULL,
                tampil_online VARCHAR(1) DEFAULT "1",
                tampil_antrian INTEGER DEFAULT 0,
                kode_antrian VARCHAR(1) NULL,
                fhir_id_location VARCHAR(255) NULL,
                suara_antrian VARCHAR(255) NULL
            )
        ');

        // 2. Create legacy tbl_user table for user identity mapping
        DB::connection('legacy')->statement('
            CREATE TABLE tbl_user (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_pegawai INTEGER NULL,
                nama VARCHAR(100),
                username VARCHAR(40) NOT NULL,
                password VARCHAR(50),
                login VARCHAR(1) DEFAULT "0",
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME,
                id_user INTEGER NULL,
                is_active INTEGER DEFAULT 1
            )
        ');

        // 3. Create legacy ref_gudang table
        DB::connection('legacy')->statement('
            CREATE TABLE ref_gudang (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kode VARCHAR(50),
                nama VARCHAR(50),
                deskripsi VARCHAR(50),
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME,
                id_user INTEGER
            )
        ');

        // 4. Create legacy transaction tables for referential protection
        DB::connection('legacy')->statement('
            CREATE TABLE trx_visit (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_poliklinik INTEGER
            )
        ');
        DB::connection('legacy')->statement('
            CREATE TABLE trx_jadwal_dokter (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_poliklinik INTEGER
            )
        ');
        DB::connection('legacy')->statement('
            CREATE TABLE map_tarif_tindakan_poliklinik (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_poliklinik INTEGER
            )
        ');

        // Seed warehouse
        DB::connection('legacy')->table('ref_gudang')->insert([
            'id' => 1,
            'kode' => 'GDF',
            'nama' => 'Gudang Utama Farmasi',
            'status' => '1',
            'created_at' => now()->toDateTimeString(),
        ]);

        // Seed legacy user corresponding to admin
        DB::connection('legacy')->table('tbl_user')->insert([
            'id' => 103,
            'id_pegawai' => 111,
            'nama' => 'Administrator SIMRS',
            'username' => 'admin',
            'status' => '1',
            'is_active' => 1,
            'created_at' => now()->toDateTimeString(),
        ]);

        // Create application user mapped to legacy admin
        $this->authUser = User::factory()->create([
            'username' => 'admin',
            'name' => 'Administrator SIMRS',
        ]);
    }

    protected function tearDown(): void
    {
        LegacyAccess::clearCache();
        parent::tearDown();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'OP0010',
            'name' => 'Poli Penyakit Dalam',
            'service_type' => 'rawat-jalan',
            'description' => 'Poli Penyakit Dalam Lantai 1',
            'is_visible' => true,
            'is_online_visible' => false,
            'quota' => 30,
            'jkn_quota' => 15,
            'warehouse_id' => 1,
            'bpjs_code' => 'INT',
        ], $overrides);
    }

    // ------------------------------------------------------------------ //
    // 1. Authorization & Identity Mapping
    // ------------------------------------------------------------------ //
    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/master-data/polyclinics')->assertUnauthorized();
        $this->postJson('/api/v1/master-data/polyclinics', $this->validPayload())->assertUnauthorized();
    }

    public function test_user_without_legacy_tbl_user_mapping_is_rejected_with_403(): void
    {
        $unmappedUser = User::factory()->create([
            'username' => 'unknown_doctor',
        ]);

        $response = $this->actingAs($unmappedUser)->postJson(
            '/api/v1/master-data/polyclinics',
            $this->validPayload()
        );

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
            ]);
    }

    // ------------------------------------------------------------------ //
    // 2. READ: Menampilkan data Poliklinik dari ref_poliklinik
    // ------------------------------------------------------------------ //
    public function test_can_list_polyclinics_from_legacy_ref_poliklinik(): void
    {
        DB::connection('legacy')->table('ref_poliklinik')->insert([
            [
                'kode' => 'OP0001',
                'nama' => 'Poliklinik Bedah',
                'jenis' => 'rawat-jalan',
                'id_gudang' => 1,
                'status' => '1',
                'deleted_at' => null,
                'created_at' => now()->toDateTimeString(),
            ],
            [
                'kode' => 'OP0002',
                'nama' => 'Poliklinik Gigi',
                'jenis' => 'rawat-jalan',
                'id_gudang' => 1,
                'status' => '1',
                'deleted_at' => '0000-00-00 00:00:00',
                'created_at' => now()->toDateTimeString(),
            ],
        ]);

        $response = $this->actingAs($this->authUser)->getJson('/api/v1/master-data/polyclinics');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.code', 'OP0001')
            ->assertJsonPath('data.0.name', 'Poliklinik Bedah')
            ->assertJsonPath('data.0.warehouse.name', 'Gudang Utama Farmasi')
            ->assertJsonPath('data.1.code', 'OP0002')
            ->assertJsonPath('data.1.name', 'Poliklinik Gigi');
    }

    public function test_can_search_polyclinics_in_legacy_database(): void
    {
        DB::connection('legacy')->table('ref_poliklinik')->insert([
            ['kode' => 'OP0001', 'nama' => 'Poli Anak', 'jenis' => 'rawat-jalan', 'status' => '1', 'created_at' => now()->toDateTimeString()],
            ['kode' => 'OP0002', 'nama' => 'Poli Jantung', 'jenis' => 'rawat-jalan', 'status' => '1', 'created_at' => now()->toDateTimeString()],
        ]);

        $response = $this->actingAs($this->authUser)->getJson('/api/v1/master-data/polyclinics?search=Jantung');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Poli Jantung');
    }

    public function test_can_show_single_polyclinic_from_legacy_ref_poliklinik(): void
    {
        $id = DB::connection('legacy')->table('ref_poliklinik')->insertGetId([
            'kode' => 'OP0005',
            'nama' => 'Poli Mata',
            'jenis' => 'rawat-jalan',
            'deskripsi' => 'Spesialis Mata',
            'id_gudang' => 1,
            'status' => '1',
            'created_at' => now()->toDateTimeString(),
        ]);

        $response = $this->actingAs($this->authUser)->getJson("/api/v1/master-data/polyclinics/{$id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.code', 'OP0005')
            ->assertJsonPath('data.name', 'Poli Mata')
            ->assertJsonPath('data.warehouse.name', 'Gudang Utama Farmasi');
    }

    // ------------------------------------------------------------------ //
    // 3. CREATE: Menambahkan Poliklinik langsung ke ref_poliklinik
    // ------------------------------------------------------------------ //
    public function test_can_create_polyclinic_directly_in_legacy_ref_poliklinik(): void
    {
        $payload = $this->validPayload([
            'code' => 'OP0020',
            'name' => 'Poli Ortopedi',
            'service_type' => 'rawat-jalan',
            'description' => 'Lantai 2 Bedah',
            'quota' => 25,
            'jkn_quota' => 10,
            'warehouse_id' => 1,
            'bpjs_code' => 'ORT',
        ]);

        $response = $this->actingAs($this->authUser)->postJson('/api/v1/master-data/polyclinics', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.code', 'OP0020')
            ->assertJsonPath('data.name', 'Poli Ortopedi')
            ->assertJsonPath('data.service_type', 'rawat-jalan')
            ->assertJsonPath('data.quota', 25)
            ->assertJsonPath('data.jkn_quota', 10)
            ->assertJsonPath('data.warehouse.name', 'Gudang Utama Farmasi');

        $createdId = $response->json('data.id');

        // BUKTI 1: Data masuk langsung ke ref_poliklinik di database legacy
        $legacyRecord = DB::connection('legacy')->table('ref_poliklinik')->where('id', $createdId)->first();
        $this->assertNotNull($legacyRecord);
        $this->assertEquals('OP0020', $legacyRecord->kode);
        $this->assertEquals('Poli Ortopedi', $legacyRecord->nama);
        $this->assertEquals('rawat-jalan', $legacyRecord->jenis);
        $this->assertEquals(103, $legacyRecord->id_user); // User ID dipetakan dari tbl_user!
        $this->assertEquals('1', $legacyRecord->status);
        $this->assertNotNull($legacyRecord->created_at);

        // BUKTI 2: Tidak ada data yang tersimpan ke tabel polyclinics di database aplikasi
        $this->assertDatabaseMissing('polyclinics', ['code' => 'OP0020']);
    }

    // ------------------------------------------------------------------ //
    // 4. UPDATE: Mengedit Poliklinik langsung di ref_poliklinik
    // ------------------------------------------------------------------ //
    public function test_can_update_polyclinic_in_legacy_ref_poliklinik(): void
    {
        $id = DB::connection('legacy')->table('ref_poliklinik')->insertGetId([
            'kode' => 'OP0030',
            'nama' => 'Poli Saraf Lama',
            'jenis' => 'rawat-jalan',
            'status' => '1',
            'created_at' => now()->toDateTimeString(),
        ]);

        $updatePayload = $this->validPayload([
            'code' => 'OP0030',
            'name' => 'Poli Saraf & Neurologi',
            'service_type' => 'rawat-jalan',
            'description' => 'Klinik saraf terpadu',
            'quota' => 40,
        ]);

        $response = $this->actingAs($this->authUser)->putJson("/api/v1/master-data/polyclinics/{$id}", $updatePayload);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Poli Saraf & Neurologi')
            ->assertJsonPath('data.quota', 40);

        // Verifikasi langsung ke ref_poliklinik
        $updated = DB::connection('legacy')->table('ref_poliklinik')->where('id', $id)->first();
        $this->assertEquals('Poli Saraf & Neurologi', $updated->nama);
        $this->assertEquals(40, $updated->kuota);
        $this->assertEquals(103, $updated->id_user);
        $this->assertNotNull($updated->updated_at);

        // Pastikan muncul pada READ
        $readResponse = $this->actingAs($this->authUser)->getJson("/api/v1/master-data/polyclinics/{$id}");
        $readResponse->assertOk()
            ->assertJsonPath('data.name', 'Poli Saraf & Neurologi');
    }

    public function test_can_toggle_polyclinic_status_in_legacy_database(): void
    {
        $id = DB::connection('legacy')->table('ref_poliklinik')->insertGetId([
            'kode' => 'OP0040',
            'nama' => 'Poli THT',
            'jenis' => 'rawat-jalan',
            'status' => '1',
            'created_at' => now()->toDateTimeString(),
        ]);

        $response = $this->actingAs($this->authUser)->patchJson("/api/v1/master-data/polyclinics/{$id}/status", [
            'is_active' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_active', false);

        $record = DB::connection('legacy')->table('ref_poliklinik')->where('id', $id)->first();
        $this->assertEquals('0', $record->status);
    }

    // ------------------------------------------------------------------ //
    // 5. DELETE: Soft delete sesuai aturan legacy
    // ------------------------------------------------------------------ //
    public function test_can_soft_delete_polyclinic_in_legacy_ref_poliklinik(): void
    {
        $id = DB::connection('legacy')->table('ref_poliklinik')->insertGetId([
            'kode' => 'OP0050',
            'nama' => 'Poli Akupunktur',
            'jenis' => 'rawat-jalan',
            'status' => '1',
            'created_at' => now()->toDateTimeString(),
        ]);

        $response = $this->actingAs($this->authUser)->deleteJson("/api/v1/master-data/polyclinics/{$id}");
        $response->assertNoContent();

        // Verifikasi bahwa data di database legacy di-soft delete, bukan di-hard delete
        $record = DB::connection('legacy')->table('ref_poliklinik')->where('id', $id)->first();
        $this->assertNotNull($record);
        $this->assertNotNull($record->deleted_at);
        $this->assertEquals('0', $record->status);

        // Verifikasi tidak muncul lagi saat list atau show
        $listResponse = $this->actingAs($this->authUser)->getJson('/api/v1/master-data/polyclinics');
        $this->assertEmpty(collect($listResponse->json('data'))->where('id', $id));

        $showResponse = $this->actingAs($this->authUser)->getJson("/api/v1/master-data/polyclinics/{$id}");
        $showResponse->assertNotFound();
    }

    public function test_cannot_delete_polyclinic_with_active_children(): void
    {
        $parentId = DB::connection('legacy')->table('ref_poliklinik')->insertGetId([
            'kode' => 'OP0060',
            'nama' => 'Poli Induk Bedah',
            'jenis' => 'rawat-jalan',
            'status' => '1',
            'created_at' => now()->toDateTimeString(),
        ]);

        DB::connection('legacy')->table('ref_poliklinik')->insert([
            'kode' => 'OP0061',
            'parent_id' => 'OP0060', // Sub-poliklinik mengacu ke kode parent
            'nama' => 'Sub Poli Bedah Plastik',
            'jenis' => 'rawat-jalan',
            'status' => '1',
            'created_at' => now()->toDateTimeString(),
        ]);

        $response = $this->actingAs($this->authUser)->deleteJson("/api/v1/master-data/polyclinics/{$parentId}");

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Tidak dapat menghapus poliklinik karena memiliki sub-poliklinik aktif.',
            ]);

        // Pastikan tidak terhapus
        $record = DB::connection('legacy')->table('ref_poliklinik')->where('id', $parentId)->first();
        $this->assertNull($record->deleted_at);
    }

    public function test_cannot_delete_polyclinic_referenced_in_transactions(): void
    {
        $id = DB::connection('legacy')->table('ref_poliklinik')->insertGetId([
            'kode' => 'OP0070',
            'nama' => 'Poli Penyakit Dalam Historis',
            'jenis' => 'rawat-jalan',
            'status' => '1',
            'created_at' => now()->toDateTimeString(),
        ]);

        // Tambahkan riwayat transaksi kunjungan pasien
        DB::connection('legacy')->table('trx_visit')->insert([
            'id_poliklinik' => $id,
        ]);

        $response = $this->actingAs($this->authUser)->deleteJson("/api/v1/master-data/polyclinics/{$id}");

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Poliklinik tidak dapat dihapus karena sudah memiliki riwayat kunjungan, jadwal dokter, atau tarif terkait. Gunakan fitur nonaktifkan status jika poliklinik tidak lagi digunakan.',
            ]);

        $record = DB::connection('legacy')->table('ref_poliklinik')->where('id', $id)->first();
        $this->assertNull($record->deleted_at);
    }

    // ------------------------------------------------------------------ //
    // 6. Validasi & Penanganan Data Duplikat
    // ------------------------------------------------------------------ //
    public function test_validates_required_fields(): void
    {
        $response = $this->actingAs($this->authUser)->postJson('/api/v1/master-data/polyclinics', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code', 'name', 'service_type']);
    }

    public function test_prevents_duplicate_code_in_legacy_database(): void
    {
        DB::connection('legacy')->table('ref_poliklinik')->insert([
            'kode' => 'OP0080',
            'nama' => 'Poli Kulit',
            'jenis' => 'rawat-jalan',
            'status' => '1',
            'created_at' => now()->toDateTimeString(),
        ]);

        $response = $this->actingAs($this->authUser)->postJson(
            '/api/v1/master-data/polyclinics',
            $this->validPayload(['code' => 'OP0080', 'name' => 'Poli Kulit Baru'])
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    public function test_allows_reusing_code_of_soft_deleted_polyclinic(): void
    {
        DB::connection('legacy')->table('ref_poliklinik')->insert([
            'kode' => 'OP0090',
            'nama' => 'Poli Lama Dihapus',
            'jenis' => 'rawat-jalan',
            'status' => '0',
            'deleted_at' => now()->toDateTimeString(),
            'created_at' => now()->toDateTimeString(),
        ]);

        $response = $this->actingAs($this->authUser)->postJson(
            '/api/v1/master-data/polyclinics',
            $this->validPayload(['code' => 'OP0090', 'name' => 'Poli Baru Aktif'])
        );

        $response->assertCreated()
            ->assertJsonPath('data.code', 'OP0090');
    }

    public function test_other_legacy_models_remain_strictly_read_only(): void
    {
        $spec = new \App\Models\Legacy\LegacySpecialization();
        $spec->nama_spesialisasi = 'Test Spesialis Baru';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Operasi tulis ke database legacy dinonaktifkan untuk menjaga integritas data sumber.');

        $spec->save();
    }
}
