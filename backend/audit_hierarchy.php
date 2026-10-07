<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$db = DB::connection('legacy');
$records = $db->table('ref_jenis_kegiatan')->get();

$roots = 0;
$hasParent = 0;
$orphans = [];
$selfParent = [];
$emptyNames = 0;
$parentZeros = 0;
$statusDist = ['active' => 0, 'inactive' => 0];

$idMap = [];
foreach ($records as $r) {
    $idMap[$r->id] = $r;
    if ($r->status == 1) {
        $statusDist['active']++;
    } else {
        $statusDist['inactive']++;
    }

    if (trim($r->nama) === '') {
        $emptyNames++;
    }
}

$parentIsDeleted = 0;
foreach ($records as $r) {
    $pid = $r->parent_id;
    if ($pid === null || $pid === '0' || $pid === 0 || $pid === '') {
        $roots++;
        if ($pid === '0' || $pid === 0) {
            $parentZeros++;
        }
    } else {
        $hasParent++;
        if (! isset($idMap[$pid])) {
            $orphans[] = $r->id;
        } else {
            if ($r->id == $pid) {
                $selfParent[] = $r->id;
            }
            if ($idMap[$pid]->deleted_at !== null) {
                $parentIsDeleted++;
            }
        }
    }
}

// Find max depth and cycles
$maxDepth = 0;
$cycles = [];
foreach ($records as $r) {
    $visited = [];
    $curr = $r->id;
    $depth = 0;
    while (isset($idMap[$curr]) && $idMap[$curr]->parent_id !== null && $idMap[$curr]->parent_id !== 0) {
        if (isset($visited[$curr])) {
            $cycles[] = $r->id;
            break;
        }
        $visited[$curr] = true;
        $curr = $idMap[$curr]->parent_id;
        $depth++;
    }
    if ($depth > $maxDepth) {
        $maxDepth = $depth;
    }
}

// Name duplicates under same parent
$duplicates = 0;
$nameByParent = [];
foreach ($records as $r) {
    $pid = $r->parent_id ?: 'root';
    $name = strtolower(trim($r->nama));
    if (isset($nameByParent[$pid][$name])) {
        $duplicates++;
    }
    $nameByParent[$pid][$name] = true;
}

echo "=== HIERARCHY AUDIT ===\n";
echo 'Total Records: '.count($records)."\n";
echo "Roots: {$roots} (With parent=0: {$parentZeros})\n";
echo "With Parent: {$hasParent}\n";
echo 'Orphans (Parent not found): '.count($orphans)."\n";
echo 'Self Parents: '.count($selfParent)."\n";
echo "Parent is Soft-Deleted: {$parentIsDeleted}\n";
echo "Empty Names: {$emptyNames}\n";
echo "Max Depth: {$maxDepth}\n";
echo 'Cycles Found: '.count($cycles)."\n";
echo "Status: Active={$statusDist['active']}, Inactive={$statusDist['inactive']}\n";
echo "Duplicates under same parent: {$duplicates}\n";
