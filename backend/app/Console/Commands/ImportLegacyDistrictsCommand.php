<?php

namespace App\Console\Commands;

use App\Models\Regency;
use App\Models\District;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportLegacyDistrictsCommand extends Command
{
    protected $signature = 'legacy:import-districts {--dry-run : Only show what would be done without saving}';
    protected $description = 'Import districts from legacy ref_kecamatan table';

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

        $legacyDistricts = DB::connection('legacy')->table('ref_kecamatan')->get();
        $this->info("Total Read: " . $legacyDistricts->count());

        $regencyMap = Regency::pluck('id', 'legacy_id')->toArray();
        $existingDistricts = District::pluck('id', 'legacy_id')->toArray();

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
            foreach ($legacyDistricts as $row) {
                $legacyId = trim((string)$row->id);
                $legacyRegencyId = trim((string)$row->id_kabupaten);
                
                $isDeleted = !in_array($row->deleted_at, [null, '', '0000-00-00', '0000-00-00 00:00:00'], true);
                if ($isDeleted) {
                    $stats['skipped']++;
                    continue;
                }

                if (!isset($regencyMap[$legacyRegencyId])) {
                    $stats['missing_parent']++;
                    if (!in_array($legacyRegencyId, $missingParents)) {
                        $missingParents[] = $legacyRegencyId;
                    }
                    continue;
                }

                $regencyId = $regencyMap[$legacyRegencyId];
                $name = $row->nama ? Str::squish(trim($row->nama)) : '-';
                $code = $legacyId; // Usually legacy ID is the code for districts
                $isActive = in_array($row->status, [1, '1'], true);

                $payload = [
                    'regency_id' => $regencyId,
                    'code' => $code,
                    'name' => $name,
                    'is_active' => $isActive,
                ];

                if (isset($existingDistricts[$legacyId])) {
                    $stats['would_update']++;
                    if (!$isDryRun) {
                        District::where('legacy_id', $legacyId)->update($payload);
                        $stats['updated']++;
                    }
                } else {
                    $stats['would_create']++;
                    if (!$isDryRun) {
                        $payload['legacy_id'] = $legacyId;
                        District::create($payload);
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
                ['Total Read', $legacyDistricts->count()],
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
            $this->warn("Missing parents (legacy id_kabupaten) for " . count($missingParents) . " regencies. Need to import them first.");
        }

        return 0;
    }
}
