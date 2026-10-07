<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$columns = DB::connection('legacy')->select("
    SELECT TABLE_NAME, COLUMN_NAME 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'simrs_legacy_full' 
    AND (COLUMN_NAME LIKE '%kategori%' OR COLUMN_NAME LIKE '%kategori_lab%')
");
foreach ($columns as $c) {
    echo $c->TABLE_NAME.' -> '.$c->COLUMN_NAME."\n";
}
