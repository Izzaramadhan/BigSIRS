<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('legacy:import-radiology-groups')]
#[Description('Import radiology groups and their item group mappings from legacy database')]
class ImportLegacyRadiologyGroupsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting import of radiology groups...');

        $legacyGroups = \Illuminate\Support\Facades\DB::connection('legacy')->table('ref_grup_rad')->get();
        $legacyMappings = \Illuminate\Support\Facades\DB::connection('legacy')->table('map_grup_kelompok_rad')->get();

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $missingParent = 0;
        $pivotInserted = 0;
        $duplicatePivot = 0;
        $missingItem = 0;

        // Group mapping for faster pivot sync
        $mappingByGroup = [];
        foreach ($legacyMappings as $mapping) {
            $mappingByGroup[$mapping->id_grup_rad][] = $mapping->id_kelompok_rad;
        }

        foreach ($legacyGroups as $legacyGroup) {
            $name = trim($legacyGroup->nama);
            if (empty($name)) {
                $skipped++;
                continue;
            }

            // Resolve parents
            $categoryId = null;
            if ($legacyGroup->id_kategori_rad) {
                $category = \App\Models\MasterData\RadiologyCategory::where('legacy_id', $legacyGroup->id_kategori_rad)->first();
                if ($category) {
                    $categoryId = $category->id;
                } else {
                    $missingParent++;
                    $this->warn("Missing category legacy_id {$legacyGroup->id_kategori_rad}");
                }
            }

            $typeId = null;
            if ($legacyGroup->id_tipe_rad) {
                $type = \App\Models\MasterData\RadiologyType::where('legacy_id', $legacyGroup->id_tipe_rad)->first();
                if ($type) {
                    $typeId = $type->id;
                } else {
                    $missingParent++;
                    $this->warn("Missing type legacy_id {$legacyGroup->id_tipe_rad}");
                }
            }

            $activityTypeId = null;
            if ($legacyGroup->id_ref_jenis_kegiatan) {
                $activityType = \App\Models\MasterData\ActivityType::where('legacy_id', $legacyGroup->id_ref_jenis_kegiatan)->first();
                if ($activityType) {
                    $activityTypeId = $activityType->id;
                } else {
                    $missingParent++;
                    $this->warn("Missing activity type legacy_id {$legacyGroup->id_ref_jenis_kegiatan}");
                }
            }

            $data = [
                'radiology_category_id' => $categoryId,
                'radiology_type_id' => $typeId,
                'activity_type_id' => $activityTypeId,
                'name' => $name,
                'price' => $legacyGroup->harga ?: 0,
                'interpretation_price' => $legacyGroup->harga_interpretasi ?: 0,
                'loinc_code' => $legacyGroup->loinc_code,
                'loinc_url' => $legacyGroup->loinc_url,
                'is_active' => (bool)$legacyGroup->status,
            ];

            if ($legacyGroup->deleted_at && $legacyGroup->deleted_at !== '0000-00-00 00:00:00') {
                $data['deleted_at'] = $legacyGroup->deleted_at;
            } else {
                $data['deleted_at'] = null;
            }

            $group = \App\Models\MasterData\RadiologyGroup::where('legacy_id', $legacyGroup->id)->first();
            
            if ($group) {
                $group->update($data);
                $updated++;
            } else {
                $data['legacy_id'] = $legacyGroup->id;
                $group = \App\Models\MasterData\RadiologyGroup::create($data);
                $inserted++;
            }

            // Sync item groups
            if (isset($mappingByGroup[$legacyGroup->id])) {
                $itemGroupLegacyIds = $mappingByGroup[$legacyGroup->id];
                $itemGroupIdsToSync = [];

                foreach ($itemGroupLegacyIds as $itemGroupLegacyId) {
                    $itemGroup = \App\Models\MasterData\RadiologyItemGroup::where('legacy_id', $itemGroupLegacyId)->first();
                    if ($itemGroup) {
                        $itemGroupIdsToSync[] = $itemGroup->id;
                    } else {
                        $missingItem++;
                        $this->warn("Missing radiology item group legacy_id {$itemGroupLegacyId} for group {$legacyGroup->id}");
                    }
                }

                // Sync pivot
                $syncResult = $group->itemGroups()->sync($itemGroupIdsToSync);
                $pivotInserted += count($syncResult['attached']);
                // Assuming unchanged as duplicate attempts
                $duplicatePivot += count($itemGroupIdsToSync) - count($syncResult['attached']) - count($syncResult['detached']) - count($syncResult['updated']);
            }
        }

        $this->info("Import completed!");
        $this->info("Total legacy groups: {$legacyGroups->count()}");
        $this->info("Groups Inserted: {$inserted}");
        $this->info("Groups Updated: {$updated}");
        $this->info("Groups Skipped: {$skipped}");
        $this->info("Missing Parents: {$missingParent}");
        
        $this->info("Total legacy mappings: {$legacyMappings->count()}");
        $this->info("Pivot Inserted: {$pivotInserted}");
        $this->info("Pivot Duplicate/Unchanged: {$duplicatePivot}");
        $this->info("Missing Items (Kelompok): {$missingItem}");
    }
}
