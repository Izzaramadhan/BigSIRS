<?php

namespace App\Console\Commands;

use App\Models\MasterData\RadiologyItemGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportRadiologyItemGroupsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:radiology-item-groups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import radiology item groups from legacy database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting import of Radiology Item Groups...');

        $legacyGroups = DB::connection('legacy')->table('ref_kelompok_item_rad')->get();

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($legacyGroups as $legacy) {
            $name = trim($legacy->nama);

            if (empty($name)) {
                $this->warn("Skipping ID {$legacy->id} due to empty name.");
                $skipped++;
                continue;
            }

            $isActive = (string) $legacy->status === '1';

            $existing = RadiologyItemGroup::where('legacy_id', $legacy->id)->first();

            if ($existing) {
                $existing->update([
                    'name' => $name,
                    'description' => trim($legacy->deskripsi),
                    'is_active' => $isActive,
                ]);
                $updated++;
            } else {
                RadiologyItemGroup::create([
                    'legacy_id' => $legacy->id,
                    'name' => $name,
                    'description' => trim($legacy->deskripsi),
                    'is_active' => $isActive,
                ]);
                $inserted++;
            }
        }

        $this->info("Import completed! Inserted: {$inserted}, Updated: {$updated}, Skipped: {$skipped}");
    }
}
