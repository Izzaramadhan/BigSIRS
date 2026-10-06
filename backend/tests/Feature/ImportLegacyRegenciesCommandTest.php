<?php

namespace Tests\Feature;

use App\Models\Regency;
use App\Models\Province;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportLegacyRegenciesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock legacy database
        DB::connection('legacy')->statement('CREATE TABLE ref_kabupaten (id CHAR(4), id_provinsi CHAR(2), nama VARCHAR(255), status INT, updated_at DATETIME, deleted_at DATETIME)');
    }

    protected function tearDown(): void
    {
        DB::connection('legacy')->statement('DROP TABLE IF EXISTS ref_kabupaten');
        parent::tearDown();
    }

    public function test_imports_regencies_correctly()
    {
        $province = Province::factory()->create(['legacy_id' => '11']);

        DB::connection('legacy')->table('ref_kabupaten')->insert([
            ['id' => '1101', 'id_provinsi' => '11', 'nama' => 'KABUPATEN A', 'status' => 1, 'updated_at' => '0000-00-00 00:00:00', 'deleted_at' => '0000-00-00 00:00:00'],
            ['id' => '1102', 'id_provinsi' => '11', 'nama' => 'KOTA B', 'status' => 0, 'updated_at' => '0000-00-00 00:00:00', 'deleted_at' => '0000-00-00 00:00:00'],
        ]);

        $this->artisan('legacy:import-regencies')
            ->assertExitCode(0)
            ->expectsTable(
                ['Total Read', 'Valid/Processed', 'Skipped', 'Conflicts', 'Would Create / Created', 'Would Update / Updated', 'Failed'],
                [
                    [2, 2, 0, 0, 2, 0, 0]
                ]
            );

        $this->assertDatabaseHas('regencies', ['code' => '1101', 'name' => 'KABUPATEN A', 'province_id' => $province->id, 'is_active' => true]);
        $this->assertDatabaseHas('regencies', ['code' => '1102', 'name' => 'KOTA B', 'province_id' => $province->id, 'is_active' => false]);
    }

    public function test_skips_deleted_regencies()
    {
        $province = Province::factory()->create(['legacy_id' => '11']);

        DB::connection('legacy')->table('ref_kabupaten')->insert([
            ['id' => '1101', 'id_provinsi' => '11', 'nama' => 'KABUPATEN A', 'status' => 1, 'updated_at' => now(), 'deleted_at' => now()],
        ]);

        $this->artisan('legacy:import-regencies')
            ->assertExitCode(0)
            ->expectsTable(
                ['Total Read', 'Valid/Processed', 'Skipped', 'Conflicts', 'Would Create / Created', 'Would Update / Updated', 'Failed'],
                [
                    [1, 0, 1, 0, 0, 0, 0]
                ]
            );

        $this->assertDatabaseCount('regencies', 0);
    }

    public function test_skips_when_province_not_found()
    {
        // No province mapped
        DB::connection('legacy')->table('ref_kabupaten')->insert([
            ['id' => '1101', 'id_provinsi' => '99', 'nama' => 'KABUPATEN A', 'status' => 1, 'updated_at' => '0000-00-00 00:00:00', 'deleted_at' => '0000-00-00 00:00:00'],
        ]);

        $this->artisan('legacy:import-regencies')
            ->assertExitCode(0)
            ->expectsTable(
                ['Total Read', 'Valid/Processed', 'Skipped', 'Conflicts', 'Would Create / Created', 'Would Update / Updated', 'Failed'],
                [
                    [1, 0, 1, 0, 0, 0, 0]
                ]
            );
    }
}
