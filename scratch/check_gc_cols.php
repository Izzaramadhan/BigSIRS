<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (['trx_admisi', 'trx_visit'] as $t) {
    $cols = Illuminate\Support\Facades\Schema::connection('legacy')->getColumnListing($t);
    echo "$t: " . implode(', ', preg_grep('/consent|general|print|ttd|status/i', $cols)) . "\n";
}
