<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$legacy = DB::connection('legacy');

echo "=== PROFIL MASTER JENIS TARIF (ref_jenis_tarif) ===\n";

// Total record
$totalMaster = $legacy->table('ref_jenis_tarif')->count();
echo "Total record: $totalMaster\n";

// nama kosong
$namaKosong = $legacy->table('ref_jenis_tarif')->whereNull('nama')->orWhere('nama', '')->count();
echo "Nama kosong: $namaKosong\n";

// kode kosong
$kodeKosong = $legacy->table('ref_jenis_tarif')->whereNull('kode')->orWhere('kode', '')->count();
echo "Kode kosong: $kodeKosong\n";

// kode duplikat
$kodeDuplikat = $legacy->table('ref_jenis_tarif')->select(DB::raw("kode, count(*) as count"))->whereNotNull('kode')->where('kode', '!=', '')->groupBy('kode')->havingRaw('count(*) > 1')->get();
echo "Kode duplikat: " . count($kodeDuplikat) . "\n";

// nama duplikat exact
$namaDuplikatExact = $legacy->table('ref_jenis_tarif')->select(DB::raw("nama, count(*) as count"))->whereNotNull('nama')->where('nama', '!=', '')->groupBy('nama')->havingRaw('count(*) > 1')->get();
echo "Nama duplikat exact: " . count($namaDuplikatExact) . "\n";

// nama duplikat case-insensitive
$namaDuplikatCI = $legacy->table('ref_jenis_tarif')->select(DB::raw("LOWER(nama) as lower_nama, count(*) as count"))->whereNotNull('nama')->where('nama', '!=', '')->groupBy(DB::raw("LOWER(nama)"))->havingRaw('count(*) > 1')->get();
echo "Nama duplikat case-insensitive: " . count($namaDuplikatCI) . "\n";

// deskripsi kosong
$descKosong = $legacy->table('ref_jenis_tarif')->whereNull('deskripsi')->orWhere('deskripsi', '')->count();
echo "Deskripsi kosong: $descKosong\n";

// status aktif/nonaktif
$statusAktif = $legacy->table('ref_jenis_tarif')->where('status', 1)->count();
$statusNonaktif = $legacy->table('ref_jenis_tarif')->where('status', 0)->count();
$statusOther = $legacy->table('ref_jenis_tarif')->whereNotIn('status', [0, 1])->count();
echo "Status aktif (1): $statusAktif, Nonaktif (0): $statusNonaktif, Other: $statusOther\n";

// soft-deleted
$softDeleted = $legacy->table('ref_jenis_tarif')->whereNotNull('deleted_at')->where('deleted_at', '!=', '0000-00-00 00:00:00')->count();
echo "Soft-deleted (actual date): $softDeleted\n";

// zero-date deleted_at
$zeroDate = $legacy->table('ref_jenis_tarif')->where('deleted_at', '0000-00-00 00:00:00')->orWhere('deleted_at', '0000-00-00')->count();
echo "Zero-date deleted_at: $zeroDate\n";
$nullDate = $legacy->table('ref_jenis_tarif')->whereNull('deleted_at')->count();
echo "NULL deleted_at: $nullDate\n";


echo "\n=== PROFIL RELASI KOMPONEN (map_jenis_tarif_komponen) ===\n";

// Total baris relasi
$totalRelasi = $legacy->table('map_jenis_tarif_komponen')->count();
echo "Total baris relasi: $totalRelasi\n";

// orphan id_jenis_tarif
$orphanJT = $legacy->table('map_jenis_tarif_komponen as m')
    ->leftJoin('ref_jenis_tarif as j', 'm.id_jenis_tarif', '=', 'j.id')
    ->whereNull('j.id')
    ->count();
echo "Orphan id_jenis_tarif: $orphanJT\n";

// orphan id_komponen (not in ref_komponen)
$orphanKomponen = $legacy->table('map_jenis_tarif_komponen as m')
    ->leftJoin('ref_komponen as k', 'm.id_komponen', '=', 'k.id')
    ->whereNull('k.id')
    ->count();
echo "Orphan id_komponen: $orphanKomponen\n";

