<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$tables = ['ref_lab', 'map_grup_item_lab', 'ref_grup_lab'];
foreach ($tables as $table) {
    echo "COLUMNS FOR $table:\n";
    $columns = DB::connection('legacy')->select("SHOW COLUMNS FROM $table");
    foreach ($columns as $c) {
        echo $c->Field.' | '.$c->Type.' | '.$c->Null."\n";
    }

    $data = DB::connection('legacy')->table($table)->limit(1)->get();
    echo "\nDATA FOR $table:\n";
    print_r($data->toArray());
    echo "\n----------------------\n";
}
