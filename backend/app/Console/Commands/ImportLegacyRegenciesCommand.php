<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Regency;
use App\Models\Province;
use Illuminate\Support\Facades\Log;

#[Signature('legacy:import-regencies {--dry-run : Only show what would be imported}')]
#[Description('Import regencies from legacy database')]
class ImportLegacyRegenciesCommand extends Command
{
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->info("Running in DRY-RUN mode. No data will be written.");
        }

        $legacyDb = DB::connection('legacy');
        
        $this->info("Fetching legacy Regencies...");
        
        $data = $legacyDb->select("SELECT id, id_provinsi, nama as name, updated_at, deleted_at, status FROM ref_kabupaten");
        
        $stats = [
            'read' => count($data),
            'skipped' => 0,
            'conflicts' => 0,
            'create' => 0, 
            'update' => 0, 
            'failed' => 0
        ];

        $provinceMap = Province::pluck('id', 'legacy_id')->toArray();

        // Check test DB override logic
        if (config('database.default') === 'sqlite' && config('database.connections.legacy.database') !== 'simrs_legacy_test') {
            $this->error("Legacy connection must be simrs_legacy_test during testing.");
            return 1;
        }

        if (!$dryRun) {
            DB::beginTransaction();
        }

        $missingProvinces = [];

        $this->withProgressBar($data, function ($row) use ($dryRun, &$stats, $provinceMap, &$missingProvinces) {
            try {
                if (!isset($provinceMap[$row->id_provinsi])) {
                    $stats['skipped']++;
                    $missingProvinces[$row->id_provinsi] = true;
                    return;
                }

                $isActive = in_array($row->status, [1, '1'], true);
                
                $notDeletedValues = [null, '', '0000-00-00', '0000-00-00 00:00:00'];
                $isDeleted = !in_array($row->deleted_at, $notDeletedValues, true);

                if ($isDeleted) {
                    $stats['skipped']++;
                    return;
                }

                $mapped = [
                    'code' => trim($row->id),
                    'name' => trim($row->name),
                    'province_id' => $provinceMap[$row->id_provinsi],
                    'is_active' => $isActive,
                ];

                $existingByLegacyId = Regency::where('legacy_id', $row->id)->first();

                if ($existingByLegacyId) {
                    // Update
                    if (!$dryRun) {
                        $existingByLegacyId->update($mapped);
                        $stats['update']++;
                    } else {
                        $stats['update']++;
                    }
                } else {
                    // Check duplicate code
                    $existingByCode = Regency::where('code', $row->id)->first();
                    if ($existingByCode) {
                        $stats['conflicts']++;
                        return;
                    }

                    if (!$dryRun) {
                        $mapped['legacy_id'] = $row->id;
                        Regency::create($mapped);
                        $stats['create']++;
                    } else {
                        $stats['create']++;
                    }
                }
            } catch (\Exception $e) {
                $stats['failed']++;
                if (!$dryRun) Log::error("Failed importing Regency legacy_id {$row->id}: " . $e->getMessage());
            }
        });

        if (!$dryRun) {
            DB::commit();
        }
        
        $this->newLine();

        if (count($missingProvinces) > 0) {
            $this->warn("\nMissing parent provinces (legacy IDs): " . implode(', ', array_keys($missingProvinces)));
            $this->warn("Please import provinces first. Regencies with missing provinces were skipped.");
        }

        $this->newLine();
        $this->table(
            ['Total Read', 'Valid/Processed', 'Skipped', 'Conflicts', 'Would Create / Created', 'Would Update / Updated', 'Failed'], 
            [[
                $stats['read'],
                $stats['create'] + $stats['update'],
                $stats['skipped'],
                $stats['conflicts'],
                $stats['create'],
                $stats['update'],
                $stats['failed']
            ]]
        );
    }
}
