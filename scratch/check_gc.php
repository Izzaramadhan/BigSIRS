<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cols = Illuminate\Support\Facades\Schema::connection('legacy')->getColumnListing('formulir_general_consent');
echo "COLUMNS IN formulir_general_consent:\n" . implode(', ', $cols) . "\n";

$rows = Illuminate\Support\Facades\DB::connection('legacy')->table('formulir_general_consent')->limit(5)->get();
echo "COUNT: " . Illuminate\Support\Facades\DB::connection('legacy')->table('formulir_general_consent')->count() . "\n";
echo json_encode($rows, JSON_PRETTY_PRINT);
