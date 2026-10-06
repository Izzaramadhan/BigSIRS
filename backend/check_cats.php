<?php

require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$legacy = DB::connection('legacy');
$tindakanCategories = $legacy->table('ref_tarif_tindakan')->distinct()->pluck('id_kategori')->toArray();
$actualCategories = $legacy->table('ref_kategori_tindakan')->pluck('id')->toArray();

$missing = array_diff($tindakanCategories, $actualCategories);
echo 'Missing category IDs in ref_tarif_tindakan: '.implode(', ', $missing)."\n";
