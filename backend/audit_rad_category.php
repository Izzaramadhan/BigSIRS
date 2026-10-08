<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$legacyDb = \Illuminate\Support\Facades\DB::connection('legacy');

echo "ref_kategori_rad:\n";
print_r($legacyDb->select('SELECT * FROM ref_kategori_rad LIMIT 5'));

echo "\nref_kategori_radiologi:\n";
print_r($legacyDb->select('SELECT * FROM ref_kategori_radiologi LIMIT 5'));