// pasangan duplikat
$pasanganDuplikat = $legacy->table('map_jenis_tarif_komponen')->select(DB::raw("id_jenis_tarif, id_komponen, count(*) as count"))
    ->groupBy('id_jenis_tarif', 'id_komponen')
    ->havingRaw('count(*) > 1')
    ->get();
echo "Pasangan Jenis Tarif-Komponen duplikat: " . count($pasanganDuplikat) . "\n";
if (count($pasanganDuplikat) > 0) {
    foreach ($pasanganDuplikat as $dup) {
        echo "   - JT: {$dup->id_jenis_tarif}, Komp: {$dup->id_komponen} ({$dup->count}x)\n";
    }
}

// Jenis Tarif tanpa komponen
$jtTanpaKomponen = $legacy->table('ref_jenis_tarif as j')
    ->leftJoin('map_jenis_tarif_komponen as m', 'j.id', '=', 'm.id_jenis_tarif')
    ->whereNull('m.id')
    ->count();
echo "Jenis Tarif tanpa Komponen: $jtTanpaKomponen\n";

// Distribusi persen
$persenNull = $legacy->table('map_jenis_tarif_komponen')->whereNull('persen')->count();
$persenNegatif = $legacy->table('map_jenis_tarif_komponen')->where('persen', '<', 0)->count();
$persenZero = $legacy->table('map_jenis_tarif_komponen')->where('persen', 0)->count();
$persen1To100 = $legacy->table('map_jenis_tarif_komponen')->whereBetween('persen', [0.0001, 100])->count();
$persenAbove100 = $legacy->table('map_jenis_tarif_komponen')->where('persen', '>', 100)->count();
$persen5000 = $legacy->table('map_jenis_tarif_komponen')->where('persen', 5000)->count();

echo "Persentase distribusi:\n";
echo "- NULL: $persenNull\n";
echo "- Negatif (<0): $persenNegatif\n";
echo "- Zero (0): $persenZero\n";
echo "- 1 - 100: $persen1To100\n";
echo "- > 100: $persenAbove100\n";
echo "- Exactly 5000: $persen5000\n";

if ($persen5000 > 0 || $persenAbove100 > 0) {
    $outliers = $legacy->table('map_jenis_tarif_komponen')->where('persen', '>', 100)->get();
    echo "\nOutliers (>100):\n";
    foreach ($outliers as $o) {
        echo "- ID relasi {$o->id}: id_jenis_tarif={$o->id_jenis_tarif}, id_komponen={$o->id_komponen}, persen={$o->persen}\n";
    }
}

// Total persentase per Jenis Tarif
$totalPerJT = $legacy->table('map_jenis_tarif_komponen')->select(DB::raw("id_jenis_tarif, SUM(persen) as total_persen"))
    ->groupBy('id_jenis_tarif')
    ->get();

$kurangDari100 = 0;
$samaDengan100 = 0;
$lebihDari100 = 0;

foreach ($totalPerJT as $row) {
    if ($row->total_persen < 100) $kurangDari100++;
    elseif ($row->total_persen == 100) $samaDengan100++;
    else $lebihDari100++;
}

echo "\nJenis Tarif berdasarkan total persentase:\n";
echo "- Kurang dari 100: $kurangDari100\n";
echo "- Sama dengan 100: $samaDengan100\n";
echo "- Lebih dari 100: $lebihDari100\n";

if ($lebihDari100 > 0) {
    echo "\nContoh Jenis Tarif total > 100:\n";
    foreach ($totalPerJT as $row) {
        if ($row->total_persen > 100) {
            echo "- id_jenis_tarif {$row->id_jenis_tarif}: total_persen = {$row->total_persen}\n";
        }
    }
}

// Check tabel tarif yang mereferensikan Jenis Tarif (e.g., ref_tarif_tindakan)
$tarifTindakanCount = $legacy->table('ref_tarif_tindakan')->count();
$tarifTindakanWithJT = $legacy->table('ref_tarif_tindakan')->whereNotNull('id_jenis_tarif')->where('id_jenis_tarif', '!=', 0)->count();
echo "\n=== REFERENSI LAIN ===\n";
echo "Total ref_tarif_tindakan: $tarifTindakanCount\n";
echo "ref_tarif_tindakan yang mereferensikan id_jenis_tarif: $tarifTindakanWithJT\n";

