<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
$tables = DB::connection('legacy')->select('SHOW TABLES');
foreach($tables as $t) {
    $tableName = array_values((array)$t)[0];
    if(str_contains(strtolower($tableName), 'route') || 
       str_contains(strtolower($tableName), 'medctx') || 
       str_contains(strtolower($tableName), 'jalur') || 
       str_contains(strtolower($tableName), 'pemberian') || 
       str_contains(strtolower($tableName), 'cara')) {
        echo $tableName . "\n";
    }
}
