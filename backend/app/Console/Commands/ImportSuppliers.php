<?php

namespace App\Console\Commands;

use App\Models\MasterData\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSuppliers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:legacy-suppliers {--dry-run : Only show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import suppliers from legacy database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('DRY RUN: No data will be modified.');
        }

        $this->info('Starting import of legacy suppliers...');

        $legacySuppliersQuery = DB::connection('legacy')->table('ref_supplier');
        $totalLegacy = $legacySuppliersQuery->count();
        $this->info("Found {$totalLegacy} suppliers in legacy database.");

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        $legacySuppliersQuery->orderBy('id')->chunk(500, function ($suppliers) use (
            $isDryRun, &$inserted, &$updated, &$skipped, &$failed
        ) {
            foreach ($suppliers as $legacy) {
                try {
                    $name = trim($legacy->nama);

                    if (empty($name)) {
                        $this->warn("Skipping legacy ID {$legacy->id}: Name is empty");
                        $skipped++;

                        continue;
                    }

                    // Normalize phone
                    $phone = trim($legacy->telp);
                    if (empty($phone) || $phone === '-') {
                        $phone = null;
                    }

                    // Normalize address
                    $address = trim($legacy->alamat);
                    if (empty($address) || $address === '-') {
                        $address = null;
                    }

                    $isActive = (int) $legacy->status === 1;

                    if (! $isDryRun) {
                        $existing = Supplier::where('legacy_id', $legacy->id)->first();

                        if ($existing) {
                            $existing->update([
                                'name' => $name,
                                'phone' => $phone,
                                'address' => $address,
                                'is_active' => $isActive,
                            ]);

                            if ($legacy->deleted_at && ! $existing->trashed()) {
                                $existing->delete();
                            } elseif (! $legacy->deleted_at && $existing->trashed()) {
                                $existing->restore();
                            }

                            $updated++;
                        } else {
                            $supplier = Supplier::create([
                                'legacy_id' => $legacy->id,
                                'name' => $name,
                                'phone' => $phone,
                                'address' => $address,
                                'is_active' => $isActive,
                            ]);

                            if ($legacy->deleted_at) {
                                $supplier->delete();
                            }

                            $inserted++;
                        }
                    } else {
                        $existing = Supplier::where('legacy_id', $legacy->id)->first();
                        if ($existing) {
                            $updated++;
                        } else {
                            $inserted++;
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to process legacy ID {$legacy->id}: {$e->getMessage()}");
                    $failed++;
                }
            }
        });

        $this->info('Import completed!');
        $this->table(
            ['Inserted', 'Updated', 'Skipped', 'Failed'],
            [[$inserted, $updated, $skipped, $failed]]
        );

        return 0;
    }
}
