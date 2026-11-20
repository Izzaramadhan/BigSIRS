<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = Illuminate\Support\Facades\DB::connection('legacy')->select('SHOW TABLES');
echo "=== CLINICAL & BILLING TABLES IN LEGACY DB ===\n";
foreach ($tables as $t) {
    $n = array_values((array)$t)[0];
    if (preg_match('/pemeriksaan|soap|diag|tindakan|resep|obat|lab|radio|bill|kasir|visit|admisi|rekam|rm|anamnesa|vital|rawat|klinik/i', $n)) {
        echo "- " . $n . "\n";
    }
}
