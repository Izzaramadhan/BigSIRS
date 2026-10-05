<?php

namespace Tests\Feature\Console;

use App\Models\Guarantor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Enums\GuarantorType;

class LegacyImportGuarantorsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock legacy database connection
        $legacyDb = DB::connection('legacy');
        
        $legacyDb->statement('DROP TABLE IF EXISTS ref_jenis_asuransi');
        $legacyDb->statement('
            CREATE TABLE ref_jenis_asuransi (
                id INT PRIMARY KEY,
                jenis_asuransi VARCHAR(255),
                pemerintah TINYINT(1),
                status TINYINT(1),
                type VARCHAR(50),
                id_inacbg VARCHAR(10),
                kode VARCHAR(50),
                deleted_at DATETIME NULL
            )
        ');
    }

    public function test_can_import_guarantors_from_legacy_db()
    {
        $legacyDb = DB::connection('legacy');
        
        $legacyDb->insert("
            INSERT INTO ref_jenis_asuransi 
            (id, jenis_asuransi, pemerintah, status, type, id_inacbg, kode, deleted_at) 
            VALUES 
            (1, 'BPJS KESEHATAN', 1, 1, 'BPJS', '3', 'ASR-001', '0000-00-00 00:00:00'),
            (2, 'ASURANSI SWASTA', 0, 1, 'PRIVATE', '2', '', NULL),
            (3, 'UMUM', 0, 0, 'UMUM', '1', 'ASR-003', NULL),
            (4, 'DELETED ASURANSI', 1, 1, 'UMUM', '4', 'ASR-004', '2023-01-01 10:00:00')
        ");

        $this->artisan('legacy:import-guarantors')
             ->expectsOutputToContain('Fetching legacy guarantors from ref_jenis_asuransi...')
             ->expectsOutputToContain('Import completed.')
             ->assertExitCode(0);

        $this->assertDatabaseCount('guarantors', 3);
        
        $this->assertDatabaseHas('guarantors', [
            'legacy_id' => 1,
            'name' => 'BPJS KESEHATAN',
            'is_government' => true,
            'is_active' => true,
            'type' => GuarantorType::Bpjs->value,
            'inacbg_id' => '3',
            'code' => 'ASR-001',
        ]);

        $this->assertDatabaseHas('guarantors', [
            'legacy_id' => 2,
            'name' => 'ASURANSI SWASTA',
            'is_government' => false,
            'is_active' => true,
            'type' => GuarantorType::PrivateInsurance->value,
            'inacbg_id' => '2',
            'code' => 'GUR-00002',
        ]);
        
        $this->assertDatabaseMissing('guarantors', [
            'legacy_id' => 4,
        ]);
    }

    public function test_dry_run_does_not_modify_database()
    {
        $legacyDb = DB::connection('legacy');
        
        $legacyDb->insert("
            INSERT INTO ref_jenis_asuransi 
            (id, jenis_asuransi, pemerintah, status, type, id_inacbg, kode, deleted_at) 
            VALUES 
            (1, 'BPJS KESEHATAN', 1, 1, 'BPJS', '3', 'ASR-001', NULL)
        ");

        $this->artisan('legacy:import-guarantors', ['--dry-run' => true])
             ->expectsOutputToContain('Running in DRY-RUN mode. No data will be written.')
             ->assertExitCode(0);

        $this->assertDatabaseCount('guarantors', 0);
    }
}
