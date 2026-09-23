<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CaptchaController;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('captcha', [CaptchaController::class, 'generate']);
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
        
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('master-data')->group(function () {
            Route::get('polyclinics/service-types', [\App\Http\Controllers\Api\V1\MasterData\PolyclinicController::class, 'serviceTypes']);
            Route::patch('polyclinics/{polyclinic}/status', [\App\Http\Controllers\Api\V1\MasterData\PolyclinicController::class, 'status']);
            Route::apiResource('polyclinics', \App\Http\Controllers\Api\V1\MasterData\PolyclinicController::class);

            Route::patch('guarantors/{guarantor}/status', [\App\Http\Controllers\Api\V1\MasterData\GuarantorController::class, 'status']);
            Route::apiResource('guarantors', \App\Http\Controllers\Api\V1\MasterData\GuarantorController::class);

            Route::patch('procedure-categories/{procedureCategory}/status', [\App\Http\Controllers\Api\V1\MasterData\ProcedureCategoryController::class, 'status']);
            Route::apiResource('procedure-categories', \App\Http\Controllers\Api\V1\MasterData\ProcedureCategoryController::class);

            Route::patch('tariff-components/{tariffComponent}/status', [\App\Http\Controllers\Api\V1\MasterData\TariffComponentController::class, 'updateStatus']);
            Route::apiResource('tariff-components', \App\Http\Controllers\Api\V1\MasterData\TariffComponentController::class);

            Route::patch('tariff-types/{tariffType}/status', [\App\Http\Controllers\Api\V1\MasterData\TariffTypeController::class, 'updateStatus']);
            Route::apiResource('tariff-types', \App\Http\Controllers\Api\V1\MasterData\TariffTypeController::class);
        });
    });
});
