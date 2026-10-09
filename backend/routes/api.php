<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CaptchaController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\MasterData\ActivityTypeController;
use App\Http\Controllers\Api\V1\MasterData\DietTypeController;
use App\Http\Controllers\Api\V1\MasterData\DistrictController;
use App\Http\Controllers\Api\V1\MasterData\DoctorController;
use App\Http\Controllers\Api\V1\MasterData\DoctorScheduleController;
use App\Http\Controllers\Api\V1\MasterData\EducationController;
use App\Http\Controllers\Api\V1\MasterData\EmployeeController;
use App\Http\Controllers\Api\V1\MasterData\GuarantorController;
use App\Http\Controllers\Api\V1\MasterData\Icd10CodeController;
use App\Http\Controllers\Api\V1\MasterData\Icd9CmController;
use App\Http\Controllers\Api\V1\MasterData\LaboratoryCategoryController;
use App\Http\Controllers\Api\V1\MasterData\LaboratoryGroupController;
use App\Http\Controllers\Api\V1\MasterData\LaboratoryItemController;
use App\Http\Controllers\Api\V1\MasterData\LetterTypeController;
use App\Http\Controllers\Api\V1\MasterData\MedicalProcedureController;
use App\Http\Controllers\Api\V1\MasterData\MedicineCategoryController;
use App\Http\Controllers\Api\V1\MasterData\MedicineController;
use App\Http\Controllers\Api\V1\MasterData\MedicationSignaController;
use App\Http\Controllers\Api\V1\MasterData\MedicineRouteController;
use App\Http\Controllers\Api\V1\MasterData\MedicineUnitController;
use App\Http\Controllers\Api\V1\MasterData\MedicinePackageController;
use App\Http\Controllers\Api\V1\MasterData\OccupationController;
use App\Http\Controllers\Api\V1\MasterData\PolyclinicController;
use App\Http\Controllers\Api\V1\MasterData\PositionController;
use App\Http\Controllers\Api\V1\MasterData\ProcedureCategoryController;
use App\Http\Controllers\Api\V1\MasterData\ProcedurePackageController;
use App\Http\Controllers\Api\V1\MasterData\ProcedureUserMappingController;
use App\Http\Controllers\Api\V1\MasterData\RadiologyCategoryController;
use App\Http\Controllers\Api\V1\MasterData\RadiologyGroupController;
use App\Http\Controllers\Api\V1\MasterData\RadiologyItemController;
use App\Http\Controllers\Api\V1\MasterData\RadiologyItemGroupController;
use App\Http\Controllers\Api\V1\MasterData\RadiologyTypeController;
use App\Http\Controllers\Api\V1\MasterData\RegencyController;
use App\Http\Controllers\Api\V1\MasterData\ReportGroupController;
use App\Http\Controllers\Api\V1\MasterData\ServiceTypeController;
use App\Http\Controllers\Api\V1\MasterData\SupplierController;
use App\Http\Controllers\Api\V1\MasterData\TariffComponentController;
use App\Http\Controllers\Api\V1\MasterData\TariffTypeController;
use App\Http\Controllers\Api\V1\MasterData\VillageController;
use App\Http\Controllers\Api\V1\MasterData\WarehouseController;
use Illuminate\Support\Facades\Route;

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
            Route::get('polyclinics/service-types', [PolyclinicController::class, 'serviceTypes']);
            Route::patch('polyclinics/{polyclinic}/status', [PolyclinicController::class, 'status']);
            Route::apiResource('polyclinics', PolyclinicController::class);

            Route::patch('guarantors/{guarantor}/status', [GuarantorController::class, 'status']);
            Route::apiResource('guarantors', GuarantorController::class);

            Route::patch('procedure-categories/{procedureCategory}/status', [ProcedureCategoryController::class, 'status']);
            Route::apiResource('procedure-categories', ProcedureCategoryController::class);

            Route::patch('tariff-components/{tariffComponent}/status', [TariffComponentController::class, 'updateStatus']);
            Route::apiResource('tariff-components', TariffComponentController::class);

            Route::patch('tariff-types/{tariffType}/status', [TariffTypeController::class, 'updateStatus']);
            Route::apiResource('tariff-types', TariffTypeController::class);

            Route::patch('procedures/{procedure}/visibility', [MedicalProcedureController::class, 'updateVisibility']);
            Route::apiResource('procedures', MedicalProcedureController::class);

            Route::patch('report-groups/{reportGroup}/status', [ReportGroupController::class, 'updateStatus']);
            Route::apiResource('report-groups', ReportGroupController::class);

            Route::patch('procedure-packages/{procedurePackage}/status', [ProcedurePackageController::class, 'updateStatus']);
            Route::apiResource('procedure-packages', ProcedurePackageController::class);

            Route::apiResource('procedure-user-mappings', ProcedureUserMappingController::class);
            Route::patch('icd10/{icd10}/status', [Icd10CodeController::class, 'updateStatus']);
            Route::apiResource('icd10', Icd10CodeController::class);

            Route::patch('icd9-cms/{icd9_cm}/status', [Icd9CmController::class, 'updateStatus']);
            Route::apiResource('icd9-cms', Icd9CmController::class);

            Route::patch('doctors/{doctor}/status', [DoctorController::class, 'updateStatus']);
            Route::apiResource('doctors', DoctorController::class);

            Route::apiResource('doctor-schedules', DoctorScheduleController::class);

            Route::patch('educations/{education}/status', [EducationController::class, 'updateStatus']);
            Route::apiResource('educations', EducationController::class);

            Route::patch('occupations/{occupation}/status', [OccupationController::class, 'updateStatus']);
            Route::apiResource('occupations', OccupationController::class);

            Route::patch('regencies/{regency}/status', [RegencyController::class, 'updateStatus']);
            Route::apiResource('regencies', RegencyController::class);

            Route::patch('districts/{district}/status', [DistrictController::class, 'updateStatus']);
            Route::apiResource('districts', DistrictController::class);

            Route::patch('villages/{village}/status', [VillageController::class, 'updateStatus']);
            Route::apiResource('villages', VillageController::class);

            Route::patch('service-types/{service_type}/status', [ServiceTypeController::class, 'updateStatus']);
            Route::apiResource('service-types', ServiceTypeController::class);

            Route::patch('diet-types/{diet_type}/status', [DietTypeController::class, 'updateStatus']);
            Route::apiResource('diet-types', DietTypeController::class);

            Route::get('activity-types/lookup', [ActivityTypeController::class, 'lookup']);
            Route::patch('activity-types/{activity_type}/status', [ActivityTypeController::class, 'updateStatus']);
            Route::apiResource('activity-types', ActivityTypeController::class);

            Route::patch('letter-types/{letter_type}/status', [LetterTypeController::class, 'updateStatus']);
            Route::apiResource('letter-types', LetterTypeController::class);

            Route::patch('employees/{employee}/status', [EmployeeController::class, 'updateStatus']);
            Route::apiResource('employees', EmployeeController::class);

            Route::apiResource('positions', PositionController::class);

            Route::patch('laboratory-categories/{laboratory_category}/status', [LaboratoryCategoryController::class, 'updateStatus']);
            Route::apiResource('laboratory-categories', LaboratoryCategoryController::class);

            Route::apiResource('laboratory-items', LaboratoryItemController::class);

            Route::apiResource('laboratory-groups', LaboratoryGroupController::class);

            Route::apiResource('radiology-categories', RadiologyCategoryController::class);
            Route::patch('radiology-types/{radiology_type}/status', [RadiologyTypeController::class, 'updateStatus']);
            Route::apiResource('radiology-types', RadiologyTypeController::class);
            Route::patch('radiology-item-groups/{radiology_item_group}/status', [RadiologyItemGroupController::class, 'updateStatus']);
            Route::apiResource('radiology-item-groups', RadiologyItemGroupController::class);
            Route::patch('radiology-items/{radiology_item}/status', [RadiologyItemController::class, 'updateStatus']);
            Route::apiResource('radiology-items', RadiologyItemController::class);

            Route::patch('radiology-groups/{radiology_group}/status', [RadiologyGroupController::class, 'updateStatus']);
            Route::apiResource('radiology-groups', RadiologyGroupController::class);

            Route::patch('medicine-categories/{medicine_category}/status', [MedicineCategoryController::class, 'updateStatus']);
            Route::apiResource('medicine-categories', MedicineCategoryController::class);
            Route::patch('medicine-units/{medicine_unit}/status', [MedicineUnitController::class, 'updateStatus']);
            Route::apiResource('medicine-units', MedicineUnitController::class);
            Route::patch('medicine-routes/{medicine_route}/status', [MedicineRouteController::class, 'updateStatus']);
            Route::apiResource('medicine-routes', MedicineRouteController::class);
            Route::patch('medication-signas/{medication_signa}/status', [MedicationSignaController::class, 'updateStatus']);
            Route::apiResource('medication-signas', MedicationSignaController::class);
            Route::apiResource('suppliers', SupplierController::class);
            Route::apiResource('warehouses', WarehouseController::class);

            Route::patch('medicines/{medicine}/status', [MedicineController::class, 'updateStatus']);
            Route::apiResource('medicines', MedicineController::class);
            Route::apiResource('medicine-packages', MedicinePackageController::class);
        });

        Route::prefix('lookups')->group(function () {
            Route::get('icd9-cms', [Icd9CmController::class, 'index']);
            Route::get('icd9-cms/{icd9Cm}', [Icd9CmController::class, 'show']);
            Route::get('employees', [LookupController::class, 'employees']);
            Route::get('icd10', [Icd10CodeController::class, 'index']);
            Route::get('doctors', [DoctorController::class, 'lookup']);
            Route::get('specializations', [LookupController::class, 'specializations']);
            Route::get('provinces', [LookupController::class, 'provinces']);
            Route::get('regencies', [LookupController::class, 'regencies']);
            Route::get('districts', [LookupController::class, 'districts']);
            Route::get('villages', [LookupController::class, 'villages']);
            Route::get('educations', [LookupController::class, 'educations']);
            Route::get('occupations', [LookupController::class, 'occupations']);
            Route::get('positions', [LookupController::class, 'positions']);
            Route::get('laboratory-categories', [LookupController::class, 'laboratoryCategories']);
            Route::get('radiology-categories', [LookupController::class, 'radiologyCategories']);
            Route::get('radiology-types', [LookupController::class, 'radiologyTypes']);
            Route::get('radiology-item-groups', [LookupController::class, 'radiologyItemGroups']);

            // Logistics Lookups
            Route::get('medicine-units', [LookupController::class, 'medicineUnits']);
            Route::get('suppliers', [LookupController::class, 'suppliers']);
            Route::get('warehouses', [LookupController::class, 'warehouses']);
            Route::get('medicine-categories', [LookupController::class, 'medicineCategories']);
            Route::get('medicine-routes', [LookupController::class, 'medicineRoutes']);
            Route::get('medication-signas', [LookupController::class, 'medicationSignas']);
            Route::get('medicine-classifications', [LookupController::class, 'medicineClassifications']);
            Route::get('generic-medicines', [LookupController::class, 'genericMedicines']);
        });
    });
});

Route::get('debug/doctors/{doctor}', [DoctorController::class, 'show']);
