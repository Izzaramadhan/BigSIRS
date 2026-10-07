<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$tables = DB::connection('legacy')->select('SHOW TABLES');
foreach ($tables as $t) {
    $name = (array) $t;
    $name = array_values($name)[0];
    if (stripos($name, 'lab') !== false || stripos($name, 'item') !== false || stripos($name, 'pemeriksaan') !== false) {
        echo $name."\n";
    }
}
