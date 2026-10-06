<?php

namespace App\Console\Commands;

use App\Models\District;
use App\Models\Village;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportLegacyVillagesCommand extends Command
{
    protected $signature = 'legacy:import-villages {--dry-run : Only show what would be done without saving}';
    protected $description = 'Import villages from legacy ref_kelurahan table';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $legacyDb = config('database.default') === 'sqlite' 
            ? 'simrs_legacy_test' 
            : 'simrs_legacy_restored';

        if (config('database.default') === 'sqlite' && config('database.connections.legacy.database') !== 'simrs_legacy_test') {
            $this->error('Safety guard: Test environment must use simrs_legacy_test');
            return 1;
        }

        $this->info("Reading from: {$legacyDb}");
        $this->info($isDryRun ? "MODE: DRY-RUN" : "MODE: ACTUAL IMPORT");

        $totalRead = DB::connection('legacy')->table('ref_kelurahan')->count();
        $this->info("Total Read: " . $totalRead);

        $legacyVillages = DB::connection('legacy')->table('ref_kelurahan')->cursor();

        $districtMap = District::pluck('id', 'legacy_id')->toArray();
        $existingVillages = Village::pluck('id', 'legacy_id')->toArray();

        $stats = [
            'would_create' => 0,
            'would_update' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'missing_parent' => 0,
            'conflicts' => 0,
            'failed' => 0,
        ];

        $missingParents = [];

        if (!$isDryRun) {
            DB::beginTransaction();
        }

        try {
            foreach ($legacyVillages as $row) {
                $legacyId = trim((string)$row->id);
                $legacyDistrictId = trim((string)$row->id_kecamatan);
                
                $isDeleted = !in_array($row->deleted_at, [null, '', '0000-00-00', '0000-00-00 00:00:00', '2018-05-24 11:43:23'], true);
                if ($isDeleted) {
                    $stats['skipped']++;
                    continue;
                }

                if (!isset($districtMap[$legacyDistrictId])) {
                    $stats['missing_parent']++;
                    if (!in_array($legacyDistrictId, $missingParents)) {
                        $missingParents[] = $legacyDistrictId;
                    }
                    continue;
                }

                $districtId = $districtMap[$legacyDistrictId];
                $name = $row->nama ? Str::squish(trim($row->nama)) : '-';
                $code = trim((string)$row->kode_kelurahan); 
                $isActive = in_array($row->status, [1, '1'], true);

                $payload = [
                    'district_id' => $districtId,
                    'code' => $code ?: null,
                    'name' => $name,
                    'is_active' => $isActive,
                ];

                if (isset($existingVillages[$legacyId])) {
                    $stats['would_update']++;
                    if (!$isDryRun) {
                        Village::where('legacy_id', $legacyId)->update($payload);
                        $stats['updated']++;
                    }
                } else {
                    $stats['would_create']++;
                    if (!$isDryRun) {
                        $payload['legacy_id'] = $legacyId;
                        Village::create($payload);
                        $stats['created']++;
                    }
                }
            }

            if (!$isDryRun) {
                DB::commit();
            }
        } catch (\Exception $e) {
            if (!$isDryRun) {
                DB::rollBack();
            }
            $stats['failed']++;
            $this->error("Error: " . $e->getMessage());
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Read', $legacyVillages->count()],
                ['Would Create', $stats['would_create']],
                ['Would Update', $stats['would_update']],
                ['Created', $stats['created']],
                ['Updated', $stats['updated']],
                ['Skipped', $stats['skipped']],
                ['Missing Parent', $stats['missing_parent']],
                ['Conflicts', $stats['conflicts']],
                ['Failed', $stats['failed']],
            ]
        );

        if (count($missingParents) > 0) {
            $this->warn("Missing parents (legacy id_kecamatan) for " . count($missingParents) . " districts. Need to import them first.");
        }

        return 0;
    }
}
