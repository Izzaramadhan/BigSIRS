<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$conn = DB::connection('legacy');

echo "=== COLUMNS IN trx_antrian ===\n";
$cols = $conn->select("SHOW COLUMNS FROM trx_antrian");
foreach ($cols as $c) {
    echo "{$c->Field} ({$c->Type})\n";
}

echo "\n=== SAMPLE LATEST trx_antrian ===\n";
$latest = $conn->table('trx_antrian')->orderByDesc('id')->limit(5)->get();
print_r($latest);
