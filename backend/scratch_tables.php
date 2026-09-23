<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$legacy = DB::connection('legacy');

$softDeleted = $legacy->table('ref_komponen')->whereNotNull('deleted_at')->pluck('deleted_at');
echo "deleted_at values:\n";
foreach ($softDeleted as $d) {
    echo "- '{$d}'\n";
}
