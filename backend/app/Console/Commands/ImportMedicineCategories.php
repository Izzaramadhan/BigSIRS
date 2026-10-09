<?php

namespace App\Console\Commands;

use App\Models\MasterData\MedicineCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportMedicineCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:medicine-categories {--dry-run : Only read legacy data, do not write}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import medicine categories from legacy database (ref_kategori_obat)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('Starting import of medicine categories' . ($dryRun ? ' (DRY RUN)' : ''));

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        DB::connection('legacy')->table('ref_kategori_obat')
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
                            'name' => Str::upper($name),
                            'description' => $category->deskripsi ?? null,
                            'is_active' => $category->status == '1' ? true : false,
                        ];

                        if (!$dryRun) {
                            $targetCategory = MedicineCategory::where('legacy_id', $category->id)->first();

                            if ($targetCategory) {
                                $targetCategory->update($data);
                                $updated++;
                            } else {
                                $data['legacy_id'] = $category->id;
                                MedicineCategory::create($data);
                                $inserted++;
                            }
                        } else {
                            $inserted++;
                        }
                    } catch (\Exception $e) {
                        $this->error("Failed to import record ID {$category->id}: " . $e->getMessage());
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
