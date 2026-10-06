<?php

namespace App\Console\Commands;

use App\Models\District;
use App\Models\Education;
use App\Models\Occupation;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Signature('legacy:import-references {--dry-run : Only show what would be imported}')]
#[Description('Import references (education, occupation, regions) from legacy database')]
class LegacyImportReferencesCommand extends Command
{
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->info('Running in DRY-RUN mode. No data will be written.');
        }

        $legacyDb = DB::connection('legacy');

        $this->importEducations($legacyDb, $dryRun);
        $this->importOccupations($legacyDb, $dryRun);
        $this->importProvinces($legacyDb, $dryRun);
        $this->importRegencies($legacyDb, $dryRun);
        $this->importDistricts($legacyDb, $dryRun);
        $this->importVillages($legacyDb, $dryRun);
    }

    private function doImport($name, $data, $dryRun, $modelClass, $mapFn)
    {
        $this->info("Fetching legacy $name...");
        $stats = [
            'read' => count($data),
            'skipped' => 0,
            'create' => 0,
            'update' => 0,
            'failed' => 0,
        ];

        $this->withProgressBar($data, function ($row) use ($dryRun, &$stats, $modelClass, $mapFn, $name) {
            try {
                $mapped = $mapFn($row);
                if (! $mapped) {
                    $stats['skipped']++;

                    return;
                }

                if (! $dryRun) {
                    $item = $modelClass::updateOrCreate(
                        ['legacy_id' => $row->id],
                        $mapped
                    );
                    if ($item->wasRecentlyCreated) {
                        $stats['create']++;
                    } else {
                        $stats['update']++;
                    }
                } else {
                    $exists = $modelClass::where('legacy_id', $row->id)->exists();
                    if ($exists) {
                        $stats['update']++;
                    } else {
                        $stats['create']++;
                    }
                }
            } catch (\Exception $e) {
                $stats['failed']++;
                echo "\nError: ".$e->getMessage()."\n";
                if (! $dryRun) {
                    Log::error("Failed importing $name legacy_id {$row->id}: ".$e->getMessage());
                }
            }
        });

        $this->newLine();
        $this->table(
            ['Total Read', 'Valid/Processed', 'Skipped', 'Would Create / Created', 'Would Update / Updated', 'Failed'],
            [[
                $stats['read'],
                $stats['create'] + $stats['update'],
                $stats['skipped'],
                $stats['create'],
                $stats['update'],
                $stats['failed'],
            ]]
        );
    }

    private function importEducations($legacyDb, $dryRun)
    {
        $data = $legacyDb->select('SELECT id, pendidikan as name FROM ref_pendidikan');
        $this->doImport('Educations', $data, $dryRun, Education::class, function ($row) {
            return ['name' => $row->name];
        });
    }

    private function importOccupations($legacyDb, $dryRun)
    {
        $data = $legacyDb->select('SELECT id, pekerjaan as name FROM ref_pekerjaan');
        $this->doImport('Occupations', $data, $dryRun, Occupation::class, function ($row) {
            return ['name' => $row->name];
        });
    }

    private function importProvinces($legacyDb, $dryRun)
    {
        $data = $legacyDb->select('SELECT id, nama as name FROM ref_provinsi');
        $this->doImport('Provinces', $data, $dryRun, Province::class, function ($row) {
            return ['name' => $row->name];
        });
    }

    private function importRegencies($legacyDb, $dryRun)
    {
        $data = $legacyDb->select('SELECT id, id_provinsi, nama as name FROM ref_kabupaten');
        // Preload provinces mapping
        $provinceMap = Province::pluck('id', 'legacy_id')->toArray();
        $this->doImport('Regencies', $data, $dryRun, Regency::class, function ($row) use ($provinceMap) {
            if (! isset($provinceMap[$row->id_provinsi])) {
                return null;
            }

            return [
                'code' => $row->id,
                'name' => $row->name,
                'province_id' => $provinceMap[$row->id_provinsi],
            ];
        });
    }

    private function importDistricts($legacyDb, $dryRun)
    {
        $data = $legacyDb->select('SELECT id, id_kabupaten, nama as name FROM ref_kecamatan');
        $regencyMap = Regency::pluck('id', 'legacy_id')->toArray();
        $this->doImport('Districts', $data, $dryRun, District::class, function ($row) use ($regencyMap) {
            if (! isset($regencyMap[$row->id_kabupaten])) {
                return null;
            }

            return [
                'name' => $row->name,
                'regency_id' => $regencyMap[$row->id_kabupaten],
            ];
        });
    }

    private function importVillages($legacyDb, $dryRun)
    {
        $data = $legacyDb->select('SELECT id, id_kecamatan, nama as name FROM ref_kelurahan');
        $districtMap = District::pluck('id', 'legacy_id')->toArray();
        $this->doImport('Villages', $data, $dryRun, Village::class, function ($row) use ($districtMap) {
            if (! isset($districtMap[$row->id_kecamatan])) {
                return null;
            }

            return [
                'name' => $row->name,
                'district_id' => $districtMap[$row->id_kecamatan],
            ];
        });
    }
}
