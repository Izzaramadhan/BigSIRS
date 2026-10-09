<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$tables = DB::connection('legacy')->select('SHOW TABLES');
foreach ($tables as $t) {
    $name = array_values((array) $t)[0];
    if (preg_match('/gudang|depo|apotek|warehouse/i', $name)) {
        echo "Found table: $name\n";
    }
}
