<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$tables = DB::connection('legacy')->select('SHOW TABLES');
$candidates = [];
foreach ($tables as $t) {
    $name = (array) $t;
    $name = array_values($name)[0];
    if (strpos($name, 'lab') !== false || strpos($name, 'item') !== false || strpos($name, 'periksa') !== false || strpos($name, 'pemeriksaan') !== false || strpos($name, 'ref_') === 0) {
        $candidates[] = $name;
    }
}

foreach ($candidates as $table) {
    $columns = DB::connection('legacy')->select("SHOW COLUMNS FROM $table");
    $hasKategori = false;
    $hasSatuan = false;
    foreach ($columns as $c) {
        if (strpos($c->Field, 'kategori') !== false) {
            $hasKategori = true;
        }
        if (strpos($c->Field, 'satuan') !== false) {
            $hasSatuan = true;
        }
    }
    if ($hasKategori || $hasSatuan) {
        echo "$table hasKategori: $hasKategori, hasSatuan: $hasSatuan\n";
    }
}
