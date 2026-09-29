<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

function printTitle($title) {
    echo "\n=== $title ===\n";
}

$legacy = DB::connection('legacy');

printTitle('Tables matching icd or diagnos');
$tables = $legacy->select("SHOW TABLES");
$dbName = 'simrs_legacy'; // Assuming this, or we can just get the values
$icdTables = [];
$reportTables = [];
$tindakanTables = [];

foreach ($tables as $tableObj) {
    $tableArray = (array) $tableObj;
    $tableName = array_values($tableArray)[0];
    
    if (stripos($tableName, 'icd') !== false || stripos($tableName, 'diagnos') !== false) {
        $icdTables[] = $tableName;
    }
    if (stripos($tableName, 'report') !== false || stripos($tableName, 'kelompok_laporan') !== false || stripos($tableName, 'grup') !== false) {
        $reportTables[] = $tableName;
    }
    if (stripos($tableName, 'tindakan') !== false || stripos($tableName, 'tarif') !== false || stripos($tableName, 'procedure') !== false || stripos($tableName, 'layanan') !== false) {
        $tindakanTables[] = $tableName;
    }
}

echo "ICD Tables: " . implode(', ', $icdTables) . "\n";
echo "Report Tables: " . implode(', ', $reportTables) . "\n";
echo "Tindakan Tables: " . implode(', ', $tindakanTables) . "\n";

printTitle('Describe Tindakan Tables');
$targetTindakanTable = 'master_tindakan'; // Common name, let's see if we find it
foreach ($tindakanTables as $tbl) {
    if (in_array($tbl, ['master_tindakan', 'tindakan', 'layanan'])) {
        echo "Columns in $tbl:\n";
        $cols = $legacy->select("SHOW COLUMNS FROM `$tbl`");
        foreach ($cols as $col) {
            echo " - {$col->Field} ({$col->Type})\n";
        }
    }
}

printTitle('Describe Report Tables');
foreach ($reportTables as $tbl) {
    echo "Columns in $tbl:\n";
    $cols = $legacy->select("SHOW COLUMNS FROM `$tbl`");
    foreach ($cols as $col) {
        echo " - {$col->Field} ({$col->Type})\n";
    }
}

printTitle('Describe ICD Tables');
foreach ($icdTables as $tbl) {
    echo "Columns in $tbl:\n";
    $cols = $legacy->select("SHOW COLUMNS FROM `$tbl`");
    foreach ($cols as $col) {
        echo " - {$col->Field} ({$col->Type})\n";
    }
}

echo "\nDone.\n";
