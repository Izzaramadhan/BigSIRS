<?php

namespace App\Console\Commands;

use App\Models\MasterData\RadiologyItem;
use App\Models\MasterData\RadiologyItemGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyRadiologyItemsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:import-radiology-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import radiology items from legacy database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting import of radiology items...');

        $legacyItems = DB::connection('legacy')->table('ref_item_rad')->get();

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $missingGroup = 0;

        foreach ($legacyItems as $legacyItem) {
            $name = trim($legacyItem->nama);
            if (empty($name)) {
                $skipped++;

                continue;
            }

            $group = null;
            if ($legacyItem->id_kelompok_item_rad) {
                $group = RadiologyItemGroup::where('legacy_id', $legacyItem->id_kelompok_item_rad)->first();
                if (! $group) {
                    $missingGroup++;
                    $this->warn("Missing group ID {$legacyItem->id_kelompok_item_rad} for item {$legacyItem->id}");
                }
            }

            $data = [
                'name' => $name,
                'radiology_item_group_id' => $group ? $group->id : null,
                'is_active' => (bool) $legacyItem->status,
            ];

            if ($legacyItem->deleted_at && $legacyItem->deleted_at !== '0000-00-00 00:00:00') {
                $data['deleted_at'] = $legacyItem->deleted_at;
            } else {
                $data['deleted_at'] = null;
            }

            $existing = RadiologyItem::where('legacy_id', $legacyItem->id)->first();

            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                $data['legacy_id'] = $legacyItem->id;
                RadiologyItem::create($data);
                $inserted++;
            }
        }

        $this->info('Import completed!');
        $this->info("Total legacy items: {$legacyItems->count()}");
        $this->info("Inserted: {$inserted}");
        $this->info("Updated: {$updated}");
        $this->info("Skipped (empty name): {$skipped}");
        $this->info("Missing group: {$missingGroup}");
    }
}
