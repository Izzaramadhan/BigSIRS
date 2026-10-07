<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$c1 = DB::connection('legacy')->table('ref_item_lab')->count();
$c2 = DB::connection('legacy')->table('ref_grup_lab')->count();
echo "ref_item_lab count: $c1\n";
echo "ref_grup_lab count: $c2\n";
