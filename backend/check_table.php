<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$columns = DB::connection('legacy')->select('SHOW COLUMNS FROM ref_item_lab');
echo "COLUMNS:\n";
foreach ($columns as $c) {
    echo $c->Field.' | '.$c->Type.' | '.$c->Null."\n";
}

$data = DB::connection('legacy')->table('ref_item_lab')->limit(5)->get();
echo "\nDATA:\n";
print_r($data->toArray());
