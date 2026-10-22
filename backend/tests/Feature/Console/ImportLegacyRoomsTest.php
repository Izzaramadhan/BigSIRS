<?php

namespace Tests\Feature\Console;

use App\Models\MasterData\Room;
use App\Models\MasterData\Ward;
use Database\Seeders\RoomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportLegacyRoomsTest extends TestCase
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
            CREATE TABLE ref_kamar (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kode VARCHAR(50),
                id_bangsal INTEGER,
                id_kelas INTEGER,
                nama VARCHAR(100),
                deskripsi TEXT,
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME,
                id_user INTEGER,
                status_update_aplicares SMALLINT,
                total_bed INTEGER,
                total_bed_terpakai INTEGER,
                total_bed_kosong INTEGER,
                status_update_sirs SMALLINT
            )
        ');
    }

    public function test_can_import_active_rooms_from_legacy_ref_kamar(): void
    {
        $ward = Ward::factory()->create(['legacy_id' => 1, 'name' => 'Bangsal Anak']);

        DB::connection('legacy')->table('ref_kamar')->insert([
            [
                'id' => 1,
                'kode' => 'IP001',
                'id_bangsal' => 1,
                'id_kelas' => 3,
                'nama' => 'Kamar 1',
                'deskripsi' => 'Ruang Anak',
                'status' => '1',
                'deleted_at' => '0000-00-00 00:00:00',
            ],
            [
                'id' => 2,
                'kode' => 'IP002',
                'id_bangsal' => 1,
                'id_kelas' => 2,
                'nama' => 'Kamar 2',
                'deskripsi' => null,
                'status' => '1',
                'deleted_at' => null,
            ],
        ]);

        $this->artisan('legacy:import-rooms')->assertSuccessful();

        $this->assertEquals(2, Room::count());
        $this->assertDatabaseHas('rooms', [
            'legacy_id' => 1,
            'ward_id' => $ward->id,
            'code' => 'IP001',
            'name' => 'Kamar 1',
            'class_id' => 3,
            'description' => 'Ruang Anak',
        ]);
        $this->assertDatabaseHas('rooms', [
            'legacy_id' => 2,
            'ward_id' => $ward->id,
            'code' => 'IP002',
            'name' => 'Kamar 2',
            'class_id' => 2,
        ]);
    }

    public function test_skips_soft_deleted_legacy_records(): void
    {
        $ward = Ward::factory()->create(['legacy_id' => 1]);

        DB::connection('legacy')->table('ref_kamar')->insert([
            [
                'id' => 1,
                'kode' => 'IP001',
                'id_bangsal' => 1,
                'id_kelas' => 1,
                'nama' => 'Kamar Aktif',
                'deskripsi' => 'Aktif',
                'status' => '1',
                'deleted_at' => null,
            ],
            [
                'id' => 2,
                'kode' => 'IP002',
                'id_bangsal' => 1,
                'id_kelas' => 1,
                'nama' => 'Kamar Terhapus',
                'deskripsi' => 'Dihapus',
                'status' => '0',
                'deleted_at' => '2023-01-30 16:24:24',
            ],
        ]);

        $this->artisan('legacy:import-rooms')->assertSuccessful();

        $this->assertEquals(1, Room::count());
        $this->assertDatabaseHas('rooms', ['name' => 'Kamar Aktif']);
        $this->assertDatabaseMissing('rooms', ['name' => 'Kamar Terhapus']);
    }

    public function test_dry_run_does_not_persist_data(): void
    {
        Ward::factory()->create(['legacy_id' => 1]);

        DB::connection('legacy')->table('ref_kamar')->insert([
            [
                'id' => 1,
                'kode' => 'DRY-01',
                'id_bangsal' => 1,
                'id_kelas' => 1,
                'nama' => 'Kamar Dry Run',
                'deskripsi' => 'Test',
                'status' => '1',
                'deleted_at' => null,
            ],
        ]);

        $this->artisan('legacy:import-rooms', ['--dry-run' => true])->assertSuccessful();

        $this->assertEquals(0, Room::count());
    }

    public function test_import_is_idempotent(): void
    {
        $ward = Ward::factory()->create(['legacy_id' => 1]);

        DB::connection('legacy')->table('ref_kamar')->insert([
            [
                'id' => 1,
                'kode' => 'IP001',
                'id_bangsal' => 1,
                'id_kelas' => 1,
                'nama' => 'Kamar 1',
                'deskripsi' => 'Deskripsi Awal',
                'status' => '1',
                'deleted_at' => null,
            ],
        ]);

        $this->artisan('legacy:import-rooms')->assertSuccessful();
        $this->assertEquals(1, Room::count());

        DB::connection('legacy')->table('ref_kamar')->where('id', 1)->update([
            'deskripsi' => 'Deskripsi Baru',
        ]);

        $this->artisan('legacy:import-rooms')->assertSuccessful();
        $this->assertEquals(1, Room::count());
        $this->assertDatabaseHas('rooms', [
            'name' => 'Kamar 1',
            'description' => 'Deskripsi Baru',
        ]);
    }

    public function test_seeder_executes_import(): void
    {
        Ward::factory()->create(['legacy_id' => 1]);

        DB::connection('legacy')->table('ref_kamar')->insert([
            [
                'id' => 1,
                'kode' => 'SEED-01',
                'id_bangsal' => 1,
                'id_kelas' => 1,
                'nama' => 'Kamar Seeder',
                'deskripsi' => 'Seeder',
                'status' => '1',
                'deleted_at' => null,
            ],
        ]);

        $this->seed(RoomSeeder::class);

        $this->assertEquals(1, Room::count());
        $this->assertDatabaseHas('rooms', ['name' => 'Kamar Seeder']);
    }
}
