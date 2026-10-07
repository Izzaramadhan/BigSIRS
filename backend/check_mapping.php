<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$items_with_multiple_groups = DB::connection('legacy')->select('
    SELECT id_item, COUNT(id_grup) as c 
    FROM map_grup_item_lab 
    GROUP BY id_item 
    HAVING c > 1
');

echo 'Items with multiple groups: '.count($items_with_multiple_groups)."\n";
if (count($items_with_multiple_groups) > 0) {
    print_r($items_with_multiple_groups);
}

$items_without_groups = DB::connection('legacy')->select('
    SELECT id 
    FROM ref_item_lab 
    WHERE id NOT IN (SELECT id_item FROM map_grup_item_lab)
');
echo 'Items without groups: '.count($items_without_groups)."\n";
