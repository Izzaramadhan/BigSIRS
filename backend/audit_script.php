<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = ['ref_tarif_tindakan', 'map_tarif_tindakan_komponen', 'map_tarif_tindakan_poliklinik', 'map_tindakan_kelompok_laporan', 'ref_kategori_tindakan', 'ref_jenis_tarif', 'ref_komponen'];
$result = [];
foreach ($tables as $t) {
    try {
        $result[$t]['columns'] = DB::connection('legacy')->select("SHOW COLUMNS FROM $t");
        $result[$t]['count'] = DB::connection('legacy')->table($t)->count();
        $result[$t]['sample'] = DB::connection('legacy')->table($t)->limit(3)->get();
    } catch (\Exception $e) {
        $result[$t]['error'] = $e->getMessage();
    }
}
file_put_contents('audit.json', json_encode($result, JSON_PRETTY_PRINT));
echo "Done.";
