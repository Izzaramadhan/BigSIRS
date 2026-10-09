<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$samples = DB::connection('legacy')->table('trx_resep_detail')
    ->whereNotNull('signa')
    ->where('signa', '!=', '')
    ->select('signa')
    ->limit(10)
    ->get();

echo json_encode($samples);
