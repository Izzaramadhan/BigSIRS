<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$db = DB::connection('legacy');
$rows = $db->select("
    SELECT u.id, u.nama, u.status as is_active, p.id_jabatan, p.nip
    FROM tbl_user u 
    LEFT JOIN ref_pegawai p ON u.id_pegawai = p.id 
    LIMIT 5
");
print_r($rows);
