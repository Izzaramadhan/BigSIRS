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
    AND table_name LIKE 'ref_%'
");

$names = [];
foreach ($tables as $table) {
    $names[] = $table->table_name ?? $table->TABLE_NAME;
}

echo implode("\n", $names);
