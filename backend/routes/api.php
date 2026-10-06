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

            Route::patch('procedures/{procedure}/visibility', [\App\Http\Controllers\Api\V1\MasterData\MedicalProcedureController::class, 'updateVisibility']);
            Route::apiResource('procedures', \App\Http\Controllers\Api\V1\MasterData\MedicalProcedureController::class);


            Route::patch('report-groups/{reportGroup}/status', [\App\Http\Controllers\Api\V1\MasterData\ReportGroupController::class, 'updateStatus']);
            Route::apiResource('report-groups', \App\Http\Controllers\Api\V1\MasterData\ReportGroupController::class);

            Route::patch('procedure-packages/{procedurePackage}/status', [\App\Http\Controllers\Api\V1\MasterData\ProcedurePackageController::class, 'updateStatus']);
            Route::apiResource('procedure-packages', \App\Http\Controllers\Api\V1\MasterData\ProcedurePackageController::class);

            Route::apiResource('procedure-user-mappings', \App\Http\Controllers\Api\V1\MasterData\ProcedureUserMappingController::class);
            Route::patch('icd10/{icd10}/status', [\App\Http\Controllers\Api\V1\MasterData\Icd10CodeController::class, 'updateStatus']);
            Route::apiResource('icd10', \App\Http\Controllers\Api\V1\MasterData\Icd10CodeController::class);

            Route::patch('icd9-cms/{icd9_cm}/status', [\App\Http\Controllers\Api\V1\MasterData\Icd9CmController::class, 'updateStatus']);
            Route::apiResource('icd9-cms', \App\Http\Controllers\Api\V1\MasterData\Icd9CmController::class);

            Route::patch('doctors/{doctor}/status', [\App\Http\Controllers\Api\V1\MasterData\DoctorController::class, 'updateStatus']);
            Route::apiResource('doctors', \App\Http\Controllers\Api\V1\MasterData\DoctorController::class);
            
            Route::apiResource('doctor-schedules', \App\Http\Controllers\Api\V1\MasterData\DoctorScheduleController::class);

            Route::patch('educations/{education}/status', [\App\Http\Controllers\Api\V1\MasterData\EducationController::class, 'updateStatus']);
            Route::apiResource('educations', \App\Http\Controllers\Api\V1\MasterData\EducationController::class);

            Route::patch('occupations/{occupation}/status', [\App\Http\Controllers\Api\V1\MasterData\OccupationController::class, 'updateStatus']);
            Route::apiResource('occupations', \App\Http\Controllers\Api\V1\MasterData\OccupationController::class);

            Route::patch('regencies/{regency}/status', [\App\Http\Controllers\Api\V1\MasterData\RegencyController::class, 'updateStatus']);
            Route::apiResource('regencies', \App\Http\Controllers\Api\V1\MasterData\RegencyController::class);
        });

        Route::prefix('lookups')->group(function () {
            Route::get('icd9-cms', [\App\Http\Controllers\Api\V1\MasterData\Icd9CmController::class, 'index']);
            Route::get('icd9-cms/{icd9Cm}', [\App\Http\Controllers\Api\V1\MasterData\Icd9CmController::class, 'show']);
            Route::get('employees', [\App\Http\Controllers\Api\V1\LookupController::class, 'employees']);
            Route::get('icd10', [\App\Http\Controllers\Api\V1\MasterData\Icd10CodeController::class, 'index']);
            Route::get('doctors', [\App\Http\Controllers\Api\V1\MasterData\DoctorController::class, 'lookup']);
            Route::get('specializations', [\App\Http\Controllers\Api\V1\LookupController::class, 'specializations']);
            Route::get('provinces', [\App\Http\Controllers\Api\V1\LookupController::class, 'provinces']);
            Route::get('regencies', [\App\Http\Controllers\Api\V1\LookupController::class, 'regencies']);
            Route::get('districts', [\App\Http\Controllers\Api\V1\LookupController::class, 'districts']);
            Route::get('villages', [\App\Http\Controllers\Api\V1\LookupController::class, 'villages']);
            Route::get('educations', [\App\Http\Controllers\Api\V1\LookupController::class, 'educations']);
            Route::get('occupations', [\App\Http\Controllers\Api\V1\LookupController::class, 'occupations']);
        });
    });
});


Route::get('debug/doctors/{doctor}', [\App\Http\Controllers\Api\V1\MasterData\DoctorController::class, 'show']);
