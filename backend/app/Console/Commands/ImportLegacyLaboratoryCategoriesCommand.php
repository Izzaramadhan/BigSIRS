<?php

namespace App\Console\Commands;

use App\Models\MasterData\LaboratoryCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyLaboratoryCategoriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:legacy-laboratory-categories {--dry-run : Only show what would be imported without actually saving to database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import laboratory categories from legacy ref_kategori_lab table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting legacy laboratory categories import...');
        
        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be saved to the target database.');
        }

        try {
            $legacyCategories = DB::connection('legacy')
                ->table('ref_kategori_lab')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->get();

            $total = $legacyCategories->count();
            $this->info("Found {$total} active laboratory categories in legacy database.");
            
            if ($total === 0) {
                $this->info('Nothing to import.');
                return Command::SUCCESS;
            }

            $stats = [
                'inserted' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0
            ];

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            foreach ($legacyCategories as $legacyCategory) {
                try {
                    $name = trim($legacyCategory->nama);
                    
                    if (empty($name)) {
                        $stats['skipped']++;
                        $bar->advance();
                        continue;
                    }

                    // For laboratory categories, name should be unique based on our migrations.
                    $existingByName = LaboratoryCategory::where('name', $name)->first();
                    
                    if ($existingByName && $existingByName->legacy_id !== $legacyCategory->id) {
                        $this->newLine();
                        $this->warn("Conflict: Name '{$name}' is already used by category ID {$existingByName->id} (Legacy ID {$existingByName->legacy_id}). Skipping legacy ID {$legacyCategory->id}.");
                        $stats['skipped']++;
                        $bar->advance();
                        continue;
                    }

                    $isActive = $legacyCategory->status === '1' ? true : false;
                    $description = !empty(trim($legacyCategory->deskripsi)) ? trim($legacyCategory->deskripsi) : null;

                    $existingCategory = LaboratoryCategory::where('legacy_id', $legacyCategory->id)->first();

                    if (!$isDryRun) {
                        LaboratoryCategory::updateOrCreate(
                            ['legacy_id' => $legacyCategory->id],
                            [
                                'name' => $name,
                                'description' => $description,
                                'type' => in_array($legacyCategory->tipe, ['lab klinik', 'lab gigi', 'lab mikrobakteri']) ? $legacyCategory->tipe : null,
                                'loinc_code' => $legacyCategory->loinc_code,
                                'loinc_url' => $legacyCategory->loinc_url,
                                'snomed_code' => $legacyCategory->snomed_code,
                                'snomed_url' => $legacyCategory->snomed_url,
                                'is_active' => $isActive,
                            ]
                        );
                    }

                    if ($existingCategory) {
                        $stats['updated']++;
                    } else {
                        $stats['inserted']++;
                    }

                } catch (\Exception $e) {
                    $stats['failed']++;
                    $this->newLine();
                    $this->error("Failed to import legacy ID {$legacyCategory->id}: " . $e->getMessage());
                }
                
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
            
            $this->info('Import Summary:');
            $this->line("  Processed : {$total}");
            $this->line("  Inserted  : {$stats['inserted']}");
            $this->line("  Updated   : {$stats['updated']}");
            $this->line("  Skipped   : {$stats['skipped']}");
            $this->line("  Failed    : {$stats['failed']}");
            
            if ($isDryRun) {
                $this->warn('DRY RUN COMPLETED - Database was not modified.');
            } else {
                $this->info('Import completed successfully!');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to connect to legacy database or read data.');
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
