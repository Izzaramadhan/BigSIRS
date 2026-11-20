<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = [
    'ref_pasien',
    'master_person_index',
    'trx_admisi',
    'trx_visit',
    'trx_pemeriksaan',
    'trx_vital_sign',
    'trx_diagnosa',
    'trx_tindakan',
    'trx_resep',
    'trx_resep_detail',
    'trx_lab',
    'trx_billing',
    'trx_billing_detail',
    'ref_diagnosa',
    'ref_tarif_tindakan',
    'ref_obat',
    'ref_poliklinik',
    'ref_dokter'
];

echo "=== RECORD COUNTS IN SIMRS_LEGACY ===\n";
foreach ($tables as $t) {
    try {
        $count = Illuminate\Support\Facades\DB::connection('legacy')->table($t)->count();
        echo sprintf("%-25s : %d rows\n", $t, $count);
    } catch (\Exception $e) {
        echo sprintf("%-25s : ERROR (%s)\n", $t, $e->getMessage());
    }
}
