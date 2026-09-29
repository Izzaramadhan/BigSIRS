<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('legacy:import-procedure-user-mappings {--dry-run : Only show what would be imported}')]
#[Description('Import procedure to employee mappings from legacy database')]
class LegacyImportProcedureUserMappingsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->info("Running in DRY-RUN mode. No data will be written.");
        }

        $legacyDb = \Illuminate\Support\Facades\DB::connection('legacy');
        
        $this->info("Fetching legacy mappings from map_ref_tindakan_user...");

        $mappings = $legacyDb->table('map_ref_tindakan_user')->get();

        $stats = ['create' => 0, 'skip' => 0, 'failed' => 0, 'orphan' => 0];

        // Fetch local models
        $procedures = \App\Models\MasterData\MedicalProcedure::whereNotNull('legacy_id')->pluck('id', 'legacy_id')->toArray();
        $employees = \App\Models\Employee::whereNotNull('legacy_id')->pluck('id', 'legacy_id')->toArray();

        $this->withProgressBar($mappings, function ($row) use ($dryRun, &$stats, $procedures, $employees) {
            try {
                if ($row->deleted_at && $row->deleted_at != '0000-00-00 00:00:00') {
                    $stats['skip']++;
                    return;
                }

                if (!isset($procedures[$row->id_ref_tindakan]) || !isset($employees[$row->id_aktor])) {
                    $stats['orphan']++;
                    return;
                }

                $procedureId = $procedures[$row->id_ref_tindakan];
                $employeeId = $employees[$row->id_aktor];

                if (!$dryRun) {
                    $exists = \Illuminate\Support\Facades\DB::table('procedure_employee')
                                ->where('procedure_id', $procedureId)
                                ->where('employee_id', $employeeId)
                                ->exists();

                    if (!$exists) {
                        \Illuminate\Support\Facades\DB::table('procedure_employee')->insert([
                            'procedure_id' => $procedureId,
                            'employee_id' => $employeeId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $stats['create']++;
                    } else {
                        $stats['skip']++;
                    }
                } else {
                    $stats['create']++; // estimate
                }
            } catch (\Exception $e) {
                $stats['failed']++;
                if (!$dryRun) \Illuminate\Support\Facades\Log::error("Failed importing mapping id {$row->id}: " . $e->getMessage());
            }
        });

        $this->newLine();
        $this->info("Import completed.");
        $this->table(['Create', 'Skip (Exists/Deleted)', 'Orphan', 'Failed'], [[$stats['create'], $stats['skip'], $stats['orphan'], $stats['failed']]]);
    }
}
