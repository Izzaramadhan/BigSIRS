<?php

namespace Tests\Feature\Console;

use App\Models\MasterData\Building;
use Database\Seeders\BuildingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportLegacyBuildingsTest extends TestCase
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
            CREATE TABLE ref_gedung (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
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

    public function test_can_import_active_buildings_from_legacy_ref_gedung(): void
    {
        DB::connection('legacy')->table('ref_gedung')->insert([
            [
                'id' => 1,
                'nama' => 'RUANG BUGENVILLE',
                'deskripsi' => 'BANGUNAN BANGSAL BUGENVILLE',
                'status' => '1',
                'deleted_at' => '0000-00-00 00:00:00',
            ],
            [
                'id' => 2,
                'nama' => 'RUANG ANGGREK',
                'deskripsi' => 'BANGUNAN BANGSAL ANGGREK',
                'status' => '1',
                'deleted_at' => null,
            ],
        ]);

        $this->artisan('legacy:import-buildings')
            ->assertSuccessful();

        $this->assertEquals(2, Building::count());
        $this->assertDatabaseHas('buildings', [
            'legacy_id' => 1,
            'name' => 'RUANG BUGENVILLE',
            'description' => 'BANGUNAN BANGSAL BUGENVILLE',
        ]);
        $this->assertDatabaseHas('buildings', [
            'legacy_id' => 2,
            'name' => 'RUANG ANGGREK',
            'description' => 'BANGUNAN BANGSAL ANGGREK',
        ]);
    }

    public function test_skips_soft_deleted_legacy_records(): void
    {
        DB::connection('legacy')->table('ref_gedung')->insert([
            [
                'id' => 1,
                'nama' => 'Gedung Aktif',
                'deskripsi' => 'Aktif',
                'status' => '1',
                'deleted_at' => null,
            ],
            [
                'id' => 132,
                'nama' => 'Gedung Terhapus',
                'deskripsi' => 'Dihapus',
                'status' => '0',
                'deleted_at' => '2018-11-06 06:12:04',
            ],
        ]);

        $this->artisan('legacy:import-buildings')
            ->assertSuccessful();

        $this->assertEquals(1, Building::count());
        $this->assertDatabaseHas('buildings', ['name' => 'Gedung Aktif']);
        $this->assertDatabaseMissing('buildings', ['name' => 'Gedung Terhapus']);
    }

    public function test_dry_run_does_not_persist_data(): void
    {
        DB::connection('legacy')->table('ref_gedung')->insert([
            [
                'id' => 1,
                'nama' => 'Gedung Dry Run',
                'deskripsi' => 'Test',
                'status' => '1',
                'deleted_at' => null,
            ],
        ]);

        $this->artisan('legacy:import-buildings', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertEquals(0, Building::count());
    }

    public function test_import_is_idempotent(): void
    {
        DB::connection('legacy')->table('ref_gedung')->insert([
            [
                'id' => 1,
                'nama' => 'Gedung Utama',
                'deskripsi' => 'Deskripsi Awal',
                'status' => '1',
                'deleted_at' => null,
            ],
        ]);

        $this->artisan('legacy:import-buildings')->assertSuccessful();
        $this->assertEquals(1, Building::count());

        // Update in legacy
        DB::connection('legacy')->table('ref_gedung')->where('id', 1)->update([
            'deskripsi' => 'Deskripsi Baru',
        ]);

        $this->artisan('legacy:import-buildings')->assertSuccessful();
        $this->assertEquals(1, Building::count());
        $this->assertDatabaseHas('buildings', [
            'name' => 'Gedung Utama',
            'description' => 'Deskripsi Baru',
        ]);
    }

    public function test_seeder_executes_import(): void
    {
        DB::connection('legacy')->table('ref_gedung')->insert([
            [
                'id' => 1,
                'nama' => 'Gedung Seeder',
                'deskripsi' => 'Imported via Seeder',
                'status' => '1',
                'deleted_at' => null,
            ],
        ]);

        $this->seed(BuildingSeeder::class);

        $this->assertEquals(1, Building::count());
        $this->assertDatabaseHas('buildings', ['name' => 'Gedung Seeder']);
    }
}
