<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$checkTables = ['trx_pemeriksaan', 'trx_vital_sign', 'trx_diagnosa', 'trx_tindakan', 'trx_resep', 'trx_billing'];

foreach ($checkTables as $t) {
    echo "=== COLUMNS: $t ===\n";
    $cols = Illuminate\Support\Facades\Schema::connection('legacy')->getColumnListing($t);
    echo implode(', ', $cols) . "\n\n";
}
