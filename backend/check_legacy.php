<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$columns = DB::connection('legacy')->select('SHOW COLUMNS FROM map_grup_kelompok_rad');
echo "COLUMNS map_grup_kelompok_rad:\n";
print_r($columns);

$data = DB::connection('legacy')->table('map_grup_kelompok_rad')->get();
echo "\nDATA map_grup_kelompok_rad:\n";
print_r($data);
