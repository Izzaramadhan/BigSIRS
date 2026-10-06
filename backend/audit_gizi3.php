<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$legacyDb = DB::connection('legacy');

$tables = $legacyDb->select("
    SELECT table_name 
    FROM information_schema.tables 
    WHERE table_schema = 'simrs_legacy_full' 
    AND (table_name LIKE '%asuhan%')
");

echo "=== TABLES ===\n";
foreach ($tables as $table) {
    $tableName = $table->table_name ?? $table->TABLE_NAME;
    echo '- '.$tableName."\n";

    // Dump some data from it if it's a master table
    if (strpos($tableName, 'ref_') !== false || strpos($tableName, 'trx_') === false) {
        try {
            $count = $legacyDb->table($tableName)->count();
            echo "  Rows: $count\n";
            $sample = $legacyDb->table($tableName)->limit(3)->get();
            print_r($sample);
        } catch (Exception $e) {
        }
    }
}
