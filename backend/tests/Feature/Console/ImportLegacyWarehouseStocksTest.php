<?php

namespace Tests\Feature\Console;

use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\MedicineUnit;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportLegacyWarehouseStocksTest extends TestCase
{
    use RefreshDatabase;

    protected Warehouse $warehouse;

    protected Medicine $medicine;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['name' => 'Admin Test']);

        $this->warehouse = Warehouse::create([
            'legacy_id' => 1,
            'name' => 'Gudang Utama',
            'code' => 'GDF',
        ]);

        $unit = MedicineUnit::create(['name' => 'Tablet']);

        $this->medicine = Medicine::create([
            'legacy_id' => 101,
            'name' => 'Alprazolam 0.5mg',
            'code' => 'ALP-05',
            'medicine_unit_id' => $unit->id,
            'is_active' => true,
        ]);

        Config::set('database.connections.legacy', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        DB::connection('legacy')->statement('
            CREATE TABLE lgs_log_stok (
                id INTEGER PRIMARY KEY,
                id_lgs_faktur_detail INTEGER,
                id_gudang INTEGER,
                qty DECIMAL(10,2),
                harga_jual DECIMAL(15,2),
                deleted_at DATETIME
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE lgs_faktur_detail (
                id INTEGER PRIMARY KEY,
                id_obat INTEGER,
                batch VARCHAR(50),
                ed DATE,
                deleted_at DATETIME
            )
        ');
    }

    public function test_can_run_import_warehouse_stocks_dry_run(): void
    {
        DB::connection('legacy')->table('lgs_faktur_detail')->insert([
            'id' => 1,
            'id_obat' => 101,
            'batch' => 'BATCH-001',
            'ed' => '2028-12-31',
        ]);

        DB::connection('legacy')->table('lgs_log_stok')->insert([
            'id' => 10,
            'id_lgs_faktur_detail' => 1,
            'id_gudang' => 1,
            'qty' => 500,
            'harga_jual' => 1000,
        ]);

        $exitCode = Artisan::call('legacy:import-warehouse-stocks', [
            '--dry-run' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertEquals(0, WarehouseStock::count());
    }

    public function test_import_command_is_idempotent(): void
    {
        DB::connection('legacy')->table('lgs_faktur_detail')->insert([
            'id' => 1,
            'id_obat' => 101,
            'batch' => 'BATCH-001',
            'ed' => '2028-12-31',
        ]);

        DB::connection('legacy')->table('lgs_log_stok')->insert([
            'id' => 10,
            'id_lgs_faktur_detail' => 1,
            'id_gudang' => 1,
            'qty' => 500,
            'harga_jual' => 1000,
        ]);

        // First execution
        $exitCode1 = Artisan::call('legacy:import-warehouse-stocks');
        $this->assertEquals(0, $exitCode1);
        $this->assertEquals(1, WarehouseStock::count());

        $stock = WarehouseStock::first();
        $this->assertEquals(500, (float) $stock->quantity);
        $this->assertEquals(1000, (float) $stock->selling_price);
        $this->assertEquals('BATCH-001', $stock->batch_number);

        // Second execution should update without creating duplicate records
        $exitCode2 = Artisan::call('legacy:import-warehouse-stocks');
        $this->assertEquals(0, $exitCode2);
        $this->assertEquals(1, WarehouseStock::count());
    }
}
