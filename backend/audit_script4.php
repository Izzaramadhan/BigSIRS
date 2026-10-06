<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$tables = ['ref_icd9', 'ref_icd_9', 'trx_icd_9', 'ref_report_group'];
$result = [];
foreach ($tables as $t) {
    try {
        $result[$t]['columns'] = DB::connection('legacy')->select("SHOW COLUMNS FROM $t");
        $result[$t]['sample'] = DB::connection('legacy')->table($t)->limit(3)->get();
    } catch (Exception $e) {
        $result[$t]['error'] = $e->getMessage();
    }
}
file_put_contents('audit_icd.json', json_encode($result, JSON_PRETTY_PRINT));
echo 'Done.';
