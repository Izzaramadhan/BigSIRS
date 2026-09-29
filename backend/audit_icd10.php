<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$legacyDb = DB::connection('legacy');
$tables = $legacyDb->select('SHOW TABLES');
echo "Searching for ICD-10 related tables in legacy DB...\n";

foreach ($tables as $t) {
    $tableName = array_values((array)$t)[0];
    if (stripos($tableName, 'icd') !== false || stripos($tableName, 'diagnos') !== false || stripos($tableName, 'penyakit') !== false) {
        echo "\nFound Table: {$tableName}\n";
        $columns = $legacyDb->select("SHOW COLUMNS FROM `{$tableName}`");
        foreach ($columns as $c) {
            echo " - {$c->Field} ({$c->Type})\n";
        }
        
        if ($tableName === 'ref_diagnosa') {
            echo "Profiling ref_diagnosa...\n";
            $total = $legacyDb->table('ref_diagnosa')->count();
            
            $nullCode = $legacyDb->table('ref_diagnosa')->whereNull('code')->orWhere('code', '')->count();
            $nullName = $legacyDb->table('ref_diagnosa')->whereNull('nama')->orWhere('nama', '')->count();
            $nullNameEn = $legacyDb->table('ref_diagnosa')->whereNull('nama_en')->orWhere('nama_en', '')->count();
            $nullDesc = $legacyDb->table('ref_diagnosa')->whereNull('deskripsi')->orWhere('deskripsi', '')->count();
            
            $duplicateCodes = $legacyDb->select("SELECT TRIM(code) as trimmed_code, COUNT(*) as c FROM ref_diagnosa GROUP BY TRIM(code) HAVING c > 1");
            
            $inacbgCode = $legacyDb->table('ref_diagnosa')->whereNotNull('kode_inacbg')->where('kode_inacbg', '!=', '')->count();
            $inacbgDesc = $legacyDb->table('ref_diagnosa')->whereNotNull('deskripsi_inacbg')->where('deskripsi_inacbg', '!=', '')->count();
            
            $class1 = $legacyDb->table('ref_diagnosa')->whereNotNull('tarif_kelas1')->where('tarif_kelas1', '>', 0)->count();
            $class2 = $legacyDb->table('ref_diagnosa')->whereNotNull('tarif_kelas2')->where('tarif_kelas2', '>', 0)->count();
            $class3 = $legacyDb->table('ref_diagnosa')->whereNotNull('tarif_kelas3')->where('tarif_kelas3', '>', 0)->count();
            
            $historyYes = $legacyDb->table('ref_diagnosa')->where('riwayat_penyakit', '1')->count();
            $historyNo = $legacyDb->table('ref_diagnosa')->where('riwayat_penyakit', '0')->count();
            
            $softDeleted = $legacyDb->table('ref_diagnosa')->whereNotNull('deleted_at')->where('deleted_at', '!=', '0000-00-00 00:00:00')->count();
            $active = $legacyDb->table('ref_diagnosa')->where('status', '1')->count();
            $inactive = $legacyDb->table('ref_diagnosa')->where('status', '0')->count();
            
            echo "Total: {$total}\n";
            echo "Null/Empty Code: {$nullCode}\n";
            echo "Null/Empty Name: {$nullName}\n";
            echo "Null/Empty English Name: {$nullNameEn}\n";
            echo "Null/Empty Desc: {$nullDesc}\n";
            echo "Duplicate Codes: " . count($duplicateCodes) . "\n";
            if (count($duplicateCodes) > 0) {
                echo " Example duplicate: " . $duplicateCodes[0]->trimmed_code . " (" . $duplicateCodes[0]->c . " times)\n";
            }
            
            echo "With INACBG Code: {$inacbgCode}\n";
            echo "With INACBG Desc: {$inacbgDesc}\n";
            echo "With Tarif 1: {$class1}, Tarif 2: {$class2}, Tarif 3: {$class3}\n";
            
            echo "Riwayat Penyakit 1: {$historyYes}, 0: {$historyNo}\n";
            echo "Soft Deleted: {$softDeleted}\n";
            echo "Active (1): {$active}, Inactive (0): {$inactive}\n";
            
            $anomalies = $legacyDb->select("SELECT * FROM ref_diagnosa WHERE code LIKE '%-%' LIMIT 5");
            echo "Category/Range Code patterns:\n";
            foreach($anomalies as $a) {
                echo " - " . $a->code . "\n";
            }
        }

    }
}
