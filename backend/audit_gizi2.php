<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$legacyDb = DB::connection('legacy');

$tables = ['ref_jenis_diet', 'ref_nutrisi'];

foreach ($tables as $candidate) {
    try {
        $count = $legacyDb->table($candidate)->count();
        echo "Table '$candidate' exists with $count rows.\n";

        $sample = $legacyDb->table($candidate)->limit(5)->get();
        echo "Sample data:\n";
        print_r($sample);
    } catch (Exception $e) {
        echo "Error on $candidate: ".$e->getMessage()."\n";
    }
}
