<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MasterData\Icd9Cm;

class ImportLegacyIcd9CmsCommand extends Command
{
    protected $signature = 'legacy:import-icd9-cms {--dry-run : Perform a dry run without saving}';
    protected $description = 'Import ICD-9-CM from legacy database';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $this->info("Starting ICD-9-CM legacy import (Dry Run: " . ($isDryRun ? 'Yes' : 'No') . ")");

        DB::beginTransaction();
        try {
            $legacyData = DB::connection('legacy')->table('ref_icd_9')->get();
            $tindakanIcds = DB::connection('legacy')->table('ref_tarif_tindakan')
                                ->whereNotNull('icd9_code')->where('icd9_code', '!=', '')
                                ->distinct()->pluck('icd9_code')->toArray();

            $stats = [
                'Total Read' => $legacyData->count(),
                'Skipped Soft Deleted' => 0,
                'Would Create' => 0,
                'Would Update' => 0,
                'Duplicate Codes' => 0,
                'Orphan Codes Created' => 0,
            ];

            $seenCodes = [];

            // 1. Import from ref_icd_9
            foreach ($legacyData as $row) {
                if ($row->deleted_at && $row->deleted_at !== '0000-00-00 00:00:00') {
                    $stats['Skipped Soft Deleted']++;
                    continue;
                }

                if (in_array($row->kode, $seenCodes)) {
                    $stats['Duplicate Codes']++;
                }
                $seenCodes[] = $row->kode;

                $existing = Icd9Cm::where('legacy_id', $row->id)->orWhere('code', $row->kode)->first();
                $isNeedsReview = empty($row->nama) || trim($row->nama) === '';
                
                if ($existing) {
                    $stats['Would Update']++;
                    if (!$isDryRun) {
                        $existing->update([
                            'legacy_id' => $row->id,
                            'code' => $row->kode,
                            'description' => $row->nama,
                            'is_active' => $row->status == 1,
                            'needs_review' => $isNeedsReview,
                        ]);
                    }
                } else {
                    $stats['Would Create']++;
                    if (!$isDryRun) {
                        Icd9Cm::create([
                            'legacy_id' => $row->id,
                            'code' => $row->kode,
                            'description' => $row->nama,
                            'is_active' => $row->status == 1,
                            'needs_review' => $isNeedsReview,
                        ]);
                    }
                }
            }

            // 2. Import distinct codes from ref_tarif_tindakan that are not in ref_icd_9
            foreach ($tindakanIcds as $code) {
                if (!in_array($code, $seenCodes)) {
                    $seenCodes[] = $code;
                    $existing = Icd9Cm::where('code', $code)->first();
                    
                    if (!$existing) {
                        $stats['Orphan Codes Created']++;
                        if (!$isDryRun) {
                            Icd9Cm::create([
                                'legacy_id' => null,
                                'code' => $code,
                                'description' => null, // Empty description as per rules
                                'is_active' => true,
                                'needs_review' => true, // Marked as needs_review
                            ]);
                        }
                    }
                }
            }

            $this->table(['Metric', 'Count'], collect($stats)->map(fn($v, $k) => [$k, $v])->toArray());

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
