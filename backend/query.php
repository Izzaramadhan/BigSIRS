<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing map to ref_item_rad:\n";
$itemTest = \Illuminate\Support\Facades\DB::connection('legacy')
    ->table('map_grup_kelompok_rad as m')
    ->join('ref_item_rad as i', 'm.id_kelompok_rad', '=', 'i.id')
    ->select('m.id_grup_rad', 'i.nama')
    ->take(5)->get();
print_r($itemTest->toArray());

echo "\nTesting map to ref_kelompok_item_rad:\n";
$kelompokTest = \Illuminate\Support\Facades\DB::connection('legacy')
    ->table('map_grup_kelompok_rad as m')
    ->join('ref_kelompok_item_rad as k', 'm.id_kelompok_rad', '=', 'k.id')
    ->select('m.id_grup_rad', 'k.nama')
    ->take(5)->get();
print_r($kelompokTest->toArray());
