<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$rows = DB::connection('legacy')->select('SELECT * FROM ref_jenis_surat');
echo json_encode($rows, JSON_PRETTY_PRINT);
