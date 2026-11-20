<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
$conn = DB::connection('legacy');

echo "=== formulir_general_consent ===\n";
$cols = $conn->select("SHOW COLUMNS FROM formulir_general_consent");
foreach ($cols as $c) {
    echo "{$c->Field} ({$c->Type})\n";
}
$cnt = $conn->table('formulir_general_consent')->count();
echo "Count: {$cnt}\n";
