<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$refs = DB::connection('legacy')->select("SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME = 'ref_obat_jalur_masuk'");
print_r($refs);

$obatCols = DB::connection('legacy')->select("SHOW COLUMNS FROM ref_obat");
print_r($obatCols);

