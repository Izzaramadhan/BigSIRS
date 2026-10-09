<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MasterData\FollowUpHandling;

class LegacyImportFollowUpHandlingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:import-follow-up-handlings {--dry-run : Only show what would be imported without actually inserting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import follow up handlings from legacy database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting import of follow up handlings from legacy database...');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in DRY-RUN mode. No data will be saved.');
        }

        try {
            $legacyHandlings = DB::connection('legacy')->table('ref_penanganan_lanjutan')->get();
            $this->info('Found ' . $legacyHandlings->count() . ' records in legacy database.');

            $inserted = 0;
            $updated = 0;
            $skipped = 0;
            $failed = 0;

            foreach ($legacyHandlings as $legacy) {
                if (empty(trim($legacy->nama))) {
                    $skipped++;
                    continue;
                }

                $data = [
                    'legacy_id' => $legacy->id,
                    'code' => $legacy->kode ?: null,
                    'name' => trim($legacy->nama),
                    'is_active' => (bool) $legacy->status,
                ];

                if (!$dryRun) {
                    try {
                        $existing = FollowUpHandling::where('legacy_id', $legacy->id)->first();
                        
                        if ($existing) {
                            $existing->update($data);
                            $updated++;
                        } else {
                            FollowUpHandling::create($data);
                            $inserted++;
                        }
                    } catch (\Exception $e) {
                        $this->error("Failed to import record ID {$legacy->id}: " . $e->getMessage());
                        $failed++;
                    }
                } else {
                    $inserted++; // Mock insertion count
                }
            }

            $this->info('Import completed.');
            $this->info("Total legacy records: " . $legacyHandlings->count());
            $this->info("Inserted: {$inserted}");
            $this->info("Updated: {$updated}");
            $this->info("Skipped (empty name): {$skipped}");
            $this->info("Failed: {$failed}");

        } catch (\Exception $e) {
            $this->error('Failed to read from legacy database: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
