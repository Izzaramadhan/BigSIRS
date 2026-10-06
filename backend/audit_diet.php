<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$legacyDb = DB::connection('legacy');
$table = 'ref_jenis_diet';

echo "=== PROFILING $table ===\n";

$all = $legacyDb->table($table)->get();
$total = $all->count();
echo "Total records: $total\n\n";

$active = 0;
$inactive = 0;
$softDeleted = 0;
$nullDeletedAt = 0;
$zeroDeletedAt = 0;
$validDeletedAt = 0;
$emptyNames = 0;
$emptyDesc = 0;
$names = [];
$duplicateNames = [];

foreach ($all as $row) {
    if ($row->status == 1) {
        $active++;
    } else {
        $inactive++;
    }

    if ($row->deleted_at === null) {
        $nullDeletedAt++;
    } elseif ($row->deleted_at === '0000-00-00 00:00:00') {
        $zeroDeletedAt++;
    } else {
        $validDeletedAt++;
        $softDeleted++;
    }

    $name = trim($row->nama ?? '');
    if (empty($name)) {
        $emptyNames++;
    } else {
        $lowerName = strtolower($name);
        if (isset($names[$lowerName])) {
            $duplicateNames[] = $name;
        }
        $names[$lowerName] = true;
    }

    $desc = trim($row->deskripsi ?? '');
    if (empty($desc)) {
        $emptyDesc++;
    }

    echo "ID: {$row->id} | Nama: '{$row->nama}' | Desc: '{$row->deskripsi}' | Status: '{$row->status}' | Deleted: '{$row->deleted_at}'\n";
}

echo "\n=== SUMMARY ===\n";
echo "Active: $active\n";
echo "Inactive (status=0/null/empty): $inactive\n";
echo "Soft Deleted (valid deleted_at): $softDeleted\n";
echo "Null deleted_at: $nullDeletedAt\n";
echo "Zero deleted_at (0000-00-00 00:00:00): $zeroDeletedAt\n";
echo "Empty names: $emptyNames\n";
echo "Empty descriptions: $emptyDesc\n";
echo 'Duplicate names: '.implode(', ', $duplicateNames)."\n";
