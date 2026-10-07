<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$columns = DB::connection('legacy')->select('SHOW COLUMNS FROM ref_tarif_lab');
foreach ($columns as $c) {
    echo $c->Field.' | '.$c->Type.' | '.$c->Null."\n";
}
