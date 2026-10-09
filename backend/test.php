<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

echo "Running Regression Test...\n";

// Get base URL from env or default to localhost
$baseUrl = env('APP_URL', 'http://localhost');
// Usually APIs are available internally via web server or we can just call it via local route dispatch
$request = Request::create('/api/v1/master-data/activity-types', 'GET', ['per_page' => 100]);
$response = app()->handle($request);

if ($response->getStatusCode() === 200) {
    $data = json_decode($response->getContent(), true)['data'];
    $invalidFound = false;

    foreach ($data as $item) {
        if (strpos($item['name'], 'gram') !== false || strpos($item['name'], 'Prematur') !== false) {
            echo 'FAILED: Invalid baby weight data found: '.$item['name']."\n";
            $invalidFound = true;
        }
    }

    if (! $invalidFound) {
        echo "PASSED: No baby weight data found in API response.\n";
    }

    echo 'Total valid Activity Types in first page (per_page=100): '.count($data)."\n";
} else {
    echo 'FAILED: API returned status '.$response->getStatusCode()."\n";
}
