<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MasterData\ReportGroup;

class ImportLegacyReportGroupsCommand extends Command
{
    protected $signature = 'legacy:import-report-groups {--dry-run : Perform a dry run without saving}';
    protected $description = 'Import Report Groups from legacy database';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $this->info("Starting Report Group legacy import (Dry Run: " . ($isDryRun ? 'Yes' : 'No') . ")");

        DB::beginTransaction();
        try {
            $legacyData = DB::connection('legacy')->table('ref_report_group')->get();

            $stats = [
                'Total Read' => $legacyData->count(),
                'Skipped Soft Deleted' => 0,
                'Would Create' => 0,
                'Would Update' => 0,
            ];

            foreach ($legacyData as $row) {
                if ($row->deleted_at && $row->deleted_at !== '0000-00-00 00:00:00') {
                    $stats['Skipped Soft Deleted']++;
                    continue;
                }

                $existing = ReportGroup::where('legacy_id', $row->id)->orWhere('name', $row->nama)->first();
                if ($existing) {
                    $stats['Would Update']++;
                    if (!$isDryRun) {
                        $existing->update([
                            'legacy_id' => $row->id,
                            'name' => $row->nama,
                            'description' => $row->deskripsi,
                            'type' => $row->tipe ?? $row->type, // handling possible column name difference
                            'is_active' => $row->status == 1,
                        ]);
                    }
                } else {
                    $stats['Would Create']++;
                    if (!$isDryRun) {
                        ReportGroup::create([
                            'legacy_id' => $row->id,
                            'name' => $row->nama,
                            'description' => $row->deskripsi,
                            'type' => $row->tipe ?? $row->type,
                            'is_active' => $row->status == 1,
                        ]);
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
