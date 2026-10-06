<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$result = [];

// 1. Check duplicates in ref_tarif_tindakan
$result['duplicates'] = DB::connection('legacy')->table('ref_tarif_tindakan')
    ->select('nama', DB::raw('count(*) as c'), DB::raw('GROUP_CONCAT(id_jenis_tarif) as jenis_tarif_ids'))
    ->groupBy('nama')
    ->having('c', '>', 1)
    ->limit(5)
    ->get();

// 2. Check map_jenis_tarif_komponen structure
$result['map_jenis_tarif_komponen'] = [
    'columns' => DB::connection('legacy')->select('SHOW COLUMNS FROM map_jenis_tarif_komponen'),
    'sample' => DB::connection('legacy')->table('map_jenis_tarif_komponen')->limit(3)->get(),
];

// 3. Check trx_tindakan reference
$result['trx_tindakan'] = [
    'columns' => DB::connection('legacy')->select('SHOW COLUMNS FROM trx_tindakan'),
    'sample' => DB::connection('legacy')->table('trx_tindakan')->limit(1)->get(),
];

file_put_contents('audit2.json', json_encode($result, JSON_PRETTY_PRINT));
echo 'Done.';
