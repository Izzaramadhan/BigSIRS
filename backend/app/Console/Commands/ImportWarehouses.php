<?php

namespace App\Console\Commands;

use App\Models\MasterData\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportWarehouses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:legacy-warehouses {--dry-run : Only show what would be imported}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import warehouses (gudang) from legacy database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting import of legacy warehouses..');

        $isDryRun = $this->option('dry-run');

        try {
            $legacyWarehouses = DB::connection('legacy')->table('ref_gudang')->get();
        } catch (\Exception $e) {
            $this->error('Failed to connect to legacy database or read table: '.$e->getMessage());

            return 1;
        }

        $this->info('Found '.$legacyWarehouses->count().' warehouses in legacy database.');

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($legacyWarehouses as $legacy) {
            $name = trim($legacy->nama);
            if (empty($name)) {
                $this->warn("Skipping legacy ID {$legacy->id}: Name is empty");
                $skipped++;

                continue;
            }

            // Normalisasi code
            $code = trim($legacy->kode);
            if (empty($code) || $code === '-') {
                $code = null;
            }

            $description = trim($legacy->deskripsi);

            // Perlakuan soft deletes: jika deleted_at ada atau status = 0, anggap dihapus
            $deletedAt = $legacy->deleted_at;
            if ($legacy->status == '0' && ! $deletedAt) {
                $deletedAt = now();
            }

            if ($isDryRun) {
                $inserted++;

                continue;
            }

            try {
                $warehouse = Warehouse::withTrashed()->updateOrCreate(
                    ['legacy_id' => $legacy->id],
                    [
                        'code' => $code,
                        'name' => $name,
                        'description' => $description ?: null,
                        'deleted_at' => $deletedAt,
                    ]
                );

                if ($warehouse->wasRecentlyCreated) {
                    $inserted++;
                } else {
                    $updated++;
                }
            } catch (\Exception $e) {
                $this->error("Failed to process legacy ID {$legacy->id}: ".$e->getMessage());
                $failed++;
            }
        }

        if (! $isDryRun) {
            $this->info('Import completed!');
            $this->table(
                ['Inserted', 'Updated', 'Skipped', 'Failed'],
                [[$inserted, $updated, $skipped, $failed]]
            );

            // Backfill warehouse_id in polyclinics table
            $this->info('Backfilling warehouse_id in polyclinics table...');
            DB::statement('
                UPDATE polyclinics p
                JOIN warehouses w ON p.legacy_default_warehouse_id = w.legacy_id
                SET p.warehouse_id = w.id
            ');
            $this->info('Backfill completed.');

        } else {
            $this->info("DRY RUN: Would insert/update $inserted records.");
        }

        return 0;
    }
}
