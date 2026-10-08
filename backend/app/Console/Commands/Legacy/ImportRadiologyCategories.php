<?php

namespace App\Console\Commands\Legacy;

use App\Models\MasterData\RadiologyCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportRadiologyCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:import-radiology-categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import radiology categories from legacy database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting radiology categories import...');

        $legacyDb = DB::connection('legacy');
        $legacyCategories = $legacyDb->table('ref_kategori_rad')->get();

        $total = $legacyCategories->count();
        $this->info("Found {$total} categories in legacy database.");

        $stats = [
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        $bar = $this->output->createProgressBar($total);

        foreach ($legacyCategories as $legacy) {
            if (empty(trim($legacy->nama))) {
                $stats['skipped']++;
                $bar->advance();
                continue;
            }

            $category = RadiologyCategory::updateOrCreate(
                ['legacy_id' => $legacy->id],
                [
                    'name' => trim($legacy->nama),
                    'description' => $legacy->deskripsi ? trim($legacy->deskripsi) : null,
                    'is_active' => (bool) $legacy->status,
                    'loinc_code' => $legacy->loinc_code ? trim($legacy->loinc_code) : null,
                    'loinc_url' => $legacy->loinc_url ? trim($legacy->loinc_url) : null,
                    'snomed_code' => $legacy->snomed_code ? trim($legacy->snomed_code) : null,
                    'snomed_url' => $legacy->snomed_url ? trim($legacy->snomed_url) : null,
                    'created_at' => $legacy->created_at ?? now(),
                    'updated_at' => $legacy->updated_at ?? now(),
                    'deleted_at' => $legacy->deleted_at,
                ]
            );

            if ($category->wasRecentlyCreated) {
                $stats['inserted']++;
            } else {
                $stats['updated']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Import completed:");
        $this->line("- Total found: {$total}");
        $this->line("- Inserted: {$stats['inserted']}");
        $this->line("- Updated: {$stats['updated']}");
        $this->line("- Skipped: {$stats['skipped']}");
    }
}
