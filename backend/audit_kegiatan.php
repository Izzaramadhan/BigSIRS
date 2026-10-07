<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$legacyDb = DB::connection('legacy');

$tables = $legacyDb->select('SHOW TABLES');

$matches = [];
foreach ($tables as $table) {
    $vars = get_object_vars($table);
    $tableName = array_values($vars)[0];

    if (str_contains($tableName, 'kegiatan') || str_contains($tableName, 'jenis') || str_contains($tableName, 'activity')) {
        $columns = $legacyDb->select("SHOW COLUMNS FROM {$tableName}");
        $colNames = array_map(fn ($c) => $c->Field, $columns);

        $count = $legacyDb->table($tableName)->count();

        $matches[] = [
            'table' => $tableName,
            'count' => $count,
            'columns' => implode(', ', $colNames),
        ];
    }
}

echo "Found matching tables:\n";
foreach ($matches as $match) {
    echo "- {$match['table']} ({$match['count']} rows)\n";
    echo "  Columns: {$match['columns']}\n";

    // Sample if it looks promising
    if ($match['count'] > 0 && $match['count'] < 500) {
        $sample = $legacyDb->table($match['table'])->limit(3)->get();
        echo '  Sample: '.json_encode($sample)."\n";
    }

    // Specially look for 'Radiologi'
    if (in_array('nama', explode(', ', $match['columns']))) {
        $radio = $legacyDb->table($match['table'])->where('nama', 'like', '%Radiologi%')->first();
        if ($radio) {
            echo "  ** FOUND 'Radiologi' here: ".json_encode($radio)."\n";
        }
    }
}
