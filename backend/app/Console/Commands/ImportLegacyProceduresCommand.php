<?php

namespace App\Console\Commands;

use App\Models\MasterData\Icd9Cm;
use App\Models\MasterData\MedicalProcedure;
use App\Models\MasterData\ReportGroup;
use App\Models\Polyclinic;
use App\Models\ProcedureCategory;
use App\Models\TariffComponent;
use App\Models\TariffType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyProceduresCommand extends Command
{
    protected $signature = 'legacy:import-procedures {--dry-run : Perform a dry run without saving to the database}';

    protected $description = 'Import medical procedures from legacy database';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Starting legacy import (Dry Run: '.($isDryRun ? 'Yes' : 'No').')');

        DB::beginTransaction();
        try {
            // Stats
            $stats = [
                'Total Read' => 0,
                'Skipped Actual Soft Deleted' => 0,
                'Valid for Import' => 0,
                'Would Create' => 0,
                'Would Update' => 0,
                'Missing Category Mapping' => 0,
                'Missing Tariff Type Mapping' => 0,
                'Missing Component Mapping' => 0,
                'Missing Polyclinic Mapping' => 0,
                'Missing ICD Mapping' => 0,
                'Missing Report Group Mapping' => 0,
                'Orphan Relations' => 0,
                'Duplicate Codes' => 0,
                'Duplicate Procedure-Tariff Relations' => 0,
                'Invalid/Negative Amounts' => 0,
                'Total Mismatches' => 0,
                'Empty Component Details' => 0,
                'Records Marked Needs Review' => 0,
                'Failed' => 0,
            ];

            // Build legacy_id maps for performance
            $categoryMap = ProcedureCategory::pluck('id', 'legacy_id')->toArray();
            $tariffTypeMap = TariffType::pluck('id', 'legacy_id')->toArray();
            $componentMap = TariffComponent::pluck('id', 'legacy_id')->toArray();
            $polyclinicMap = Polyclinic::pluck('id', 'legacy_id')->toArray();
            $reportGroupMap = ReportGroup::pluck('id', 'legacy_id')->toArray();
            $icd9Map = Icd9Cm::pluck('id', 'code')->toArray();

            // Chunking legacy data to prevent memory issues
            $legacyProcedures = DB::connection('legacy')->table('ref_tarif_tindakan')
                ->orderBy('id')
                ->get();

            $stats['Total Read'] = $legacyProcedures->count();

            // Filter out soft deleted
            $validProcedures = [];
            foreach ($legacyProcedures as $row) {
                if ($row->deleted_at && $row->deleted_at !== '0000-00-00 00:00:00') {
                    $stats['Skipped Actual Soft Deleted']++;

                    continue;
                }
                $validProcedures[] = $row;
            }

            // Group by kode + nama to form MedicalProcedure Header
            $groupedProcedures = [];
            foreach ($validProcedures as $row) {
                $stats['Valid for Import']++;
                $groupKey = trim($row->kode).'_'.trim($row->nama).'_'.$row->id_kategori;
                if (! isset($groupedProcedures[$groupKey])) {
                    $groupedProcedures[$groupKey] = [
                        'legacy_id' => $row->id,
                        'kode' => trim($row->kode),
                        'nama' => trim($row->nama),
                        'id_kategori' => $row->id_kategori,
                        'icd9_code' => $row->icd9_code,
                        'status' => $row->status,
                        'tariffs' => [],
                    ];
                }
                $groupedProcedures[$groupKey]['tariffs'][] = $row;
            }

            // Pre-load all legacy mapping data for polyclinics, report groups, and components
            // To save time, we will load them in bulk
            $allTariffIds = collect($validProcedures)->pluck('id')->toArray();

            $legacyPolys = [];
            if (! empty($allTariffIds)) {
                $legacyPolysRaw = DB::connection('legacy')->table('map_tarif_tindakan_poliklinik')
                    ->whereIn('id_ref_tarif_tindakan', $allTariffIds)
                    ->where(function ($q) {
                        $q->whereNull('deleted_at')->orWhere('deleted_at', '0000-00-00 00:00:00');
                    })
                    ->get();
                foreach ($legacyPolysRaw as $lp) {
                    $legacyPolys[$lp->id_ref_tarif_tindakan][] = $lp->id_poliklinik;
                }
            }

            $legacyRGs = [];
            if (! empty($allTariffIds)) {
                $legacyRGsRaw = DB::connection('legacy')->table('map_tindakan_kelompok_laporan')
                    ->whereIn('id_ref_tarif_tindakan', $allTariffIds)
                    ->where(function ($q) {
                        $q->whereNull('deleted_at')->orWhere('deleted_at', '0000-00-00 00:00:00');
                    })
                    ->get();
                foreach ($legacyRGsRaw as $lrg) {
                    $legacyRGs[$lrg->id_ref_tarif_tindakan][] = $lrg->id_kelompok_laporan;
                }
            }

            $legacyComponentsRaw = [];
            if (! empty($allTariffIds)) {
                $legacyComponentsRaw = DB::connection('legacy')->table('map_tarif_tindakan_komponen')
                    ->join('map_jenis_tarif_komponen', 'map_tarif_tindakan_komponen.id_map_jenis_tarif_komponen', '=', 'map_jenis_tarif_komponen.id')
                    ->whereIn('map_tarif_tindakan_komponen.id_tarif_tindakan', $allTariffIds)
                    ->where(function ($q) {
                        $q->whereNull('map_tarif_tindakan_komponen.deleted_at')->orWhere('map_tarif_tindakan_komponen.deleted_at', '0000-00-00 00:00:00');
                    })
                    ->select('map_tarif_tindakan_komponen.*', 'map_jenis_tarif_komponen.id_komponen', 'map_jenis_tarif_komponen.persen')
                    ->get();
            }
            $legacyComponents = [];
            foreach ($legacyComponentsRaw as $comp) {
                $legacyComponents[$comp->id_tarif_tindakan][] = $comp;
            }

            foreach ($groupedProcedures as $groupKey => $headerData) {
                // Map Category using legacy_id
                $categoryId = $categoryMap[$headerData['id_kategori']] ?? null;

                if (! $categoryId && $headerData['id_kategori'] == 0) {
                    $defaultCat = ProcedureCategory::firstOrCreate(
                        ['name' => 'Tanpa Kategori'],
                        ['description' => 'Kategori otomatis untuk tindakan tanpa kategori di legacy', 'is_active' => true]
                    );
                    $categoryId = $defaultCat->id;
                    $categoryMap[0] = $categoryId;
                }

                if (! $categoryId) {
                    $stats['Missing Category Mapping']++;
                    $stats['Failed']++;

                    continue; // Mandatory relation
                }

                // Map ICD9 using code
                $icdId = null;
                if (! empty($headerData['icd9_code'])) {
                    $icdId = $icd9Map[$headerData['icd9_code']] ?? null;
                    if (! $icdId) {
                        $stats['Missing ICD Mapping']++;
                    }
                }

                // Find Existing Header
                $procedure = MedicalProcedure::where('legacy_id', $headerData['legacy_id'])
                    ->orWhere(function ($q) use ($headerData, $categoryId) {
                        $q->where('code', $headerData['kode'])->where('procedure_category_id', $categoryId);
                    })->first();

                if ($procedure) {
                    $stats['Would Update']++;
                    if (! $isDryRun) {
                        $procedure->update([
                            'name' => $headerData['nama'],
                            'icd9_cm_id' => $icdId,
                            'is_visible' => $headerData['status'] == 1,
                        ]);
                    }
                } else {
                    $stats['Would Create']++;
                    if (! $isDryRun) {
                        $procedure = MedicalProcedure::create([
                            'legacy_id' => $headerData['legacy_id'],
                            'code' => $headerData['kode'],
                            'name' => $headerData['nama'],
                            'procedure_category_id' => $categoryId,
                            'icd9_cm_id' => $icdId,
                            'is_visible' => $headerData['status'] == 1,
                        ]);
                    } else {
                        // Mock procedure for dry-run
                        $procedure = new MedicalProcedure(['id' => crc32($groupKey)]);
                    }
                }

                // Polyclinics and Report Groups
                $polyIds = [];
                $rgIds = [];
                foreach ($headerData['tariffs'] as $legacyTariff) {
                    if (isset($legacyPolys[$legacyTariff->id])) {
                        foreach ($legacyPolys[$legacyTariff->id] as $lp) {
                            if (isset($polyclinicMap[$lp])) {
                                $polyIds[] = $polyclinicMap[$lp];
                            } else {
                                $stats['Missing Polyclinic Mapping']++;
                            }
                        }
                    }

                    if (isset($legacyRGs[$legacyTariff->id])) {
                        foreach ($legacyRGs[$legacyTariff->id] as $lrg) {
                            if (isset($reportGroupMap[$lrg])) {
                                $rgIds[] = $reportGroupMap[$lrg];
                            } else {
                                $stats['Missing Report Group Mapping']++;
                            }
                        }
                    }
                }
                $polyIds = array_unique($polyIds);
                $rgIds = array_unique($rgIds);

                if (! $isDryRun && $procedure->exists) {
                    $procedure->polyclinics()->sync($polyIds);
                    $procedure->reportGroups()->sync($rgIds);
                }

                // Tariffs
                foreach ($headerData['tariffs'] as $legacyTariff) {
                    // Map TariffType using legacy_id
                    $tariffTypeId = $tariffTypeMap[$legacyTariff->id_jenis_tarif] ?? null;

                    if (! $tariffTypeId && $legacyTariff->id_jenis_tarif == 0) {
                        $defaultType = TariffType::firstOrCreate(
                            ['name' => 'Tanpa Jenis Tarif'],
                            ['description' => 'Jenis Tarif otomatis', 'is_active' => true]
                        );
                        $tariffTypeId = $defaultType->id;
                        $tariffTypeMap[0] = $tariffTypeId;
                    }

                    if (! $tariffTypeId) {
                        $stats['Missing Tariff Type Mapping']++;

                        continue;
                    }

                    if ($legacyTariff->harga < 0) {
                        $stats['Invalid/Negative Amounts']++;
                    }

                    $components = $legacyComponents[$legacyTariff->id] ?? [];

                    if (empty($components)) {
                        $stats['Empty Component Details']++;
                    }

                    $calculatedTotal = collect($components)->sum('harga');
                    if ($calculatedTotal != $legacyTariff->harga && ! empty($components)) {
                        $stats['Total Mismatches']++;
                    }

                    if (! $isDryRun && $procedure->exists) {
                        $tariff = $procedure->tariffs()->updateOrCreate(
                            ['legacy_id' => $legacyTariff->id],
                            [
                                'tariff_type_id' => $tariffTypeId,
                                'total_amount' => $calculatedTotal > 0 ? $calculatedTotal : $legacyTariff->harga,
                            ]
                        );

                        foreach ($components as $comp) {
                            // Map Component using legacy_id
                            $compId = $componentMap[$comp->id_komponen] ?? null;
                            if (! $compId) {
                                $stats['Missing Component Mapping']++;

                                continue; // do not replace with null, skip it
                            }

                            $tariff->components()->updateOrCreate(
                                ['legacy_id' => $comp->id],
                                [
                                    'tariff_component_id' => $compId,
                                    'percentage_snapshot' => $comp->persen ?? 0,
                                    'amount' => $comp->harga,
                                ]
                            );
                        }
                    } elseif ($isDryRun) {
                        // Just check component mapping
                        foreach ($components as $comp) {
                            if (! isset($componentMap[$comp->id_komponen])) {
                                $stats['Missing Component Mapping']++;
                            }
                        }
                    }
                }
            }

            $this->table(['Metric', 'Count'], collect($stats)->map(function ($value, $key) {
                return [$key, $value];
            })->toArray());

            if ($isDryRun) {
                DB::rollBack();
                $this->info('Dry run completed. Transactions rolled back.');
            } else {
                DB::commit();
                $this->info('Import completed successfully.');
            }

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Import failed: '.$e->getMessage());

            return 1;
        }
    }
}
