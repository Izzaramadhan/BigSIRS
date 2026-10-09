<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$db = \Illuminate\Support\Facades\DB::connection('legacy');
echo "=== DESCRIBE ref_penanganan_lanjutan ===\n";
$schema = $db->select('DESCRIBE ref_penanganan_lanjutan');
print_r($schema);

echo "\n=== DATA ref_penanganan_lanjutan ===\n";
$data = $db->select('SELECT * FROM ref_penanganan_lanjutan');
print_r($data);

echo "\n=== FIND REFERENCES ===\n";
$tables = $db->select('SHOW TABLES');
foreach($tables as $t) {
    $var = get_object_vars($t);
    $name = array_values($var)[0];
    try {
        $cols = $db->select("DESCRIBE `$name`");
        foreach($cols as $c) {
            $colName = is_array($c) ? $c['Field'] : $c->Field;
            if (preg_match('/(penanganan|lanjut)/i', $colName) || $colName === 'id_penanganan' || $colName === 'id_penanganan_lanjutan' || $colName === 'penanganan_lanjutan_id') {
                echo "Table: $name, Column: $colName\n";
            }
        }
    } catch (\Exception $e) {}
}

