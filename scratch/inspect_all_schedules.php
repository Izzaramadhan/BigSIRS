<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$schedules = DB::connection('legacy')->select("
    SELECT j.id, j.id_dokter, j.id_poliklinik, j.hari_praktik, j.index_hari, j.jam_mulai, j.jam_selesai, j.status, j.libur,
           p.nama as poli_name, m.nama as doc_name
    FROM trx_jadwal_dokter j
    LEFT JOIN ref_poliklinik p ON j.id_poliklinik = p.id
    LEFT JOIN ref_dokter d ON j.id_dokter = d.id
    LEFT JOIN master_person_index m ON d.id_mpi = m.id
    ORDER BY j.id_poliklinik, j.index_hari
");

foreach ($schedules as $s) {
    echo "Poli [{$s->id_poliklinik}] {$s->poli_name} | Doc [{$s->id_dokter}] {$s->doc_name} | Hari: {$s->hari_praktik} (Idx: {$s->index_hari}) | Jam: {$s->jam_mulai} - {$s->jam_selesai} | Stat: {$s->status} | Libur: {$s->libur}\n";
}
