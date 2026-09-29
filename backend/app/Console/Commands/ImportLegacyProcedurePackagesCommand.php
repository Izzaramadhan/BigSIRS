<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MasterData\ProcedurePackage;
use App\Models\MasterData\MedicalProcedureTariff;

class ImportLegacyProcedurePackagesCommand extends Command
{
    protected $signature = 'legacy:import-procedure-packages {--dry-run : Perform a dry run without saving to the database}';
    protected $description = 'Import procedure packages from legacy database';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info("Starting legacy package import (Dry Run: " . ($isDryRun ? 'Yes' : 'No') . ")");

        DB::beginTransaction();
        try {
            $stats = [
                'Total Packages Read' => 0,
                'Total Items Read' => 0,
                'Skipped Soft Deleted' => 0,
                'Packages Without Items' => 0,
                'Missing Procedure Mapping' => 0,
                'Duplicate Procedure Lines' => 0,
                'Header Total Mismatches' => 0, // Legacy has no header total, but we calculate it anyway
                'Would Create' => 0,
                'Would Update' => 0,
                'Failed' => 0,
                'Needs Review' => 0,
            ];

            // Build legacy maps
            $tariffMap = MedicalProcedureTariff::with('medicalProcedure')->get()->keyBy('legacy_id');

            // Fetch Headers
            $legacyPackages = DB::connection('legacy')->table('ref_paket_tindakan')
                ->orderBy('id')
                ->get();

            $stats['Total Packages Read'] = $legacyPackages->count();

            // Fetch Items
            $legacyItemsRaw = DB::connection('legacy')->table('map_paket_tindakan')
                ->orderBy('id')
                ->get();

            $stats['Total Items Read'] = $legacyItemsRaw->count();

            $legacyItems = [];
            foreach ($legacyItemsRaw as $item) {
                if ($item->deleted_at && $item->deleted_at !== '0000-00-00 00:00:00') {
                    $stats['Skipped Soft Deleted']++;
                    continue;
                }
                $legacyItems[$item->id_ref_paket_tindakan][] = $item;
            }

            foreach ($legacyPackages as $row) {
                if ($row->deleted_at && $row->deleted_at !== '0000-00-00 00:00:00') {
                    $stats['Skipped Soft Deleted']++;
                    continue;
                }

                $items = $legacyItems[$row->id] ?? [];
                
                if (empty($items)) {
                    $stats['Packages Without Items']++;
                }

                $package = ProcedurePackage::where('legacy_id', $row->id)->first();
                if ($package) {
                    $stats['Would Update']++;
                    if (!$isDryRun) {
                        $package->update([
                            'name' => $row->nama,
                            'is_active' => $row->status == 1,
                        ]);
                    }
                } else {
                    $stats['Would Create']++;
                    if (!$isDryRun) {
                        $package = ProcedurePackage::create([
                            'legacy_id' => $row->id,
                            'name' => $row->nama,
                            'is_active' => $row->status == 1,
                            'total_amount' => 0,
                        ]);
                    } else {
                        $package = new ProcedurePackage(['id' => $row->id]);
                    }
                }

                $totalAmount = 0;
                $sortOrder = 1;
                $seenTariffs = [];

                foreach ($items as $legacyItem) {
                    if (isset($seenTariffs[$legacyItem->id_ref_tarif_tindakan])) {
                        $stats['Duplicate Procedure Lines']++;
                    }
                    $seenTariffs[$legacyItem->id_ref_tarif_tindakan] = true;

                    $mappedTariff = $tariffMap[$legacyItem->id_ref_tarif_tindakan] ?? null;

                    if (!$mappedTariff || !$mappedTariff->medicalProcedure) {
                        $stats['Missing Procedure Mapping']++;
                        continue;
                    }

                    $totalAmount += $mappedTariff->total_amount;

                    if (!$isDryRun && $package->exists) {
                        $package->items()->updateOrCreate(
                            ['legacy_id' => $legacyItem->id],
                            [
                                'medical_procedure_id' => $mappedTariff->medical_procedure_id,
                                'medical_procedure_tariff_id' => $mappedTariff->id,
                                'quantity' => 1, // Legacy doesn't have quantity
                                'unit_amount' => $mappedTariff->total_amount,
                                'subtotal_amount' => $mappedTariff->total_amount,
                                'sort_order' => $sortOrder++,
                            ]
                        );
                    }
                }

                if (!$isDryRun && $package->exists) {
                    $package->update(['total_amount' => $totalAmount]);
                }
            }

            $this->table(['Metric', 'Count'], collect($stats)->map(function ($value, $key) {
                return [$key, $value];
            })->toArray());

            if ($isDryRun) {
                DB::rollBack();
                $this->info("Dry run completed. Transactions rolled back.");
            } else {
                DB::commit();
                $this->info("Import completed successfully.");
            }
            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Import failed: " . $e->getMessage());
            return 1;
        }
    }
}
