<?php

namespace Tests\Feature\Console;

use App\Models\MasterData\Floor;
use App\Models\MasterData\Ward;
use Database\Seeders\WardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportLegacyWardsTest extends TestCase
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
            CREATE TABLE ref_bangsal (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_lantai INTEGER,
                kode VARCHAR(50),
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

    public function test_can_import_active_wards_from_legacy_ref_bangsal(): void
    {
        $floor = Floor::factory()->create(['legacy_id' => 2, 'name' => 'Lantai 2']);

        DB::connection('legacy')->table('ref_bangsal')->insert([
            [
                'id' => 3,
                'id_lantai' => 2,
                'kode' => 'hcu',
                'nama' => 'Bangsal Anak',
                'deskripsi' => 'Ruang Perawatan Anak',
                'status' => '1',
                'deleted_at' => '0000-00-00 00:00:00',
            ],
            [
                'id' => 4,
                'id_lantai' => 2,
                'kode' => 'cem',
                'nama' => 'Penyakit Dalam/Dewasa',
                'deskripsi' => null,
                'status' => '1',
                'deleted_at' => null,
            ],
        ]);

        $this->artisan('legacy:import-wards')->assertSuccessful();

        $this->assertEquals(2, Ward::count());
        $this->assertDatabaseHas('wards', [
            'legacy_id' => 3,
            'floor_id' => $floor->id,
            'name' => 'Bangsal Anak',
            'description' => 'Ruang Perawatan Anak',
        ]);
        $this->assertDatabaseHas('wards', [
            'legacy_id' => 4,
            'floor_id' => $floor->id,
            'name' => 'Penyakit Dalam/Dewasa',
        ]);
    }

    public function test_skips_soft_deleted_legacy_records(): void
    {
        $floor = Floor::factory()->create(['legacy_id' => 1]);

        DB::connection('legacy')->table('ref_bangsal')->insert([
            [
                'id' => 1,
                'id_lantai' => 1,
                'kode' => 'bog',
                'nama' => 'Bangsal Aktif',
                'deskripsi' => 'Aktif',
                'status' => '1',
                'deleted_at' => null,
            ],
            [
                'id' => 2,
                'id_lantai' => 1,
                'kode' => 'del',
                'nama' => 'Bangsal Terhapus',
                'deskripsi' => 'Dihapus',
                'status' => '0',
                'deleted_at' => '2021-05-15 16:43:43',
            ],
        ]);

        $this->artisan('legacy:import-wards')->assertSuccessful();

        $this->assertEquals(1, Ward::count());
        $this->assertDatabaseHas('wards', ['name' => 'Bangsal Aktif']);
        $this->assertDatabaseMissing('wards', ['name' => 'Bangsal Terhapus']);
    }

    public function test_dry_run_does_not_persist_data(): void
    {
        Floor::factory()->create(['legacy_id' => 2]);

        DB::connection('legacy')->table('ref_bangsal')->insert([
            [
                'id' => 3,
                'id_lantai' => 2,
                'kode' => 'dry',
                'nama' => 'Bangsal Dry Run',
                'deskripsi' => 'Test',
                'status' => '1',
                'deleted_at' => null,
            ],
        ]);

        $this->artisan('legacy:import-wards', ['--dry-run' => true])->assertSuccessful();

        $this->assertEquals(0, Ward::count());
    }

    public function test_import_is_idempotent(): void
    {
        $floor = Floor::factory()->create(['legacy_id' => 2]);

        DB::connection('legacy')->table('ref_bangsal')->insert([
            [
                'id' => 3,
                'id_lantai' => 2,
                'kode' => 'hcu',
                'nama' => 'Bangsal Anak',
                'deskripsi' => 'Deskripsi Awal',
                'status' => '1',
                'deleted_at' => null,
            ],
        ]);

        $this->artisan('legacy:import-wards')->assertSuccessful();
        $this->assertEquals(1, Ward::count());

        DB::connection('legacy')->table('ref_bangsal')->where('id', 3)->update([
            'deskripsi' => 'Deskripsi Baru',
        ]);

        $this->artisan('legacy:import-wards')->assertSuccessful();
        $this->assertEquals(1, Ward::count());
        $this->assertDatabaseHas('wards', [
            'name' => 'Bangsal Anak',
            'description' => 'Deskripsi Baru',
        ]);
    }

    public function test_seeder_executes_import(): void
    {
        Floor::factory()->create(['legacy_id' => 2]);

        DB::connection('legacy')->table('ref_bangsal')->insert([
            [
                'id' => 3,
                'id_lantai' => 2,
                'kode' => 'seed',
                'nama' => 'Bangsal Seeder',
                'deskripsi' => 'Seeder',
                'status' => '1',
                'deleted_at' => null,
            ],
        ]);

        $this->seed(WardSeeder::class);

        $this->assertEquals(1, Ward::count());
        $this->assertDatabaseHas('wards', ['name' => 'Bangsal Seeder']);
    }
}
