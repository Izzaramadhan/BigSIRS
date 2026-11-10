<?php

namespace Tests\Feature\Api\V1\Logistics;

use App\Models\Legacy\LegacyInvoice;
use App\Models\Legacy\LegacyStockLog;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacyLogisticsCrudTest extends TestCase
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

        // 1. Create lgs_faktur & lgs_faktur_detail
        DB::connection('legacy')->statement('
            CREATE TABLE lgs_faktur (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nomor VARCHAR(50) NOT NULL,
                id_supplier INTEGER NOT NULL,
                tanggal DATE NOT NULL,
                jumlah DOUBLE DEFAULT 0,
                diskon DOUBLE DEFAULT 0,
                ppn DOUBLE DEFAULT 0,
                total DOUBLE DEFAULT 0,
                lunas SMALLINT DEFAULT 0,
                jatuh_tempo DATE NULL,
                keterangan TEXT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                id_user INTEGER NOT NULL,
                status TINYINT DEFAULT 1,
                tanggal_lunas DATE NULL,
                jumlah_bayar DOUBLE NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE lgs_faktur_detail (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_lgs_faktur INTEGER NOT NULL,
                id_obat INTEGER NOT NULL,
                qty INTEGER NOT NULL,
                harga_beli DOUBLE NOT NULL,
                diskon TEXT NULL,
                ed DATE NOT NULL,
                batch VARCHAR(50) NOT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                id_user INTEGER NOT NULL,
                status TINYINT DEFAULT 1,
                qty_send INTEGER NULL,
                repackaged_status SMALLINT DEFAULT 0
            )
        ');

        // 2. Create lgs_log_stok
        DB::connection('legacy')->statement('
            CREATE TABLE lgs_log_stok (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_lgs_faktur_detail INTEGER NOT NULL,
                id_gudang INTEGER NOT NULL,
                qty INTEGER NOT NULL,
                tanggal_transaksi DATETIME NOT NULL,
                harga_jual DOUBLE NOT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                id_user INTEGER NOT NULL,
                status TINYINT DEFAULT 1
            )
        ');

        // 3. Create lgs_pengeluaran & lgs_pengeluaran_detail
        DB::connection('legacy')->statement('
            CREATE TABLE lgs_pengeluaran (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tanggal DATE NOT NULL,
                id_pegawai INTEGER NOT NULL,
                keperluan VARCHAR(255) NULL,
                status_lunas VARCHAR(20) NULL,
                kode_billing VARCHAR(50) NULL,
                cara_bayar VARCHAR(20) NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                status INTEGER DEFAULT 1,
                id_user INTEGER NOT NULL,
                pembeli TEXT NULL,
                flag VARCHAR(50) NULL,
                tanggal_bayar DATE NULL,
                bankjateng_transaction_id VARCHAR(20) NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE lgs_pengeluaran_detail (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_lgs_pengeluaran INTEGER NOT NULL,
                id_lgs_log_stok INTEGER NOT NULL,
                status_lunas VARCHAR(20) NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                id_user INTEGER NOT NULL,
                status INTEGER DEFAULT 1,
                jumlah FLOAT NULL,
                bankjateng_channel VARCHAR(20) NULL,
                bankjateng_device VARCHAR(20) NULL,
                bankjateng_noreff VARCHAR(20) NULL
            )
        ');

        // 4. Create lgs_mutasi & lgs_mutasi_detail
        DB::connection('legacy')->statement('
            CREATE TABLE lgs_mutasi (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_gudang_asal INTEGER NOT NULL,
                id_gudang_tujuan INTEGER NOT NULL,
                note VARCHAR(255) NOT NULL,
                tanggal DATE NOT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                id_user INTEGER NOT NULL,
                status TINYINT DEFAULT 1
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE lgs_mutasi_detail (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_mutasi INTEGER NOT NULL,
                id_lgs_faktur_detail INTEGER NOT NULL,
                qty INTEGER NOT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                id_user INTEGER NOT NULL,
                status TINYINT DEFAULT 1
            )
        ');

        // 5. Create lgs_stok_opname & lgs_stok_opname_detail
        DB::connection('legacy')->statement('
            CREATE TABLE lgs_stok_opname (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tanggal DATE NULL,
                id_gudang INTEGER NULL,
                id_user INTEGER NULL,
                status INTEGER DEFAULT 1,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                tanggal_pindah_stok DATE NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE lgs_stok_opname_detail (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_lgs_stok_opname INTEGER NULL,
                id_lgs_log_stok INTEGER NULL,
                stok_awal FLOAT NULL,
                pemasukan FLOAT NULL,
                pengeluaran FLOAT NULL,
                stok_akhir FLOAT NULL,
                selisih FLOAT NULL,
                fisik FLOAT NULL,
                id_user INTEGER NULL,
                status INTEGER DEFAULT 1,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        // 6. Master references
        DB::connection('legacy')->statement('
            CREATE TABLE ref_supplier (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(150) NOT NULL,
                alamat TEXT NULL,
                telepon VARCHAR(50) NULL,
                status TINYINT DEFAULT 1,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_gudang (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(150) NOT NULL,
                kode VARCHAR(50) NULL,
                status TINYINT DEFAULT 1,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_satuan_obat (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(50) NOT NULL,
                status TINYINT DEFAULT 1,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_obat (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kode VARCHAR(50) NOT NULL,
                nama VARCHAR(150) NOT NULL,
                id_satuan INTEGER NULL,
                harga_beli DOUBLE DEFAULT 0,
                harga_jual DOUBLE DEFAULT 0,
                status TINYINT DEFAULT 1,
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

        DB::connection('legacy')->statement('
            CREATE TABLE master_person_index (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(150) NOT NULL,
                no_ktp VARCHAR(50) NULL,
                no_hp VARCHAR(50) NULL,
                status TINYINT DEFAULT 1,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_pegawai (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_mpi INTEGER NULL,
                nip VARCHAR(50) NULL,
                nama VARCHAR(150) NULL,
                status TINYINT DEFAULT 1,
                deleted_at DATETIME NULL
            )
        ');

        // Seed master reference rows
        DB::connection('legacy')->table('master_person_index')->insert([
            'id' => 1,
            'nama' => 'Petugas Logistik',
            'no_ktp' => '3301010101010001',
            'no_hp' => '08123456789',
            'status' => 1,
        ]);

        DB::connection('legacy')->table('ref_pegawai')->insert([
            'id' => 1,
            'id_mpi' => 1,
            'nip' => 'PEG-001',
            'nama' => 'Petugas Logistik',
            'status' => 1,
        ]);

        DB::connection('legacy')->table('ref_supplier')->insert([
            'id' => 1,
            'nama' => 'PT Kimia Farma Trading',
            'alamat' => 'Jakarta Barat',
            'telepon' => '021-1234567',
            'status' => 1,
        ]);

        DB::connection('legacy')->table('ref_gudang')->insert([
            ['id' => 1, 'nama' => 'Gudang Utama', 'kode' => 'GU', 'status' => 1],
            ['id' => 2, 'nama' => 'Apotek Rawat Jalan', 'kode' => 'ARJ', 'status' => 1],
        ]);

        DB::connection('legacy')->table('ref_satuan_obat')->insert([
            'id' => 1,
            'nama' => 'Tablet',
            'status' => 1,
        ]);

        DB::connection('legacy')->table('ref_obat')->insert([
            ['id' => 1, 'kode' => 'OBT001', 'nama' => 'Paracetamol 500mg', 'id_satuan' => 1, 'harga_beli' => 500, 'harga_jual' => 700, 'status' => 1],
            ['id' => 2, 'kode' => 'OBT002', 'nama' => 'Amoxicillin 500mg', 'id_satuan' => 1, 'harga_beli' => 1000, 'harga_jual' => 1400, 'status' => 1],
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
     * Test 1: Direct CRUD for Invoices & Stock Log initialization.
     */
    public function test_can_create_invoice_and_initializes_stock_log(): void
    {
        $payload = [
            'invoice_number' => 'INV-LEGACY-001',
            'invoice_date' => '2026-11-09',
            'due_date' => '2026-12-09',
            'supplier_id' => 1,
            'warehouse_id' => 1,
            'status' => 'Belum Lunas',
            'items' => [
                [
                    'medicine_id' => 1,
                    'quantity' => 100,
                    'price' => 500,
                    'margin' => 0.28,
                    'batch_number' => 'BATCH-A1',
                    'expired_date' => '2028-12-31',
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/logistics/invoices', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.invoice_number', 'INV-LEGACY-001')
            ->assertJsonPath('data.status', 'Belum Lunas');

        // Verify written to legacy tables
        $this->assertDatabaseHas('lgs_faktur', [
            'nomor' => 'INV-LEGACY-001',
            'id_supplier' => 1,
            'total' => 50000,
            'lunas' => 0,
        ], 'legacy');

        $this->assertDatabaseHas('lgs_faktur_detail', [
            'id_obat' => 1,
            'qty' => 100,
            'batch' => 'BATCH-A1',
        ], 'legacy');

        $this->assertDatabaseHas('lgs_log_stok', [
            'id_gudang' => 1,
            'qty' => 100,
        ], 'legacy');

        // Test listing & summary
        $indexResponse = $this->getJson('/api/v1/logistics/invoices');
        $indexResponse->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $summaryResponse = $this->getJson('/api/v1/logistics/invoices/summary');
        $summaryResponse->assertStatus(200)
            ->assertJsonPath('totalCount', 1)
            ->assertJsonPath('unpaidCount', 1);
    }

    /**
     * Test 2: Stock Opname direct update on lgs_log_stok.
     */
    public function test_can_read_and_update_stock_opname_directly(): void
    {
        // Seed an invoice detail and stock log
        DB::connection('legacy')->table('lgs_faktur_detail')->insert([
            'id' => 10,
            'id_lgs_faktur' => 1,
            'id_obat' => 1,
            'qty' => 50,
            'harga_beli' => 500,
            'ed' => '2028-12-31',
            'batch' => 'BATCH-SO',
            'id_user' => 101,
            'status' => 1,
        ]);

        DB::connection('legacy')->table('lgs_log_stok')->insert([
            'id' => 20,
            'id_lgs_faktur_detail' => 10,
            'id_gudang' => 1,
            'qty' => 50,
            'tanggal_transaksi' => '2026-11-01 10:00:00',
            'harga_jual' => 700,
            'id_user' => 101,
            'status' => 1,
        ]);

        $listResponse = $this->getJson('/api/v1/logistics/stock-opnames');
        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $updateResponse = $this->putJson('/api/v1/logistics/stock-opnames/20', [
            'stock' => 45,
            'selling_price' => 750,
            'expiration_date' => '2029-01-01',
        ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.stock', 45)
            ->assertJsonPath('data.selling_price', 750)
            ->assertJsonPath('message', 'Stok opname berhasil diperbarui.');

        $this->assertDatabaseHas('lgs_log_stok', [
            'id' => 20,
            'qty' => 45,
            'harga_jual' => 750,
        ], 'legacy');

        $this->assertDatabaseHas('lgs_stok_opname', [
            'id_gudang' => 1,
            'status' => 3,
        ], 'legacy');

        $this->assertDatabaseHas('lgs_stok_opname_detail', [
            'id_lgs_log_stok' => 20,
            'stok_awal' => 50,
            'stok_akhir' => 45,
            'selisih' => -5,
        ], 'legacy');
    }

    /**
     * Test 3: Goods Issue direct operation on lgs_pengeluaran and stock deduction.
     */
    public function test_can_create_and_cancel_goods_issue_directly(): void
    {
        DB::connection('legacy')->table('lgs_faktur_detail')->insert([
            'id' => 11,
            'id_lgs_faktur' => 1,
            'id_obat' => 2,
            'qty' => 100,
            'harga_beli' => 1000,
            'ed' => '2028-12-31',
            'batch' => 'BATCH-GI',
            'id_user' => 101,
            'status' => 1,
        ]);

        DB::connection('legacy')->table('lgs_log_stok')->insert([
            'id' => 21,
            'id_lgs_faktur_detail' => 11,
            'id_gudang' => 1,
            'qty' => 100,
            'tanggal_transaksi' => '2026-11-01 10:00:00',
            'harga_jual' => 1400,
            'id_user' => 101,
            'status' => 1,
        ]);

        // Check available stocks endpoint
        $stocksRes = $this->getJson('/api/v1/logistics/warehouses/1/available-stocks');
        $stocksRes->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Create finalized Goods Issue
        $payload = [
            'issue_date' => '2026-11-09',
            'warehouse_id' => 1,
            'purpose' => 'Barang Rusak',
            'notes' => 'Pecah saat pemindahan',
            'officer_id' => 1,
            'items' => [
                [
                    'medicine_id' => 2,
                    'warehouse_stock_id' => 21,
                    'quantity' => 10,
                ],
            ],
        ];

        $createRes = $this->postJson('/api/v1/logistics/goods-issues', $payload);
        $createRes->assertStatus(201)
            ->assertJsonPath('data.status', 'Selesai');

        $issueId = $createRes->json('data.id');

        // Stock in lgs_log_stok should be deducted from 100 to 90
        $this->assertDatabaseHas('lgs_log_stok', [
            'id' => 21,
            'qty' => 90,
        ], 'legacy');

        $this->assertDatabaseHas('lgs_pengeluaran', [
            'id' => $issueId,
            'flag' => 'pengeluaran',
            'status' => 1,
        ], 'legacy');

        // Cancel Goods Issue -> Stock restored to 100
        $cancelRes = $this->postJson("/api/v1/logistics/goods-issues/{$issueId}/cancel", [
            'reason' => 'Salah input',
        ]);
        $cancelRes->assertStatus(200)
            ->assertJsonPath('data.status', 'Dibatalkan');

        $this->assertDatabaseHas('lgs_log_stok', [
            'id' => 21,
            'qty' => 100,
        ], 'legacy');
    }

    /**
     * Test 4: POS OTC Sale direct operation on lgs_pengeluaran with flag apotek_bebas.
     */
    public function test_can_create_and_cancel_pos_sale_directly(): void
    {
        DB::connection('legacy')->table('lgs_faktur_detail')->insert([
            'id' => 12,
            'id_lgs_faktur' => 1,
            'id_obat' => 1,
            'qty' => 50,
            'harga_beli' => 500,
            'ed' => '2028-12-31',
            'batch' => 'BATCH-POS',
            'id_user' => 101,
            'status' => 1,
        ]);

        DB::connection('legacy')->table('lgs_log_stok')->insert([
            'id' => 22,
            'id_lgs_faktur_detail' => 12,
            'id_gudang' => 1,
            'qty' => 50,
            'tanggal_transaksi' => '2026-11-01 10:00:00',
            'harga_jual' => 700,
            'id_user' => 101,
            'status' => 1,
        ]);

        $payload = [
            'transaction_date' => '2026-11-09',
            'customer_name' => 'Budi Sudarsono',
            'payment_method' => 'Tunai',
            'items' => [
                [
                    'medicine_id' => 1,
                    'warehouse_stock_id' => 22,
                    'quantity' => 5,
                ],
            ],
        ];

        $createRes = $this->postJson('/api/v1/logistics/pos-sales', $payload);
        $createRes->assertStatus(201)
            ->assertJsonPath('data.customer_name', 'Budi Sudarsono');

        $saleId = $createRes->json('data.id');

        // Stock in lgs_log_stok deducted from 50 to 45
        $this->assertDatabaseHas('lgs_log_stok', [
            'id' => 22,
            'qty' => 45,
        ], 'legacy');

        $this->assertDatabaseHas('lgs_pengeluaran', [
            'id' => $saleId,
            'flag' => 'apotek_bebas',
            'status' => 1,
        ], 'legacy');

        // Cancel sale
        $cancelRes = $this->postJson("/api/v1/logistics/pos-sales/{$saleId}/cancel", [
            'reason' => 'Retur pasien',
        ]);
        $cancelRes->assertStatus(200);

        // Stock restored to 50
        $this->assertDatabaseHas('lgs_log_stok', [
            'id' => 22,
            'qty' => 50,
        ], 'legacy');
    }

    /**
     * Test 5: Stock Mutation direct operation on lgs_mutasi and lgs_log_stok.
     */
    public function test_can_create_stock_mutation_directly(): void
    {
        DB::connection('legacy')->table('lgs_faktur_detail')->insert([
            'id' => 13,
            'id_lgs_faktur' => 1,
            'id_obat' => 1,
            'qty' => 80,
            'harga_beli' => 500,
            'ed' => '2028-12-31',
            'batch' => 'BATCH-MUT',
            'id_user' => 101,
            'status' => 1,
        ]);

        DB::connection('legacy')->table('lgs_log_stok')->insert([
            'id' => 23,
            'id_lgs_faktur_detail' => 13,
            'id_gudang' => 1, // Gudang Utama
            'qty' => 80,
            'tanggal_transaksi' => '2026-11-01 10:00:00',
            'harga_jual' => 700,
            'id_user' => 101,
            'status' => 1,
        ]);

        $payload = [
            'source_warehouse_id' => 1,
            'target_warehouse_id' => 2,
            'mutation_date' => '2026-11-09',
            'notes' => 'Mutasi ke apotek',
            'items' => [
                [
                    'medicine_id' => 1,
                    'quantity' => 30,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/logistics/mutations', $payload);
        $response->assertStatus(201);

        // Source warehouse stock reduced from 80 to 50
        $this->assertDatabaseHas('lgs_log_stok', [
            'id' => 23,
            'id_gudang' => 1,
            'qty' => 50,
        ], 'legacy');

        // Target warehouse stock created with 30
        $this->assertDatabaseHas('lgs_log_stok', [
            'id_gudang' => 2,
            'id_lgs_faktur_detail' => 13,
            'qty' => 30,
        ], 'legacy');

        $this->assertDatabaseHas('lgs_mutasi', [
            'id_gudang_asal' => 1,
            'id_gudang_tujuan' => 2,
            'note' => 'Mutasi ke apotek',
            'status' => 1,
        ], 'legacy');

        $this->assertDatabaseHas('lgs_mutasi_detail', [
            'id_lgs_faktur_detail' => 13,
            'qty' => 30,
        ], 'legacy');
    }
}
