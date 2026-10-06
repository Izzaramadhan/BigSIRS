<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$legacyDb = DB::connection('legacy');

// 1. Audit relational chain
echo "Tugas 1: Audit Relasi Faktual\n";
echo "=============================\n";

$legacyMappingTable = 'map_ref_tindakan_user';
$legacyEmployeeTable = 'ref_pegawai';
$legacyUserTable = 'tbl_user';

$sampleLegacyMappings = $legacyDb->table($legacyMappingTable)->limit(5)->get();
echo "Sample mapping legacy:\n";
print_r($sampleLegacyMappings);

echo "Structure of ref_pegawai:\n";
$pegawaiSample = $legacyDb->table('ref_pegawai')->first();
print_r($pegawaiSample);

echo "Structure of tbl_user:\n";
$userSample = $legacyDb->table('tbl_user')->first();
print_r($userSample);

// Find Person 1694 in tbl_user
echo "Checking jabatan for id_jabatan = 6...\n";
$jabatan = $legacyDb->table('ref_jabatan')->where('id', 6)->first();
print_r($jabatan);

echo "Checking mappings for id_aktor = 2...\n";
$mappings = $legacyDb->table('map_ref_tindakan_user')->where('id_aktor', 2)->get();
print_r($mappings);
