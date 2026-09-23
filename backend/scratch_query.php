<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$legacy = DB::connection('legacy');

// 1. Find tables matching patterns
$tables = $legacy->select("
    SELECT table_name 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE() 
    AND (
        table_name LIKE '%jenis_tarif%' OR 
        table_name LIKE '%tarif%' OR 
        table_name LIKE '%komponen%' OR 
        table_name LIKE '%tindakan%'
    )
");

echo "=== TABLES MATCHING PATTERNS ===\n";
foreach ($tables as $t) {
    echo $t->TABLE_NAME . "\n";
}

// 2. Identify the likely tables for Jenis Tarif and the relation to Komponen
// We will look at columns for each table to see which one fits
foreach ($tables as $t) {
    $columns = $legacy->select("SHOW COLUMNS FROM `{$t->TABLE_NAME}`");
    echo "\n=== Columns for {$t->TABLE_NAME} ===\n";
    foreach ($columns as $c) {
        echo "- {$c->Field} ({$c->Type})\n";
    }
}
