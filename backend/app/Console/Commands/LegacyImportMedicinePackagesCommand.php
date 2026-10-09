<?php

namespace App\Console\Commands;

use App\Models\MasterData\Medicine;
use App\Models\MasterData\MedicinePackage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LegacyImportMedicinePackagesCommand extends Command
{
    protected $signature = 'legacy:import-medicine-packages {--dry-run : Only show what would be imported}';
    protected $description = 'Import medicine packages from legacy database (ref_paket_obat and map_paket_obat)';

    public function handle()
    {
        $this->info('Starting legacy medicine packages import...');
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be saved to the database');
        }

        $legacyDb = DB::connection('legacy');
        
        $legacyHeaders = $legacyDb->table('ref_paket_obat')->get();
        $this->info("Found {$legacyHeaders->count()} headers in legacy database.");

        $stats = [
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'orphan_details' => 0,
        ];

        foreach ($legacyHeaders as $legacyHeader) {
            try {
                DB::beginTransaction();

                if (empty($legacyHeader->nama)) {
                    $this->warn("Skipping package ID {$legacyHeader->id} because name is empty.");
                    $stats['skipped']++;
                    DB::rollBack();
                    continue;
                }

                $packageData = [
                    'legacy_id' => $legacyHeader->id,
                    'name' => $legacyHeader->nama,
                    'price' => $legacyHeader->harga ?: 0,
                    'quantity' => $legacyHeader->jumlah ?: 0,
                    'description' => $legacyHeader->deskripsi,
                    'is_active' => $legacyHeader->status === '1',
                ];

                if (!$isDryRun) {
                    $package = MedicinePackage::updateOrCreate(
                        ['legacy_id' => $legacyHeader->id],
                        $packageData
                    );

                    if ($package->wasRecentlyCreated) {
                        $stats['inserted']++;
                    } else {
                        $stats['updated']++;
                    }
                    
                    if ($legacyHeader->deleted_at) {
                        $package->delete();
                    }
                    
                    // Import details
                    if (!$package->trashed()) {
                        $legacyDetails = $legacyDb->table('map_paket_obat')
                            ->where('id_ref_paket_obat', $legacyHeader->id)
                            ->whereNull('deleted_at')
                            ->get();

                        // Sync approach: clear old items and insert new ones
                        $package->items()->delete();
                        
                        foreach ($legacyDetails as $legacyDetail) {
                            if (!$legacyDetail->id_ref_obat) continue;

                            // Map legacy medicine ID to target medicine ID
                            $targetMedicine = Medicine::where('legacy_id', $legacyDetail->id_ref_obat)->first();
                            if (!$targetMedicine) {
                                $this->warn("Skipping detail for package {$legacyHeader->id}: legacy medicine ID {$legacyDetail->id_ref_obat} not found in target.");
                                $stats['orphan_details']++;
                                continue;
                            }
                            
                            $qty = $legacyDetail->jumlah ?: 1; // Fallback to 1 if null
                            $subtotal = $legacyDetail->sub_total ?: 0;
                            // Re-calculate unit price if subtotal and qty are present and non-zero
                            $unitPrice = ($qty > 0) ? $subtotal / $qty : 0;
                            
                            if ($unitPrice == 0 && $targetMedicine->selling_price_per_unit) {
                                $unitPrice = $targetMedicine->selling_price_per_unit;
                            }
                            
                            $subtotal = $qty * $unitPrice;
                            
                            $package->items()->create([
                                'legacy_id' => $legacyDetail->id,
                                'medicine_id' => $targetMedicine->id,
                                'quantity' => $qty,
                                'unit_price' => $unitPrice,
                                'subtotal' => $subtotal,
                            ]);
                        }
                    }
                } else {
                    $stats['inserted']++;
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Failed to import package ID {$legacyHeader->id}: " . $e->getMessage());
                $stats['failed']++;
            }
        }

        $this->info("Import completed!");
        $this->table(
            ['Inserted', 'Updated', 'Skipped', 'Failed', 'Orphan Details'],
            [[$stats['inserted'], $stats['updated'], $stats['skipped'], $stats['failed'], $stats['orphan_details']]]
        );
    }
}
