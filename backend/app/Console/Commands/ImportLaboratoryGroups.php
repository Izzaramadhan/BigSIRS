<?php

namespace App\Console\Commands;

use App\Models\MasterData\LaboratoryCategory;
use App\Models\MasterData\LaboratoryGroup;
use App\Models\MasterData\LaboratoryItem;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('import:laboratory-groups')]
#[Description('Import laboratory groups and their items from legacy database')]
class ImportLaboratoryGroups extends Command
{
    public function handle()
    {
        $this->info('Starting Laboratory Groups Import...');

        $legacyDb = DB::connection('legacy');

        $legacyGroups = $legacyDb->table('ref_grup_lab')->get();
        $legacyPivots = $legacyDb->table('map_grup_item_lab')->get();

        $this->info("Found {$legacyGroups->count()} groups and {$legacyPivots->count()} pivots in legacy.");

        $categoriesMap = LaboratoryCategory::whereNotNull('legacy_id')->pluck('id', 'legacy_id')->toArray();
        $itemsMap = LaboratoryItem::whereNotNull('legacy_id')->pluck('id', 'legacy_id')->toArray();

        $stats = [
            'groups_inserted' => 0,
            'groups_updated' => 0,
            'groups_skipped' => 0,
            'missing_category' => 0,
            'pivots_inserted' => 0,
            'pivots_unchanged' => 0,
            'duplicate_pivots' => 0,
            'missing_group' => 0,
            'missing_item' => 0,
        ];

        DB::beginTransaction();
        try {
            foreach ($legacyGroups as $legacyGroup) {
                if (empty($legacyGroup->id_kategori_lab)) {
                    $stats['groups_skipped']++;

                    continue;
                }

                if (! isset($categoriesMap[$legacyGroup->id_kategori_lab])) {
                    $stats['missing_category']++;
                    $stats['groups_skipped']++;

                    continue;
                }

                $group = LaboratoryGroup::updateOrCreate(
                    ['legacy_id' => $legacyGroup->id],
                    [
                        'laboratory_category_id' => $categoriesMap[$legacyGroup->id_kategori_lab],
                        'name' => $legacyGroup->nama,
                        'description' => $legacyGroup->deskripsi,
                        'price' => $legacyGroup->harga,
                        'is_active' => $legacyGroup->status !== '0', // Assuming '1' or '2' is active, '0' is inactive
                        'deleted_at' => $legacyGroup->deleted_at ? Carbon::parse($legacyGroup->deleted_at) : null,
                    ]
                );

                if ($group->wasRecentlyCreated) {
                    $stats['groups_inserted']++;
                } else {
                    $stats['groups_updated']++;
                }
            }

            $groupsMap = LaboratoryGroup::whereNotNull('legacy_id')->pluck('id', 'legacy_id')->toArray();
            $processedPivots = [];

            foreach ($legacyPivots as $legacyPivot) {
                if (! isset($groupsMap[$legacyPivot->id_grup])) {
                    $stats['missing_group']++;

                    continue;
                }

                if (! isset($itemsMap[$legacyPivot->id_item])) {
                    $stats['missing_item']++;

                    continue;
                }

                $pairKey = $groupsMap[$legacyPivot->id_grup].'-'.$itemsMap[$legacyPivot->id_item];
                if (isset($processedPivots[$pairKey])) {
                    $stats['duplicate_pivots']++;

                    continue;
                }
                $processedPivots[$pairKey] = true;

                $inserted = DB::table('laboratory_group_items')->insertOrIgnore([
                    'laboratory_group_id' => $groupsMap[$legacyPivot->id_grup],
                    'laboratory_item_id' => $itemsMap[$legacyPivot->id_item],
                    'sort_order' => null,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                if ($inserted) {
                    $stats['pivots_inserted']++;
                } else {
                    $stats['pivots_unchanged']++;
                }
            }

            DB::commit();

            $this->info('Import completed successfully.');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total groups source', $legacyGroups->count()],
                    ['Groups inserted', $stats['groups_inserted']],
                    ['Groups updated', $stats['groups_updated']],
                    ['Groups skipped', $stats['groups_skipped']],
                    ['Missing category', $stats['missing_category']],
                    ['Total pivots source', $legacyPivots->count()],
                    ['Pivots inserted', $stats['pivots_inserted']],
                    ['Pivots unchanged', $stats['pivots_unchanged']],
                    ['Duplicate pivots', $stats['duplicate_pivots']],
                    ['Missing group', $stats['missing_group']],
                    ['Missing item', $stats['missing_item']],
                ]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Import failed: '.$e->getMessage());
        }
    }
}
