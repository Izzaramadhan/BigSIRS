<?php

namespace App\Console\Commands;

use App\Models\ProcedureCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyProcedureCategoriesCommand extends Command
{
    protected $signature = 'legacy:import-procedure-categories {--dry-run : Run without making actual database changes}';
    protected $description = 'Import procedure categories from legacy database';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info("Starting legacy Procedure Categories import..");
        if ($isDryRun) {
            $this->warn("RUNNING IN DRY-RUN MODE (No data will be saved)");
        }

        try {
            $legacyCategories = DB::connection('legacy')->table('ref_kategori_tindakan')->get();
        } catch (\Exception $e) {
            $this->error("Failed to connect or read from legacy database: " . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info("Read {$legacyCategories->count()} records from ref_kategori_tindakan.");

        $stats = [
            'total_read' => $legacyCategories->count(),
            'skipped_soft_deleted' => 0,
            'conflicts' => 0,
            'valid_for_import' => 0,
            'would_create' => 0,
            'would_update' => 0,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
            'status_active' => 0,
            'status_inactive' => 0,
        ];

        DB::beginTransaction();

        try {
            foreach ($legacyCategories as $legacy) {
                // Soft delete check
                $deletedAt = $legacy->deleted_at;
                $isSoftDeleted = false;
                
                if (!is_null($deletedAt) && $deletedAt !== '' && $deletedAt !== '0000-00-00' && $deletedAt !== '0000-00-00 00:00:00') {
                    $isSoftDeleted = true;
                }

                if ($isSoftDeleted) {
                    $stats['skipped_soft_deleted']++;
                    continue;
                }

                $stats['valid_for_import']++;

                $isActive = (int) $legacy->status === 1;
                
                if ($isActive) {
                    $stats['status_active']++;
                } else {
                    $stats['status_inactive']++;
                }

                $name = is_string($legacy->nama) ? preg_replace('/\s+/', ' ', trim($legacy->nama)) : $legacy->nama;
                $description = is_string($legacy->deskripsi) && trim($legacy->deskripsi) !== '' 
                    ? preg_replace('/\s+/', ' ', trim($legacy->deskripsi)) 
                    : null;

                $data = [
                    'name' => $name,
                    'description' => $description,
                    'is_active' => $isActive,
                ];

                $existing = ProcedureCategory::where('legacy_id', $legacy->id)->first();

                if ($existing) {
                    $stats['would_update']++;
                    if (!$isDryRun) {
                        $existing->update($data);
                        $stats['updated']++;
                    }
                } else {
                    $stats['would_create']++;
                    if (!$isDryRun) {
                        ProcedureCategory::create(array_merge(['legacy_id' => $legacy->id], $data));
                        $stats['created']++;
                    }
                }
            }

            if ($isDryRun) {
                DB::rollBack();
            } else {
                DB::commit();
                $this->info("Import completed successfully.");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Import failed: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Print Report
        $this->line("");
        $this->info($isDryRun ? "[DRY-RUN] Import Report" : "Final Import Report");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Read', $stats['total_read']],
                ['Skipped (Soft Deleted)', $stats['skipped_soft_deleted']],
                ['Conflicts (Need Action)', $stats['conflicts']],
                ['Valid for Import', $stats['valid_for_import']],
                ['Would Create', $stats['would_create']],
                ['Would Update', $stats['would_update']],
                ['Failed', $stats['failed']],
                ['Status Active (is_active=true)', $stats['status_active']],
                ['Status Inactive (is_active=false)', $stats['status_inactive']],
            ]
        );

        return Command::SUCCESS;
    }
}
