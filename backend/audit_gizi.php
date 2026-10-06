<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$legacyDb = DB::connection('legacy');

// 1. Search tables
$tables = $legacyDb->select("
    SELECT table_name 
    FROM information_schema.tables 
    WHERE table_schema = 'simrs_legacy_full' 
    AND (
        table_name LIKE '%gizi%' OR 
        table_name LIKE '%nutrisi%' OR 
        table_name LIKE '%nutrition%' OR 
        table_name LIKE '%asuhan%' OR 
        table_name LIKE '%diet%'
    )
");

echo "=== TABLES MATCHING KEYWORDS ===\n";
foreach ($tables as $table) {
    $tableName = $table->table_name ?? $table->TABLE_NAME;
    echo '- '.$tableName."\n";
}

// 2. Search columns
$columns = $legacyDb->select("
    SELECT table_name, column_name 
    FROM information_schema.columns 
    WHERE table_schema = 'simrs_legacy_full' 
    AND (
        column_name LIKE '%gizi%' OR 
        column_name LIKE '%nutrisi%' OR 
        column_name LIKE '%nutrition%' OR 
        column_name LIKE '%asuhan%' OR 
        column_name LIKE '%diet%'
    )
");

echo "\n=== COLUMNS MATCHING KEYWORDS ===\n";
$colMap = [];
foreach ($columns as $column) {
    $tableName = $column->table_name ?? $column->TABLE_NAME;
    $columnName = $column->column_name ?? $column->COLUMN_NAME;
    $colMap[$tableName][] = $columnName;
}
foreach ($colMap as $table => $cols) {
    echo "- $table: ".implode(', ', $cols)."\n";
}

// Check some specific table if they look like master data
$candidates = [
    'ref_asuhan_gizi',
    'ref_gizi',
    'ref_diet',
    'asuhan_gizi',
    'gizi',
    'diet',
];

echo "\n=== CHECKING CANDIDATES ===\n";
foreach ($candidates as $candidate) {
    try {
        $count = $legacyDb->table($candidate)->count();
        echo "Table '$candidate' exists with $count rows.\n";

        $sample = $legacyDb->table($candidate)->limit(3)->get();
        echo "Sample data:\n";
        print_r($sample);
    } catch (Exception $e) {
        // Table doesn't exist
    }
}
