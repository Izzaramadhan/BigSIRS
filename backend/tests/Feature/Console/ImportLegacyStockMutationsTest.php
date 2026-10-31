<?php

namespace Tests\Feature\Console;

use App\Models\Logistics\StockMutation;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\MedicineUnit;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportLegacyStockMutationsTest extends TestCase
{
    use RefreshDatabase;

    protected Warehouse $warehouse1;

    protected Warehouse $warehouse2;

    protected Medicine $medicine1;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['name' => 'Admin Test']);

        // Setup local master data with legacy_id
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
            'is_active' => true,
        ]);

        // Mock legacy database connection as sqlite in-memory
        Config::set('database.connections.legacy', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        DB::connection('legacy')->statement('
            CREATE TABLE lgs_mutasi (
                id INTEGER PRIMARY KEY,
                tanggal DATE,
                id_gudang_asal INTEGER,
                id_gudang_tujuan INTEGER,
                note TEXT,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE lgs_mutasi_detail (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_mutasi INTEGER,
                id_lgs_faktur_detail INTEGER,
                qty DECIMAL(10,2)
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

    public function test_can_import_valid_legacy_mutations(): void
    {
        DB::connection('legacy')->table('lgs_mutasi')->insert([
            'id' => 1,
            'tanggal' => '2026-05-10',
            'id_gudang_asal' => 1,
            'id_gudang_tujuan' => 2,
            'note' => 'Mutasi tes valid',
            'created_at' => '2026-05-10 08:00:00',
        ]);

        DB::connection('legacy')->table('lgs_faktur_detail')->insert([
            'id' => 501,
            'id_obat' => 101,
            'batch' => 'BATCH-TEST-1',
            'ed' => '2027-12-31',
        ]);

        DB::connection('legacy')->table('lgs_mutasi_detail')->insert([
            'id_mutasi' => 1,
            'id_lgs_faktur_detail' => 501,
            'qty' => 25.00,
        ]);

        $this->artisan('import:legacy-mutations')
            ->expectsOutputToContain('Found 1 mutations in legacy database.')
            ->assertSuccessful();

        $this->assertEquals(1, StockMutation::count());
        $mutation = StockMutation::where('legacy_id', 1)->first();
        $this->assertNotNull($mutation);
        $this->assertEquals($this->warehouse1->id, $mutation->source_warehouse_id);
        $this->assertEquals($this->warehouse2->id, $mutation->target_warehouse_id);
        $this->assertEquals('Mutasi tes valid', $mutation->notes);
        $this->assertEquals(1, $mutation->items()->count());
        $this->assertEquals(25.00, (float) $mutation->items()->first()->quantity);
    }

    public function test_importer_is_idempotent(): void
    {
        DB::connection('legacy')->table('lgs_mutasi')->insert([
            'id' => 1,
            'tanggal' => '2026-05-10',
            'id_gudang_asal' => 1,
            'id_gudang_tujuan' => 2,
            'note' => 'Idempotency test',
            'created_at' => '2026-05-10 08:00:00',
        ]);

        DB::connection('legacy')->table('lgs_faktur_detail')->insert([
            'id' => 501,
            'id_obat' => 101,
            'batch' => 'BATCH-TEST-1',
            'ed' => '2027-12-31',
        ]);

        DB::connection('legacy')->table('lgs_mutasi_detail')->insert([
            'id_mutasi' => 1,
            'id_lgs_faktur_detail' => 501,
            'qty' => 10.00,
        ]);

        // First run: imports 1
        $this->artisan('import:legacy-mutations')->assertSuccessful();
        $this->assertEquals(1, StockMutation::count());

        // Second run: skips existing record
        $this->artisan('import:legacy-mutations')
            ->expectsOutputToContain('Mutation legacy ID 1 already imported. Skipping.')
            ->assertSuccessful();

        $this->assertEquals(1, StockMutation::count());
    }

    public function test_importer_fails_safely_when_warehouse_is_invalid(): void
    {
        // Legacy ID 99 warehouse does not exist
        DB::connection('legacy')->table('lgs_mutasi')->insert([
            'id' => 2,
            'tanggal' => '2026-05-10',
            'id_gudang_asal' => 99,
            'id_gudang_tujuan' => 2,
            'note' => 'Invalid warehouse mutation',
            'created_at' => '2026-05-10 08:00:00',
        ]);

        $this->artisan('import:legacy-mutations')
            ->expectsOutputToContain('Failed mapping warehouses for legacy mutation ID 2')
            ->assertSuccessful();

        $this->assertEquals(0, StockMutation::count());
    }

    public function test_importer_fails_safely_when_medicine_is_invalid(): void
    {
        DB::connection('legacy')->table('lgs_mutasi')->insert([
            'id' => 3,
            'tanggal' => '2026-05-10',
            'id_gudang_asal' => 1,
            'id_gudang_tujuan' => 2,
            'note' => 'Invalid medicine mutation',
            'created_at' => '2026-05-10 08:00:00',
        ]);

        // id_obat 999 does not exist in BigSIRS
        DB::connection('legacy')->table('lgs_faktur_detail')->insert([
            'id' => 502,
            'id_obat' => 999,
            'batch' => 'BATCH-TEST-2',
            'ed' => '2027-12-31',
        ]);

        DB::connection('legacy')->table('lgs_mutasi_detail')->insert([
            'id_mutasi' => 3,
            'id_lgs_faktur_detail' => 502,
            'qty' => 5.00,
        ]);

        $this->artisan('import:legacy-mutations')
            ->expectsOutputToContain('Legacy medicine ID 999 not found in BigSIRS')
            ->assertSuccessful();

        $this->assertEquals(0, StockMutation::count());
    }

    public function test_importer_supports_dry_run(): void
    {
        DB::connection('legacy')->table('lgs_mutasi')->insert([
            'id' => 4,
            'tanggal' => '2026-05-10',
            'id_gudang_asal' => 1,
            'id_gudang_tujuan' => 2,
            'note' => 'Dry run test',
            'created_at' => '2026-05-10 08:00:00',
        ]);

        DB::connection('legacy')->table('lgs_faktur_detail')->insert([
            'id' => 503,
            'id_obat' => 101,
            'batch' => 'BATCH-DRY',
            'ed' => '2027-12-31',
        ]);

        DB::connection('legacy')->table('lgs_mutasi_detail')->insert([
            'id_mutasi' => 4,
            'id_lgs_faktur_detail' => 503,
            'qty' => 15.00,
        ]);

        $this->artisan('import:legacy-mutations --dry-run')
            ->expectsOutputToContain('DRY RUN: No data will be modified.')
            ->assertSuccessful();

        $this->assertEquals(0, StockMutation::count());
    }
}

