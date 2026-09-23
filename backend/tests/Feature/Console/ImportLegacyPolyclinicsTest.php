<?php

namespace Tests\Feature\Console;

use App\Models\Polyclinic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportLegacyPolyclinicsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Override 'legacy' connection with in-memory SQLite for tests
        Config::set('database.connections.legacy', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        DB::connection('legacy')->statement('
            CREATE TABLE ref_poliklinik (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kode VARCHAR(11),
                parent_id INTEGER,
                id_gudang INTEGER,
                nama VARCHAR(50),
                jenis VARCHAR(20),
                deskripsi VARCHAR(100),
                tampil VARCHAR(1),
                status_gudang VARCHAR(1),
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME,
                id_user INTEGER,
                kode_bpjs VARCHAR(255),
                ihs_id_location VARCHAR(255),
                kuota INTEGER,
                suara_antrian VARCHAR(255)
            )
        ');
    }

    // ------------------------------------------------------------------ //
    // Soft-delete detection
    // ------------------------------------------------------------------ //

    public function test_it_treats_null_deleted_at_as_not_deleted()
    {
        DB::connection('legacy')->table('ref_poliklinik')->insert([
            'kode' => 'OP001', 'nama' => 'Poli Umum', 'status' => '1', 'deleted_at' => null,
        ]);

        $this->artisan('legacy:import-polyclinics')->assertExitCode(0);
        $this->assertDatabaseCount('polyclinics', 1);
    }

    public function test_it_treats_zero_date_as_not_deleted()
    {
        DB::connection('legacy')->table('ref_poliklinik')->insert([
            'kode' => 'OP001', 'nama' => 'Poli Umum', 'status' => '1', 'deleted_at' => '0000-00-00 00:00:00',
        ]);

        $this->artisan('legacy:import-polyclinics')->assertExitCode(0);
        $this->assertDatabaseCount('polyclinics', 1);
    }

    public function test_it_treats_zero_date_only_as_not_deleted()
    {
        DB::connection('legacy')->table('ref_poliklinik')->insert([
            'kode' => 'OP001', 'nama' => 'Poli Umum', 'status' => '1', 'deleted_at' => '0000-00-00',
        ]);

        $this->artisan('legacy:import-polyclinics')->assertExitCode(0);
        $this->assertDatabaseCount('polyclinics', 1);
    }

    public function test_it_skips_records_with_actual_deleted_at()
    {
        DB::connection('legacy')->table('ref_poliklinik')->insert([
            ['kode' => 'OP001', 'nama' => 'Poli Aktif', 'status' => '1', 'deleted_at' => null],
            ['kode' => 'OP002', 'nama' => 'Poli Hapus', 'status' => '1', 'deleted_at' => '2021-05-15 15:54:40'],
        ]);

        $this->artisan('legacy:import-polyclinics')->assertExitCode(0);

        $this->assertDatabaseCount('polyclinics', 1);
        $this->assertDatabaseHas('polyclinics', ['code' => 'OP001']);
        $this->assertDatabaseMissing('polyclinics', ['code' => 'OP002']);
    }

    // ------------------------------------------------------------------ //
    // Status mapping (is_active)
    // ------------------------------------------------------------------ //

    public function test_status_1_maps_to_is_active_true()
    {
        DB::connection('legacy')->table('ref_poliklinik')->insert([
            'kode' => 'OP001', 'nama' => 'Poli Aktif', 'status' => '1', 'deleted_at' => null,
        ]);

        $this->artisan('legacy:import-polyclinics')->assertExitCode(0);
        $this->assertDatabaseHas('polyclinics', ['code' => 'OP001', 'is_active' => 1]);
    }

    public function test_status_0_maps_to_is_active_false()
    {
        DB::connection('legacy')->table('ref_poliklinik')->insert([
            'kode' => 'OP001', 'nama' => 'Poli Nonaktif', 'status' => '0', 'deleted_at' => null,
        ]);

        $this->artisan('legacy:import-polyclinics')->assertExitCode(0);
        $this->assertDatabaseHas('polyclinics', ['code' => 'OP001', 'is_active' => 0]);
    }

    // ------------------------------------------------------------------ //
    // Normalization
    // ------------------------------------------------------------------ //

    public function test_it_normalizes_code_to_uppercase_and_trimmed()
    {
        DB::connection('legacy')->table('ref_poliklinik')->insert([
            'kode' => '  op001 ', 'nama' => 'Poli Umum', 'status' => '1', 'deleted_at' => null,
        ]);

        $this->artisan('legacy:import-polyclinics')->assertExitCode(0);
        $this->assertDatabaseHas('polyclinics', ['code' => 'OP001']);
    }

    public function test_it_collapses_whitespace_in_name()
    {
        DB::connection('legacy')->table('ref_poliklinik')->insert([
            'kode' => 'OP001', 'nama' => '  Poli   Umum  ', 'status' => '1', 'deleted_at' => null,
        ]);

        $this->artisan('legacy:import-polyclinics')->assertExitCode(0);
        $this->assertDatabaseHas('polyclinics', ['name' => 'Poli Umum']);
    }

    // ------------------------------------------------------------------ //
    // Code conflicts
    // ------------------------------------------------------------------ //

    public function test_it_reports_duplicate_code_conflict_and_imports_only_first()
    {
        DB::connection('legacy')->table('ref_poliklinik')->insert([
            ['id' => 10, 'kode' => 'OP001', 'nama' => 'Poli A', 'status' => '1', 'deleted_at' => null],
            ['id' => 11, 'kode' => 'OP001', 'nama' => 'Poli B', 'status' => '1', 'deleted_at' => null],
        ]);

        $this->artisan('legacy:import-polyclinics')
            ->expectsOutputToContain('Code Conflicts Detected')
            ->assertExitCode(0);

        $this->assertDatabaseCount('polyclinics', 1);
        $this->assertDatabaseHas('polyclinics', ['legacy_id' => 10]);
        $this->assertDatabaseMissing('polyclinics', ['legacy_id' => 11]);
    }

    public function test_it_reports_empty_code_conflict()
    {
        DB::connection('legacy')->table('ref_poliklinik')->insert([
            ['kode' => '', 'nama' => 'Tanpa Kode', 'status' => '1', 'deleted_at' => null],
            ['kode' => 'OP001', 'nama' => 'Poli Umum', 'status' => '1', 'deleted_at' => null],
        ]);

        $this->artisan('legacy:import-polyclinics')
            ->expectsOutputToContain('Code Conflicts Detected')
            ->assertExitCode(0);

        $this->assertDatabaseCount('polyclinics', 1);
        $this->assertDatabaseHas('polyclinics', ['code' => 'OP001']);
    }

    // ------------------------------------------------------------------ //
    // Dry-run
    // ------------------------------------------------------------------ //

    public function test_dry_run_does_not_save_any_data()
    {
        DB::connection('legacy')->table('ref_poliklinik')->insert([
            'kode' => 'OP001', 'nama' => 'Poli Umum', 'status' => '1', 'deleted_at' => null,
        ]);

        $this->artisan('legacy:import-polyclinics', ['--dry-run' => true])
            ->expectsOutputToContain('DRY-RUN MODE')
            ->expectsOutputToContain('Import Report')
            ->assertExitCode(0);

        $this->assertDatabaseCount('polyclinics', 0);
    }

    public function test_dry_run_shows_would_create_and_would_update_correctly()
    {
        // Seed one existing record in target
        Polyclinic::factory()->create(['legacy_id' => 141, 'code' => 'RO001']);

        DB::connection('legacy')->table('ref_poliklinik')->insert([
            ['id' => 141, 'kode' => 'RO001', 'nama' => 'Ruang VK', 'status' => '1', 'deleted_at' => null],
            ['id' => 1,   'kode' => 'OP001', 'nama' => 'Poli Umum', 'status' => '1', 'deleted_at' => null],
        ]);

        $this->artisan('legacy:import-polyclinics', ['--dry-run' => true])
            ->expectsOutputToContain('Would Create')
            ->expectsOutputToContain('Would Update')
            ->assertExitCode(0);

        // Still nothing new written
        $this->assertDatabaseCount('polyclinics', 1);
    }

    // ------------------------------------------------------------------ //
    // Idempotency
    // ------------------------------------------------------------------ //

    public function test_it_is_idempotent()
    {
        DB::connection('legacy')->table('ref_poliklinik')->insert([
            'kode' => 'OP001', 'nama' => 'Poli Umum', 'status' => '1', 'deleted_at' => null,
        ]);

        $this->artisan('legacy:import-polyclinics')->assertExitCode(0);
        $this->assertDatabaseCount('polyclinics', 1);

        $this->artisan('legacy:import-polyclinics')->assertExitCode(0);
        $this->assertDatabaseCount('polyclinics', 1);

        $polis = Polyclinic::all();
        $this->assertCount(1, $polis);
    }

    // ------------------------------------------------------------------ //
    // Hierarchy
    // ------------------------------------------------------------------ //

    public function test_it_wires_hierarchy_correctly_in_two_phases()
    {
        $parentId = DB::connection('legacy')->table('ref_poliklinik')->insertGetId([
            'kode'       => 'OP001',
            'nama'       => 'Poli Umum',
            'status'     => '1',
            'parent_id'  => null,
            'deleted_at' => null,
        ]);

        DB::connection('legacy')->table('ref_poliklinik')->insert([
            'kode'       => 'OP001A',
            'nama'       => 'Poli Umum Sub',
            'status'     => '1',
            'parent_id'  => $parentId,
            'deleted_at' => null,
        ]);

        $this->artisan('legacy:import-polyclinics')->assertExitCode(0);
        $this->assertDatabaseCount('polyclinics', 2);

        $parent = Polyclinic::where('code', 'OP001')->first();
        $child  = Polyclinic::where('code', 'OP001A')->first();

        $this->assertNull($parent->parent_id);
        $this->assertEquals($parent->id, $child->parent_id);
    }

    public function test_it_sets_parent_id_null_when_parent_is_soft_deleted_and_reports_warning()
    {
        // Parent is soft-deleted (actual date)
        $softDeletedId = DB::connection('legacy')->table('ref_poliklinik')->insertGetId([
            'kode'       => 'SDPARENT',
            'nama'       => 'Parent Dihapus',
            'status'     => '0',
            'deleted_at' => '2021-05-15 15:54:40',
        ]);

        // Child points to that soft-deleted parent
        DB::connection('legacy')->table('ref_poliklinik')->insert([
            'kode'       => 'CHILD001',
            'nama'       => 'Poli Anak',
            'status'     => '1',
            'parent_id'  => $softDeletedId,
            'deleted_at' => null,
        ]);

        $this->artisan('legacy:import-polyclinics')
            ->expectsOutputToContain('Hierarchy Warnings')
            ->assertExitCode(0);

        $this->assertDatabaseCount('polyclinics', 1);
        $child = Polyclinic::where('code', 'CHILD001')->first();
        $this->assertNull($child->parent_id);
    }
}
