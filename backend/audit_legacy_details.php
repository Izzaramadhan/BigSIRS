<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

function printTitle($title) {
    echo "\n=== $title ===\n";
}

$legacy = DB::connection('legacy');

printTitle('Count of ref_icd_9 vs ref_icd9');
$countIcd9 = $legacy->table('ref_icd_9')->count();
$countIcd9NoUnderscore = $legacy->table('ref_icd9')->count();
echo "ref_icd_9 count: $countIcd9\n";
echo "ref_icd9 count: $countIcd9NoUnderscore\n";

printTitle('Count of ref_report_group');
$countReportGroup = $legacy->table('ref_report_group')->count();
echo "ref_report_group count: $countReportGroup\n";

printTitle('Columns of ref_tarif_tindakan (Tindakan table)');
$cols = $legacy->select("SHOW COLUMNS FROM `ref_tarif_tindakan`");
foreach ($cols as $col) {
    echo " - {$col->Field} ({$col->Type})\n";
}

printTitle('ICD references in ref_tarif_tindakan');
// Let's see if there is an icd9 column
$hasIcdColumn = false;
foreach ($cols as $col) {
    if (stripos($col->Field, 'icd') !== false) {
        $hasIcdColumn = true;
        echo "Found ICD column: {$col->Field}\n";
        
        $countHasIcd = $legacy->table('ref_tarif_tindakan')->whereNotNull($col->Field)->where($col->Field, '!=', '')->count();
        $countTotal = $legacy->table('ref_tarif_tindakan')->count();
        echo "Tindakan with ICD: $countHasIcd / $countTotal\n";
        
        $distinctIcds = $legacy->table('ref_tarif_tindakan')->select($col->Field)->distinct()->get();
        echo "Distinct ICD values used: " . count($distinctIcds) . "\n";
    }
}
if (!$hasIcdColumn) {
    echo "No ICD column directly in ref_tarif_tindakan.\n";
}

printTitle('Map Tindakan Kelompok Laporan');
$countMapReport = $legacy->table('map_tindakan_kelompok_laporan')->count();
echo "map_tindakan_kelompok_laporan count: $countMapReport\n";

printTitle('Map Tarif Tindakan Poliklinik');
$countMapPoly = $legacy->table('map_tarif_tindakan_poliklinik')->count();
echo "map_tarif_tindakan_poliklinik count: $countMapPoly\n";

echo "\nDone.\n";
