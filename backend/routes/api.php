<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CaptchaController;
use App\Http\Controllers\Api\V1\Logistics\GoodsIssueController;
use App\Http\Controllers\Api\V1\Logistics\InvoiceController;
use App\Http\Controllers\Api\V1\Logistics\PosSaleController;
use App\Http\Controllers\Api\V1\Logistics\StockMutationController;
use App\Http\Controllers\Api\V1\Logistics\StockOpnameController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\MasterData\ActivityTypeController;
use App\Http\Controllers\Api\V1\MasterData\BedController;
use App\Http\Controllers\Api\V1\MasterData\BuildingController;
use App\Http\Controllers\Api\V1\MasterData\DietTypeController;
use App\Http\Controllers\Api\V1\MasterData\DistrictController;
use App\Http\Controllers\Api\V1\MasterData\DoctorController;
use App\Http\Controllers\Api\V1\MasterData\DoctorScheduleController;
use App\Http\Controllers\Api\V1\MasterData\EducationController;
use App\Http\Controllers\Api\V1\MasterData\EmployeeController;
use App\Http\Controllers\Api\V1\MasterData\FloorController;
use App\Http\Controllers\Api\V1\MasterData\FollowUpHandlingController;
use App\Http\Controllers\Api\V1\MasterData\GenericMedicineController;
use App\Http\Controllers\Api\V1\MasterData\GuarantorController;
use App\Http\Controllers\Api\V1\MasterData\Icd10CodeController;
use App\Http\Controllers\Api\V1\MasterData\Icd9CmController;
use App\Http\Controllers\Api\V1\MasterData\LaboratoryCategoryController;
use App\Http\Controllers\Api\V1\MasterData\LaboratoryGroupController;
use App\Http\Controllers\Api\V1\MasterData\LaboratoryItemController;
use App\Http\Controllers\Api\V1\MasterData\LetterTypeController;
use App\Http\Controllers\Api\V1\MasterData\MedicalProcedureController;
use App\Http\Controllers\Api\V1\MasterData\MedicationSignaController;
use App\Http\Controllers\Api\V1\MasterData\MedicineCategoryController;
use App\Http\Controllers\Api\V1\MasterData\MedicineController;
use App\Http\Controllers\Api\V1\MasterData\MedicinePackageController;
use App\Http\Controllers\Api\V1\MasterData\MedicineRouteController;
use App\Http\Controllers\Api\V1\MasterData\MedicineUnitController;
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
use App\Http\Controllers\Api\V1\MasterData\RoomClassController;
use App\Http\Controllers\Api\V1\MasterData\RoomController;
use App\Http\Controllers\Api\V1\MasterData\ServiceTypeController;
use App\Http\Controllers\Api\V1\MasterData\SupplierController;
use App\Http\Controllers\Api\V1\MasterData\TariffComponentController;
use App\Http\Controllers\Api\V1\MasterData\TariffTypeController;
use App\Http\Controllers\Api\V1\MasterData\VillageController;
use App\Http\Controllers\Api\V1\MasterData\WardController;
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

            Route::get('procedures/export', [MedicalProcedureController::class, 'export']);
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

            Route::get('doctor-schedules/recap', [DoctorScheduleController::class, 'recap']);
            Route::apiResource('doctor-schedules', DoctorScheduleController::class);

            Route::apiResource('educations', EducationController::class);

            Route::apiResource('occupations', OccupationController::class);

            Route::apiResource('regencies', RegencyController::class);

            Route::apiResource('districts', DistrictController::class);

            Route::apiResource('villages', VillageController::class);

            Route::apiResource('service-types', ServiceTypeController::class);

            Route::apiResource('diet-types', DietTypeController::class);

            Route::get('activity-types/lookup', [ActivityTypeController::class, 'lookup']);
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

            Route::patch('generic-medicines/{generic_medicine}/status', [GenericMedicineController::class, 'updateStatus']);
            Route::apiResource('generic-medicines', GenericMedicineController::class);

            Route::patch('medicines/{medicine}/status', [MedicineController::class, 'updateStatus']);
            Route::apiResource('medicines', MedicineController::class);
            Route::apiResource('medicine-packages', MedicinePackageController::class);
            Route::apiResource('follow-up-handlings', FollowUpHandlingController::class);
            Route::apiResource('buildings', BuildingController::class);
            Route::apiResource('floors', FloorController::class);
            Route::apiResource('wards', WardController::class);
            Route::get('room-classes/options', [RoomClassController::class, 'options']);
            Route::apiResource('room-classes', RoomClassController::class);
            Route::apiResource('rooms', RoomController::class);
            Route::apiResource('beds', BedController::class);
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
            Route::get('medicines', [LookupController::class, 'medicines']);
            Route::get('medicine-units', [LookupController::class, 'medicineUnits']);
            Route::get('suppliers', [LookupController::class, 'suppliers']);
            Route::get('warehouses', [LookupController::class, 'warehouses']);
            Route::get('medicine-categories', [LookupController::class, 'medicineCategories']);
            Route::get('medicine-routes', [LookupController::class, 'medicineRoutes']);
            Route::get('medication-signas', [LookupController::class, 'medicationSignas']);
            Route::get('medicine-classifications', [LookupController::class, 'medicineClassifications']);
            Route::get('generic-medicines', [LookupController::class, 'genericMedicines']);
            Route::post('generic-medicines', [GenericMedicineController::class, 'store']);
        });

        Route::prefix('logistics')->group(function () {
            Route::get('invoices/summary', [InvoiceController::class, 'summary']);
            Route::get('invoices/export', [InvoiceController::class, 'export']);
            Route::apiResource('invoices', InvoiceController::class);

            Route::get('mutations/summary', [StockMutationController::class, 'summary']);
            Route::get('mutations/export', [StockMutationController::class, 'export']);
            Route::get('warehouses/{warehouse}/available-stocks', [GoodsIssueController::class, 'availableStocks']);
            Route::get('warehouse-stocks', [StockMutationController::class, 'warehouseStocks']);
            Route::apiResource('mutations', StockMutationController::class)->only(['index', 'show', 'store']);

            // Stock Opname (Direct Warehouse Stocks & Adjustments)
            Route::get('stock-opnames/export', [StockOpnameController::class, 'export']);
            Route::get('stock-opnames', [StockOpnameController::class, 'index']);
            Route::get('stock-opnames/{stock}', [StockOpnameController::class, 'show']);
            Route::patch('stock-opnames/{stock}', [StockOpnameController::class, 'update']);
            Route::put('stock-opnames/{stock}', [StockOpnameController::class, 'update']);

            // Goods Issues (Pengeluaran Barang Nonpenjualan)
            Route::get('goods-issues/summary', [GoodsIssueController::class, 'summary']);
            Route::get('goods-issues/form-options', [GoodsIssueController::class, 'formOptions']);
            Route::get('goods-issues/export', [GoodsIssueController::class, 'export']);
            Route::post('goods-issues/{goods_issue}/finalize', [GoodsIssueController::class, 'finalize']);
            Route::post('goods-issues/{goods_issue}/cancel', [GoodsIssueController::class, 'cancel']);
            Route::post('goods-issues/{goods_issue}/items', [GoodsIssueController::class, 'addItem']);
            Route::put('goods-issues/{goods_issue}/items/{item}', [GoodsIssueController::class, 'updateItem']);
            Route::delete('goods-issues/{goods_issue}/items/{item}', [GoodsIssueController::class, 'deleteItem']);
            Route::apiResource('goods-issues', GoodsIssueController::class);

            // POS OTC Sales (Penjualan Bebas)
            Route::get('pos-sales/customers', [PosSaleController::class, 'customers']);
            Route::get('pos-sales/summary', [PosSaleController::class, 'summary']);
            Route::get('pos-sales/statistics', [PosSaleController::class, 'summary']);
            Route::get('pos-sales/form-options', [PosSaleController::class, 'formOptions']);
            Route::get('pos-sales/export', [PosSaleController::class, 'export']);
            Route::post('pos-sales/{pos_sale}/cancel', [PosSaleController::class, 'cancel']);
            Route::post('pos-sales/{pos_sale}/items', [PosSaleController::class, 'addItem']);
            Route::put('pos-sales/{pos_sale}/items/{item}', [PosSaleController::class, 'updateItem']);
            Route::delete('pos-sales/{pos_sale}/items/{item}', [PosSaleController::class, 'deleteItem']);
            Route::apiResource('pos-sales', PosSaleController::class);
        });

        Route::get('invoices/summary', [InvoiceController::class, 'summary']);
        Route::get('invoices/export', [InvoiceController::class, 'export']);
        Route::apiResource('invoices', InvoiceController::class);
    });
});

Route::get('debug/doctors/{doctor}', [DoctorController::class, 'show']);
