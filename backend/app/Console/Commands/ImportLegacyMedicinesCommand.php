<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\MedicineCategory;
use App\Models\MasterData\MedicineUnit;
use App\Models\MasterData\MedicineClassification;
use App\Models\MasterData\MedicineRoute;
use App\Models\MasterData\GenericMedicine;

class ImportLegacyMedicinesCommand extends Command
{
    protected $signature = 'import:legacy-medicines';
    protected $description = 'Import medicines and minimal dependencies from legacy database';

    public function handle()
    {
        $this->info('Starting import of legacy medicines dependencies...');

        // 1. Medicine Categories
        $legacyCategories = DB::connection('legacy')->table('ref_kategori_obat')->get();
        $this->info("Importing {$legacyCategories->count()} Medicine Categories...");
        $insertedCategories = 0;
        foreach ($legacyCategories as $cat) {
            MedicineCategory::updateOrCreate(
                ['legacy_id' => $cat->id],
                [
                    'name' => trim($cat->nama ?? 'Tanpa Nama'),
                    'is_active' => $cat->status == 1
                ]
            );
            $insertedCategories++;
        }

        // 2. Medicine Units
        $legacyUnits = DB::connection('legacy')->table('ref_satuan_obat')->get();
        $this->info("Importing {$legacyUnits->count()} Medicine Units...");
        $insertedUnits = 0;
        foreach ($legacyUnits as $unit) {
            MedicineUnit::updateOrCreate(
                ['legacy_id' => $unit->id],
                [
                    'name' => trim($unit->nama ?? 'Tanpa Nama'),
                    'is_active' => true
                ]
            );
            $insertedUnits++;
        }

        // 3. Medicine Classifications (Golongan)
        $legacyClassifications = DB::connection('legacy')->table('ref_obat_golongan')->get();
        $this->info("Importing {$legacyClassifications->count()} Medicine Classifications...");
        $insertedClass = 0;
        foreach ($legacyClassifications as $class) {
            MedicineClassification::updateOrCreate(
                ['legacy_id' => $class->id],
                [
                    'name' => trim($class->name ?? 'Tanpa Nama'),
                    'is_active' => $class->status == 1
                ]
            );
            $insertedClass++;
        }

        // 4. Medicine Routes (Jalur Masuk)
        $legacyRoutes = DB::connection('legacy')->table('ref_obat_jalur_masuk')->get();
        $this->info("Importing {$legacyRoutes->count()} Medicine Routes...");
        $insertedRoutes = 0;
        foreach ($legacyRoutes as $route) {
            MedicineRoute::updateOrCreate(
                ['legacy_id' => $route->id],
                [
                    'name' => trim($route->rute_obat ?? 'Tanpa Nama'),
                    'is_active' => $route->status == 1
                ]
            );
            $insertedRoutes++;
        }

        // 5. Generic Medicines (Chunked)
        $this->info("Importing Generic Medicines...");
        $insertedGenerics = 0;
        DB::connection('legacy')->table('ref_generik')->orderBy('id')->chunk(1000, function ($generics) use (&$insertedGenerics) {
            $genericsData = [];
            foreach ($generics as $gen) {
                $name = trim($gen->nama_objek ?? 'Tanpa Nama');
                if (empty($name)) continue;
                
                $genericsData[] = [
                    'legacy_id' => $gen->id,
                    'name' => $name,
                    'is_active' => $gen->status == 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $insertedGenerics++;
            }
            GenericMedicine::upsert($genericsData, ['legacy_id'], ['name', 'is_active', 'updated_at']);
        });

        $this->info('Dependencies imported.');

        // Get maps
        $mapCategories = MedicineCategory::pluck('id', 'legacy_id')->toArray();
        $mapUnits = MedicineUnit::pluck('id', 'legacy_id')->toArray();
        $mapClassifications = MedicineClassification::pluck('id', 'legacy_id')->toArray();
        $mapRoutes = MedicineRoute::pluck('id', 'legacy_id')->toArray();
        $mapGenerics = GenericMedicine::pluck('id', 'legacy_id')->toArray();

        // 6. Medicines
        $legacyMedicines = DB::connection('legacy')->table('ref_obat')->get();
        $this->info("Importing {$legacyMedicines->count()} Medicines...");
        
        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        
        foreach ($legacyMedicines as $med) {
            $name = trim($med->nama ?? '');
            if (empty($name)) {
                $skipped++;
                continue;
            }

            $data = [
                'code' => $med->kode,
                'name' => $name,
                'kfa_code' => $med->kode_kfa,
                'function' => $med->fungsi,
                'medicine_unit_id' => $mapUnits[$med->id_satuan_obat] ?? null,
                'medicine_category_id' => $mapCategories[$med->id_kategori_obat] ?? null,
                'medicine_classification_id' => $mapClassifications[$med->id_golongan] ?? null,
                'medicine_route_id' => $mapRoutes[$med->id_obat_jalur_masuk] ?? null,
                'generic_medicine_id' => $mapGenerics[$med->id_generik] ?? null,
                'description' => $med->deskripsi,
                'is_active' => $med->status == '1',
            ];

            $existing = Medicine::where('legacy_id', $med->id)->first();
            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                $data['legacy_id'] = $med->id;
                Medicine::create($data);
                $inserted++;
            }
        }

        $this->info("Import Summary:");
        $this->info("Inserted/Updated Categories: {$insertedCategories}");
        $this->info("Inserted/Updated Units: {$insertedUnits}");
        $this->info("Inserted/Updated Classifications: {$insertedClass}");
        $this->info("Inserted/Updated Routes: {$insertedRoutes}");
        $this->info("Inserted/Updated Generics: {$insertedGenerics}");
        $this->info("Medicines - Inserted: {$inserted}, Updated: {$updated}, Skipped: {$skipped}");
    }
}
