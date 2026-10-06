<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$res = DB::connection('legacy')->table('ref_tarif_tindakan')
    ->where('nama', '  Perawatan hipersensitive  ')
    ->get();
file_put_contents('audit3.json', json_encode($res, JSON_PRETTY_PRINT));
echo 'Done.';
