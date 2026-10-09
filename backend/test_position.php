<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$controller = $app->make('App\Http\Controllers\Api\V1\MasterData\PositionController');
$request = Request::create('/api', 'GET');
$response = $controller->index($request);
echo json_encode($response->toResponse($request)->getData(true), JSON_PRETTY_PRINT);
