<?php

namespace App\Console\Commands;

use App\Models\MasterData\Warehouse;
use App\Models\Polyclinic;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('legacy:link-polyclinic-warehouses {--dry-run : Only show what would be updated without making changes}')]
#[Description('Links imported polyclinics to their target warehouses based on legacy_default_warehouse_id.')]
class LinkPolyclinicWarehousesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in DRY-RUN mode. No data will be modified.');
        }

        $polyclinics = Polyclinic::whereNotNull('legacy_default_warehouse_id')
            ->whereNull('warehouse_id')
            ->get();

        if ($polyclinics->isEmpty()) {
            $this->info('No polyclinics need linking.');
            return;
        }

        $legacyIds = $polyclinics->pluck('legacy_default_warehouse_id')->unique()->toArray();
        $warehouses = Warehouse::whereIn('legacy_id', $legacyIds)->get()->keyBy('legacy_id');

        $matched = 0;
        $unmatched = 0;
        $skipped = 0;
        $updated = 0;

        DB::beginTransaction();

        try {
            foreach ($polyclinics as $polyclinic) {
                if (isset($warehouses[$polyclinic->legacy_default_warehouse_id])) {
                    $matched++;
                    $warehouse = $warehouses[$polyclinic->legacy_default_warehouse_id];

                    if (! $dryRun) {
                        $polyclinic->warehouse_id = $warehouse->id;
                        $polyclinic->save();
                        $updated++;
                    }
                } else {
                    $unmatched++;
                }
            }

            if (! $dryRun) {
                DB::commit();
            } else {
                DB::rollBack();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('An error occurred: ' . $e->getMessage());
            return;
        }

        $this->info("Reconciliation complete.");
        $this->info("Total analyzed: " . $polyclinics->count());
        $this->info("Matched: {$matched}");
        $this->info("Unmatched (warehouse not found): {$unmatched}");
        $this->info("Updated: {$updated}");
    }
}
