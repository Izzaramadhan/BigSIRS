<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
$tableName = 'ref_obat_jalur_masuk';

echo "=== Schema ===\n";
$columns = DB::connection('legacy')->select("DESCRIBE $tableName");
print_r($columns);

echo "\n=== Row Count ===\n";
$count = DB::connection('legacy')->table($tableName)->count();
echo "Total Rows: $count\n";

echo "\n=== Sample Data ===\n";
$data = DB::connection('legacy')->table($tableName)->limit(10)->get();
print_r($data);
