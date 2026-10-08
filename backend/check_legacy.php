<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$columns = \DB::connection('legacy')->select("SHOW COLUMNS FROM ref_tipe_rad");
echo "COLUMNS:\n";
print_r($columns);

$data = \DB::connection('legacy')->table('ref_tipe_rad')->get();
echo "\nDATA:\n";
print_r($data);
