<?php

namespace Tests\Feature\Console;

use App\Models\MasterData\Building;
use App\Models\MasterData\Floor;
use Database\Seeders\FloorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportLegacyFloorsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.connections.legacy', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        DB::connection('legacy')->statement('
            CREATE TABLE ref_lantai (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_gedung INTEGER,
                nama VARCHAR(100),
                deskripsi TEXT,
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME,
                id_user INTEGER
            )
        ');
    }

    public function test_can_import_active_floors_from_legacy_ref_lantai(): void
    {
        $building = Building::factory()->create(['legacy_id' => 1, 'name' => 'Gedung A']);

        DB::connection('legacy')->table('ref_lantai')->insert([
            [
                'id' => 1,
                'id_gedung' => 1,
                'nama' => 'Lantai 1',
                'deskripsi' => 'Lantai Pelayanan',
                'status' => '1',
                'deleted_at' => '0000-00-00 00:00:00',
            ],
            [
                'id' => 2,
                'id_gedung' => 1,
                'nama' => 'Lantai 2',
                'deskripsi' => null,
                'status' => '1',
                'deleted_at' => null,
            ],
        ]);

        $this->artisan('legacy:import-floors')->assertSuccessful();

        $this->assertEquals(2, Floor::count());
        $this->assertDatabaseHas('floors', [
            'legacy_id' => 1,
            'building_id' => $building->id,
            'name' => 'Lantai 1',
            'description' => 'Lantai Pelayanan',
        ]);
        $this->assertDatabaseHas('floors', [
            'legacy_id' => 2,
            'building_id' => $building->id,
            'name' => 'Lantai 2',
        ]);
    }

    public function test_skips_soft_deleted_legacy_records(): void
    {
        $building = Building::factory()->create(['legacy_id' => 1]);

        DB::connection('legacy')->table('ref_lantai')->insert([
            [
                'id' => 1,
                'id_gedung' => 1,
                'nama' => 'Lantai Aktif',
                'deskripsi' => 'Aktif',
                'status' => '1',
                'deleted_at' => null,
            ],
            [
                'id' => 5,
                'id_gedung' => 1,
                'nama' => 'Lantai Terhapus',
                'deskripsi' => 'Dihapus',
                'status' => '0',
                'deleted_at' => '2018-10-31 07:05:02',
            ],
        ]);

        $this->artisan('legacy:import-floors')->assertSuccessful();

        $this->assertEquals(1, Floor::count());
        $this->assertDatabaseHas('floors', ['name' => 'Lantai Aktif']);
        $this->assertDatabaseMissing('floors', ['name' => 'Lantai Terhapus']);
    }

    public function test_dry_run_does_not_persist_data(): void
    {
        Building::factory()->create(['legacy_id' => 1]);

        DB::connection('legacy')->table('ref_lantai')->insert([
            [
                'id' => 1,
                'id_gedung' => 1,
                'nama' => 'Lantai Dry Run',
                'deskripsi' => 'Test',
                'status' => '1',
                'deleted_at' => null,
            ],
        ]);

        $this->artisan('legacy:import-floors', ['--dry-run' => true])->assertSuccessful();

        $this->assertEquals(0, Floor::count());
    }

    public function test_import_is_idempotent(): void
    {
        $building = Building::factory()->create(['legacy_id' => 1]);

        DB::connection('legacy')->table('ref_lantai')->insert([
            [
                'id' => 1,
                'id_gedung' => 1,
                'nama' => 'Lantai 1',
                'deskripsi' => 'Deskripsi Awal',
                'status' => '1',
                'deleted_at' => null,
            ],
        ]);

        $this->artisan('legacy:import-floors')->assertSuccessful();
        $this->assertEquals(1, Floor::count());

        DB::connection('legacy')->table('ref_lantai')->where('id', 1)->update([
            'deskripsi' => 'Deskripsi Baru',
        ]);

        $this->artisan('legacy:import-floors')->assertSuccessful();
        $this->assertEquals(1, Floor::count());
        $this->assertDatabaseHas('floors', [
            'name' => 'Lantai 1',
            'description' => 'Deskripsi Baru',
        ]);
    }

    public function test_seeder_executes_import(): void
    {
        Building::factory()->create(['legacy_id' => 1]);

        DB::connection('legacy')->table('ref_lantai')->insert([
            [
                'id' => 1,
                'id_gedung' => 1,
                'nama' => 'Lantai Seeder',
                'deskripsi' => 'Seeder',
                'status' => '1',
                'deleted_at' => null,
            ],
        ]);

        $this->seed(FloorSeeder::class);

        $this->assertEquals(1, Floor::count());
        $this->assertDatabaseHas('floors', ['name' => 'Lantai Seeder']);
    }
}
