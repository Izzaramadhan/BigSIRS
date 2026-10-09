<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$polyclinics = DB::connection('legacy')->table('ref_poliklinik')->whereNotNull('id_gudang')->get(['id', 'nama', 'id_gudang']);
echo "Polyclinics with id_gudang:\n";
print_r($polyclinics->toArray());

$stok = DB::connection('legacy')->table('trx_stok_instrumen_operasi')->first();
echo "Stok table exists?\n";
print_r($stok);
