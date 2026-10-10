<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/api/v1/master-data/procedures', 'GET', ['per_page' => 2]);
$controller = app()->make(App\Http\Controllers\Api\V1\MasterData\MedicalProcedureController::class);
$response = $controller->index($request)->toResponse($request);
echo $response->getContent();
