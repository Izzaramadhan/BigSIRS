<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/api/v1/master-data/medication-signas', 'GET');
$request->headers->set('Accept', 'application/json');
// we bypass auth for the test by using withoutMiddleware, or just actingAs
$user = App\Models\User::first();
if ($user) {
    $app->make('auth')->guard('sanctum')->setUser($user);
}
$response = $kernel->handle($request);
echo $response->getContent();
