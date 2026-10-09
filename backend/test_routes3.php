<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
$tableName = 'ref_obat_jalur_masuk';

$total = DB::connection('legacy')->table($tableName)->count();
$active = DB::connection('legacy')->table($tableName)->where('status', 1)->count();
$inactive = DB::connection('legacy')->table($tableName)->where('status', 0)->count();
$softDeleted = DB::connection('legacy')->table($tableName)->whereNotNull('delete_time')->count();

$emptyName = DB::connection('legacy')->table($tableName)->whereNull('rute_obat')->orWhere('rute_obat', '')->count();
$emptyObj = DB::connection('legacy')->table($tableName)->whereNull('obj_id')->orWhere('obj_id', '')->count();
$dashObj = DB::connection('legacy')->table($tableName)->where('obj_id', '-')->count();

$duplicates = DB::connection('legacy')->select("SELECT rute_obat, COUNT(*) as cnt FROM $tableName GROUP BY rute_obat HAVING cnt > 1");
$duplicateObjs = DB::connection('legacy')->select("SELECT obj_id, COUNT(*) as cnt FROM $tableName WHERE obj_id IS NOT NULL AND obj_id != '' AND obj_id != '-' GROUP BY obj_id HAVING cnt > 1");

echo "Total: $total\n";
echo "Active: $active\n";
echo "Inactive: $inactive\n";
echo "Soft Deleted: $softDeleted\n";
echo "Empty Name: $emptyName\n";
echo "Empty Obj: $emptyObj\n";
echo "Dash Obj: $dashObj\n";

echo "Duplicate Names:\n";
print_r($duplicates);

echo "Duplicate Objs:\n";
print_r($duplicateObjs);
