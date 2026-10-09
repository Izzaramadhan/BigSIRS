<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$legacyDb = \Illuminate\Support\Facades\DB::connection('legacy');

$anomaly = $legacyDb->select("
    SELECT h.id as paket_id, h.nama as nama_paket, h.harga as harga_paket, h.jumlah as jumlah_paket, 
           d.id_ref_obat, d.jumlah as qty, d.sub_total, o.nama as nama_obat, o.harga_beli, o.harga_jual
    FROM ref_paket_obat h
    LEFT JOIN map_paket_obat d ON h.id = d.id_ref_paket_obat
    LEFT JOIN ref_obat o ON d.id_ref_obat = o.id
    WHERE h.deleted_at IS NULL AND d.deleted_at IS NULL
");
echo json_encode($anomaly, JSON_PRETTY_PRINT) . "\n\n";

$columns = $legacyDb->select("SHOW COLUMNS FROM map_paket_obat");
foreach ($columns as $col) {
    echo " - {$col->Field} ({$col->Type})\n";
}
