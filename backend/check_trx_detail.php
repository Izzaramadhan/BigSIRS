<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$columns = DB::connection('legacy')->select('SHOW COLUMNS FROM trx_lab_detail_pemeriksaan');
foreach ($columns as $c) {
    echo $c->Field.' | '.$c->Type.' | '.$c->Null."\n";
}
