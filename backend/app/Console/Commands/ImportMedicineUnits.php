<?php

namespace App\Console\Commands;

use App\Models\MasterData\MedicineUnit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportMedicineUnits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:medicine-units {--dry-run : Only read legacy data, do not write}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import medicine units from legacy database (ref_satuan_obat)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('Starting import of medicine units'.($dryRun ? ' (DRY RUN)' : ''));

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        DB::connection('legacy')->table('ref_satuan_obat')
            ->orderBy('id')
            ->chunk(100, function ($categories) use (&$inserted, &$updated, &$skipped, &$failed, $dryRun) {
                foreach ($categories as $category) {
                    try {
                        $name = trim($category->nama);

                        if (empty($name)) {
                            $this->warn("Skipping record with ID {$category->id} due to empty name.");
                            $skipped++;

                            continue;
                        }

                        $data = [
                            'name' => $name,
                            'description' => $category->deskripsi ?? null,
                            'is_active' => $category->status == '1' ? true : false,
                        ];

                        if (! $dryRun) {
                            $targetCategory = MedicineUnit::where('legacy_id', $category->id)->first();

                            if ($targetCategory) {
                                $targetCategory->update($data);
                                $updated++;
                            } else {
                                $data['legacy_id'] = $category->id;
                                MedicineUnit::create($data);
                                $inserted++;
                            }
                        } else {
                            $inserted++;
                        }
                    } catch (\Exception $e) {
                        $this->error("Failed to import record ID {$category->id}: ".$e->getMessage());
                        $failed++;
                    }
                }
            });

        $this->info('Import completed.');
        $this->table(
            ['Action', 'Count'],
            [
                ['Inserted/New', $inserted],
                ['Updated', $updated],
                ['Skipped', $skipped],
                ['Failed', $failed],
            ]
        );
    }
}
