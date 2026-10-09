<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$columns = DB::connection('legacy')->select('SHOW COLUMNS FROM ref_gudang');
print_r($columns);

$data = DB::connection('legacy')->table('ref_gudang')->get();
print_r($data->toArray());
