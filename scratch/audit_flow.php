<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$conn = DB::connection('legacy');

echo "=== TABLES MATCHING ANTRIAN / QUEUE ===\n";
$tables = $conn->select("SHOW TABLES LIKE '%antri%'");
print_r($tables);

echo "\n=== COLUMNS IN trx_admisi ===\n";
$colsAdmisi = $conn->select("SHOW COLUMNS FROM trx_admisi");
foreach ($colsAdmisi as $c) {
    echo "{$c->Field} ({$c->Type})\n";
}

echo "\n=== COLUMNS IN trx_visit ===\n";
$colsVisit = $conn->select("SHOW COLUMNS FROM trx_visit");
foreach ($colsVisit as $c) {
    echo "{$c->Field} ({$c->Type})\n";
}

echo "\n=== COLUMNS IN ref_pasien ===\n";
$colsPasien = $conn->select("SHOW COLUMNS FROM ref_pasien");
foreach ($colsPasien as $c) {
    echo "{$c->Field} ({$c->Type})\n";
}

echo "\n=== SAMPLE LATEST trx_admisi & trx_visit ===\n";
$latestAdm = $conn->table('trx_admisi')->orderByDesc('id')->limit(2)->get();
print_r($latestAdm);

$latestVis = $conn->table('trx_visit')->orderByDesc('id')->limit(2)->get();
print_r($latestVis);
