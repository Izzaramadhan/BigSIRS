<?php

require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$legacy = DB::connection('legacy');
$tindakanCompsRaw = $legacy->table('map_tarif_tindakan_komponen')
    ->join('map_jenis_tarif_komponen', 'map_tarif_tindakan_komponen.id_map_jenis_tarif_komponen', '=', 'map_jenis_tarif_komponen.id')
    ->where(function ($q) {
        $q->whereNull('map_tarif_tindakan_komponen.deleted_at')->orWhere('map_tarif_tindakan_komponen.deleted_at', '0000-00-00 00:00:00');
    })
    ->distinct()->pluck('map_jenis_tarif_komponen.id_komponen')->toArray();

$actualComps = $legacy->table('ref_jenis_tarif_komponen')->pluck('id')->toArray();

$missing = array_diff($tindakanCompsRaw, $actualComps);
echo 'Missing component IDs in ref_tarif_tindakan: '.implode(', ', $missing)."\n";

$tindakanTypes = $legacy->table('ref_tarif_tindakan')->distinct()->pluck('id_jenis_tarif')->toArray();
$actualTypes = $legacy->table('ref_jenis_tarif')->pluck('id')->toArray();
$missingTypes = array_diff($tindakanTypes, $actualTypes);
echo 'Missing tariff type IDs: '.implode(', ', $missingTypes)."\n";
