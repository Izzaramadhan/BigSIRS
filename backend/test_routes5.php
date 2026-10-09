<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = DB::connection('mysql')->select('SHOW TABLES');
foreach($tables as $t) {
    $tableName = array_values((array)$t)[0];
    $cols = DB::connection('mysql')->select("SHOW COLUMNS FROM $tableName");
    foreach ($cols as $col) {
        if ($col->Field == 'medicine_route_id') {
            echo $tableName . "\n";
        }
    }
}
