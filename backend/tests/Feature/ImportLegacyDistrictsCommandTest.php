<?php

namespace Tests\Feature;

use App\Models\Regency;
use App\Models\District;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportLegacyDistrictsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite') {
            $this->markTestSkipped('Database tests can only be run on SQLite in-memory database.');
        }

        // Mock the legacy table
        DB::connection('legacy')->statement('DROP TABLE IF EXISTS ref_kecamatan');
        DB::connection('legacy')->statement('
            CREATE TABLE ref_kecamatan (
                id VARCHAR(50),
                id_kabupaten VARCHAR(50),
                nama VARCHAR(255),
                kode_kecamatan VARCHAR(50),
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                deleted_at TIMESTAMP NULL,
                id_user INT,
                status INT
            )
        ');
    }

    public function test_imports_districts_correctly()
    {
        $regency = Regency::factory()->create(['legacy_id' => '1101']);

        DB::connection('legacy')->table('ref_kecamatan')->insert([
            'id' => '1101010',
            'id_kabupaten' => '1101',
            'nama' => 'TEUPAH SELATAN',
            'kode_kecamatan' => '',
            'status' => 1,
            'deleted_at' => null,
        ]);

        $this->artisan('legacy:import-districts')->assertSuccessful();

        $this->assertDatabaseHas('districts', [
            'legacy_id' => '1101010',
            'regency_id' => $regency->id,
            'name' => 'TEUPAH SELATAN',
            'code' => '1101010',
            'is_active' => 1,
        ]);
    }

    public function test_skips_deleted_districts()
    {
        $regency = Regency::factory()->create(['legacy_id' => '1101']);

        DB::connection('legacy')->table('ref_kecamatan')->insert([
            'id' => '1101010',
            'id_kabupaten' => '1101',
            'nama' => 'TEUPAH SELATAN',
            'status' => 1,
            'deleted_at' => '2023-01-01 00:00:00',
        ]);

        $this->artisan('legacy:import-districts')->assertSuccessful();

        $this->assertDatabaseMissing('districts', [
            'legacy_id' => '1101010',
        ]);
    }

    public function test_skips_when_regency_not_found()
    {
        DB::connection('legacy')->table('ref_kecamatan')->insert([
            'id' => '1101010',
            'id_kabupaten' => '9999',
            'nama' => 'MISSING',
            'status' => 1,
            'deleted_at' => null,
        ]);

        $this->artisan('legacy:import-districts')->assertSuccessful();

        $this->assertDatabaseMissing('districts', [
            'legacy_id' => '1101010',
        ]);
    }
}
