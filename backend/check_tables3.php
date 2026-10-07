<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$tables = ['ref_form_item', 'map_form_item', 'ref_item_laboratorium'];
foreach ($tables as $table) {
    try {
        $columns = DB::connection('legacy')->select("SHOW COLUMNS FROM $table");
        echo "COLUMNS FOR $table:\n";
        foreach ($columns as $c) {
            echo $c->Field.' | '.$c->Type.' | '.$c->Null."\n";
        }
        $data = DB::connection('legacy')->table($table)->limit(1)->get();
        echo "\nDATA FOR $table:\n";
        print_r($data->toArray());
    } catch (Exception $e) {
        echo "Table $table does not exist or error.\n";
    }
}
