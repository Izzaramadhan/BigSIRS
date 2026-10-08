<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/api/v1/master-data/radiology-items', 'GET');
$controller = app(App\Http\Controllers\Api\V1\MasterData\RadiologyItemController::class);

try {
    $response = $controller->index($request);
    echo json_encode($response->response()->getData(true), JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
