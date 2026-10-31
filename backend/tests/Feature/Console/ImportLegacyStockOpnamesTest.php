<?php

namespace Tests\Feature\Console;

use App\Models\Logistics\StockOpname;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\MedicineUnit;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportLegacyStockOpnamesTest extends TestCase
{
    use RefreshDatabase;

    protected Warehouse $warehouse1;

    protected Warehouse $warehouse2;

    protected Medicine $medicine1;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['name' => 'Admin Test']);

        $this->warehouse1 = Warehouse::create([
            'legacy_id' => 1,
            'name' => 'Gudang Utama',
            'code' => 'GDF',
        ]);

        $this->warehouse2 = Warehouse::create([
            'legacy_id' => 2,
            'name' => 'Apotek Rawat Jalan',
            'code' => 'AP-RJ',
        ]);

        $unit = MedicineUnit::create(['name' => 'Tablet']);

        $this->medicine1 = Medicine::create([
            'legacy_id' => 101,
            'name' => 'Paracetamol 500mg',
            'code' => 'PCT-500',
            'medicine_unit_id' => $unit->id,
            'selling_price_per_unit' => 1000,
            'is_active' => true,
        ]);

        Config::set('database.connections.legacy', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        DB::connection('legacy')->statement('
            CREATE TABLE lgs_stok_opname (
                id INTEGER PRIMARY KEY,
                tanggal DATE,
                id_gudang INTEGER,
                id_user INTEGER,
                status INTEGER,
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME,
                tanggal_pindah_stok DATE
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE lgs_stok_opname_detail (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_lgs_stok_opname INTEGER,
                id_lgs_log_stok INTEGER,
                stok_awal REAL,
                pemasukan REAL,
                pengeluaran REAL,
                stok_akhir REAL,
                selisih REAL,
                fisik REAL,
                id_user INTEGER,
                status INTEGER,
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE lgs_log_stok (
                id INTEGER PRIMARY KEY,
                id_lgs_faktur_detail INTEGER,
                id_gudang INTEGER,
                qty INTEGER,
                tanggal_transaksi DATETIME,
                harga_jual REAL,
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME,
                id_user INTEGER,
                status INTEGER
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE lgs_faktur_detail (
                id INTEGER PRIMARY KEY,
                id_obat INTEGER,
                batch VARCHAR(50),
                ed DATE
            )
        ');
    }

    private function seedLegacyData(): void
    {
        DB::connection('legacy')->table('lgs_faktur_detail')->insert([
            'id' => 1,
            'id_obat' => 101,
            'batch' => 'BATCH-TEST',
            'ed' => '2028-12-31',
        ]);

        DB::connection('legacy')->table('lgs_log_stok')->insert([
            'id' => 10,
            'id_lgs_faktur_detail' => 1,
            'id_gudang' => 1,
            'qty' => 50,
            'tanggal_transaksi' => '2026-10-01 00:00:00',
            'harga_jual' => 1200,
            'created_at' => '2026-10-01 00:00:00',
            'id_user' => 1,
            'status' => 1,
        ]);

        $statuses = [0, 1, 2, 3, 4, 0, 1, 2, 3, 3];
        for ($i = 1; $i <= 10; $i++) {
            DB::connection('legacy')->table('lgs_stok_opname')->insert([
                'id' => $i,
                'tanggal' => '2026-10-'.str_pad((string) (10 + $i), 2, '0', STR_PAD_LEFT),
                'id_gudang' => $i % 2 === 0 ? 2 : 1,
                'id_user' => 1,
                'status' => $statuses[$i - 1],
                'created_at' => '2026-10-10 08:00:00',
                'updated_at' => '2026-10-10 08:00:00',
            ]);

            DB::connection('legacy')->table('lgs_stok_opname_detail')->insert([
                'id' => $i,
                'id_lgs_stok_opname' => $i,
                'id_lgs_log_stok' => 10,
                'stok_awal' => 50,
                'pemasukan' => 0,
                'pengeluaran' => 0,
                'stok_akhir' => 50,
                'selisih' => $i === 2 ? -2 : 0,
                'fisik' => $i === 2 ? 48 : 50,
                'id_user' => 1,
                'status' => 1,
                'created_at' => '2026-10-10 08:00:00',
            ]);
        }
    }

    public function test_can_dry_run_import_legacy_stock_opnames(): void
    {
        $this->seedLegacyData();

        $this->artisan('legacy:import-stock-opnames', ['--dry-run' => true])
            ->expectsOutputToContain('Ditemukan 10 data stok opname pada database legacy.')
            ->expectsOutputToContain('Legacy ditemukan : 10')
            ->expectsOutputToContain('Total target      : 10')
            ->assertExitCode(0);

        // In dry run, no records created
        $this->assertEquals(0, StockOpname::count());
    }

    public function test_can_import_10_legacy_stock_opnames_with_idempotency(): void
    {
        $this->seedLegacyData();

        // First run: imports 10
        $this->artisan('legacy:import-stock-opnames')
            ->expectsOutputToContain('Dibuat            : 10')
            ->expectsOutputToContain('Total target      : 10')
            ->assertExitCode(0);

        $this->assertEquals(10, StockOpname::count());

        // Verify status mapping: 0 => draft, 1 => counting, 2 => review, 3 => completed, 4 => cancelled
        $this->assertEquals(StockOpname::STATUS_DRAFT, StockOpname::where('legacy_id', 1)->value('status'));
        $this->assertEquals(StockOpname::STATUS_COUNTING, StockOpname::where('legacy_id', 2)->value('status'));
        $this->assertEquals(StockOpname::STATUS_REVIEW, StockOpname::where('legacy_id', 3)->value('status'));
        $this->assertEquals(StockOpname::STATUS_COMPLETED, StockOpname::where('legacy_id', 4)->value('status'));
        $this->assertEquals(StockOpname::STATUS_CANCELLED, StockOpname::where('legacy_id', 5)->value('status'));

        // Second run: updates 10, creates 0 (idempotent, no duplicates)
        $this->artisan('legacy:import-stock-opnames')
            ->expectsOutputToContain('Dibuat            : 0')
            ->expectsOutputToContain('Diperbarui        : 10')
            ->expectsOutputToContain('Total target      : 10')
            ->assertExitCode(0);

        $this->assertEquals(10, StockOpname::count());
    }

    public function test_importer_reports_unmapped_warehouse_and_skips_safely(): void
    {
        DB::connection('legacy')->table('lgs_stok_opname')->insert([
            'id' => 999,
            'tanggal' => '2026-10-15',
            'id_gudang' => 99999, // non-existent warehouse
            'id_user' => 1,
            'status' => 0,
            'created_at' => '2026-10-15 08:00:00',
        ]);

        $this->artisan('legacy:import-stock-opnames')
            ->expectsOutputToContain('Gagal mapping gudang untuk legacy ID 999')
            ->expectsOutputToContain('Gagal             : 1')
            ->assertExitCode(0);

        $this->assertEquals(0, StockOpname::count());
    }
}

