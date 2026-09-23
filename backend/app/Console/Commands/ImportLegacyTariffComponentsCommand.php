<?php

namespace App\Console\Commands;

use App\Models\TariffComponent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyTariffComponentsCommand extends Command
{
    protected $signature = 'legacy:import-tariff-components {--dry-run : Only show what would be imported without actually inserting data}';

    protected $description = 'Import tariff components from legacy database (ref_komponen)';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('Starting DRY RUN for Tariff Components import...');
        } else {
            $this->info('Starting ACTUAL IMPORT for Tariff Components...');
        }

        try {
            $legacyDb = DB::connection('legacy');
            $this->info('Connected to legacy database successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to connect to legacy database: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $records = $legacyDb->table('ref_komponen')->get();

        $stats = [
            'total_read' => $records->count(),
            'skipped_soft_deleted' => 0,
            'would_create' => 0,
            'would_update' => 0,
            'failed' => 0,
        ];

        if (!$isDryRun) {
            DB::beginTransaction();
        }

        $bar = $this->output->createProgressBar($records->count());
        $bar->start();

        foreach ($records as $record) {
            // Soft delete logic:
            // "0000-00-00", "0000-00-00 00:00:00", "", NULL means active.
            // Actual date means soft deleted.
            $isSoftDeleted = false;
            if (!empty($record->deleted_at)) {
                $deletedAt = trim($record->deleted_at);
                if ($deletedAt !== '0000-00-00' && $deletedAt !== '0000-00-00 00:00:00') {
                    $isSoftDeleted = true;
                }
            }

            if ($isSoftDeleted) {
                $stats['skipped_soft_deleted']++;
                $bar->advance();
                continue;
            }

            // Normalization
            $name = trim($record->nama);
            $description = trim((string)$record->deskripsi);
            if ($description === '' || $description === '-') {
                $description = null;
            }
            $isActive = $record->status == '1';
            
            $legacyId = $record->id;

            try {
                $existing = TariffComponent::where('legacy_id', $legacyId)->first();

                if ($existing) {
                    $stats['would_update']++;
                    if (!$isDryRun) {
                        $existing->update([
                            'name' => $name,
                            'description' => $description,
                            'is_active' => $isActive,
                        ]);
                    }
                } else {
                    $stats['would_create']++;
                    if (!$isDryRun) {
                        TariffComponent::create([
                            'legacy_id' => $legacyId,
                            'name' => $name,
                            'description' => $description,
                            'is_active' => $isActive,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $stats['failed']++;
                $this->error("\nFailed to process record ID {$legacyId}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if (!$isDryRun) {
            DB::commit();
            $this->info('Import completed successfully!');
        } else {
            $this->info('Dry run completed. No data was modified.');
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Read', $stats['total_read']],
                ['Skipped (Soft Deleted)', $stats['skipped_soft_deleted']],
                ['Valid for Import', $stats['total_read'] - $stats['skipped_soft_deleted']],
                ['Would Create', $stats['would_create']],
                ['Would Update', $stats['would_update']],
                ['Failed', $stats['failed']],
            ]
        );

        return Command::SUCCESS;
    }
}
