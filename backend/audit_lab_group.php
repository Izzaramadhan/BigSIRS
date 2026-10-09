<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$legacy = DB::connection('legacy');
$target = DB::connection('mysql');

echo "=== Legacy ref_grup_lab Columns ===\n";
$columns = $legacy->select('SHOW COLUMNS FROM ref_grup_lab');
foreach ($columns as $col) {
    echo "{$col->Field} - {$col->Type} - Null: {$col->Null} - Key: {$col->Key}\n";
}

echo "\n=== Legacy map_grup_item_lab Columns ===\n";
$columns = $legacy->select('SHOW COLUMNS FROM map_grup_item_lab');
foreach ($columns as $col) {
    echo "{$col->Field} - {$col->Type} - Null: {$col->Null} - Key: {$col->Key}\n";
}

echo "\n=== Data Profile ===\n";
$totalGroups = $legacy->table('ref_grup_lab')->count();
echo "Total Groups: $totalGroups\n";

$withoutCategory = $legacy->table('ref_grup_lab')
    ->whereNull('id_kategori_lab')
    ->orWhere('id_kategori_lab', '')
    ->orWhere('id_kategori_lab', 0)
    ->count();
echo "Groups without category (null/empty/0): $withoutCategory\n";

// check with category not found
$categoryIds = $legacy->table('ref_kategori_lab')->pluck('id')->toArray();
$invalidCategory = $legacy->table('ref_grup_lab')
    ->whereNotIn('id_kategori_lab', $categoryIds)
    ->whereNotNull('id_kategori_lab')
    ->where('id_kategori_lab', '!=', 0)
    ->count();
echo "Groups with invalid category: $invalidCategory\n";

$totalPivots = $legacy->table('map_grup_item_lab')->count();
echo "Total map_grup_item_lab records: $totalPivots\n";

$duplicatePairs = $legacy->table('map_grup_item_lab')
    ->select('id_grup', 'id_item', DB::raw('count(*) as c'))
    ->groupBy('id_grup', 'id_item')
    ->having('c', '>', 1)
    ->get();
echo 'Duplicate pairs in map_grup_item_lab: '.count($duplicatePairs)."\n";

$orphanGroups = $legacy->table('map_grup_item_lab')
    ->whereNotIn('id_grup', function ($q) {
        $q->select('id')->from('ref_grup_lab');
    })->count();
echo "Orphan pivots (invalid group_id): $orphanGroups\n";

$orphanItems = $legacy->table('map_grup_item_lab')
    ->whereNotIn('id_item', function ($q) {
        $q->select('id')->from('ref_item_lab');
    })->count();
echo "Orphan pivots (invalid item_id): $orphanItems\n";

$groupsWithoutItem = $legacy->table('ref_grup_lab')
    ->whereNotIn('id', function ($q) {
        $q->select('id_grup')->from('map_grup_item_lab');
    })->count();
echo "Groups without items: $groupsWithoutItem\n";

$maxItemsGroup = $legacy->table('map_grup_item_lab')
    ->select('id_grup', DB::raw('count(*) as item_count'))
    ->groupBy('id_grup')
    ->orderByDesc('item_count')
    ->first();
if ($maxItemsGroup) {
    echo "Group with most items: {$maxItemsGroup->id_grup} ({$maxItemsGroup->item_count} items)\n";
}
