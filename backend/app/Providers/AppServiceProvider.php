<?php

namespace App\Providers;

use App\Models\Legacy\LegacyDoctor;
use App\Models\Legacy\LegacyEmployee;
use App\Models\Legacy\LegacyGenericMedicine;
use App\Models\Legacy\LegacyLaboratoryGroup;
use App\Models\Legacy\LegacyLaboratoryItem;
use App\Models\Legacy\LegacyMedicationSigna;
use App\Models\Legacy\LegacyMedicine;
use App\Models\Legacy\LegacyMedicineCategory;
use App\Models\Legacy\LegacyMedicinePackage;
use App\Models\Legacy\LegacyMedicineRoute;
use App\Models\Legacy\LegacyMedicineUnit;
use App\Models\Legacy\LegacyPolyclinic;
use App\Models\Legacy\LegacyRadiologyCategory;
use App\Models\Legacy\LegacyRadiologyItemGroup;
use App\Models\Legacy\LegacyRadiologyType;
use App\Models\Legacy\LegacySupplier;
use App\Models\Legacy\LegacyWarehouse;
use App\Models\Logistics\GoodsIssue;
use App\Models\Logistics\StockMutation;
use App\Models\Logistics\StockOpname;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\MedicineUnit;
use App\Models\MasterData\Supplier;
use App\Models\MasterData\Warehouse;
use App\Models\Polyclinic;
use App\Policies\LaboratoryGroupPolicy;
use App\Policies\LaboratoryItemPolicy;
use App\Policies\Logistics\GoodsIssuePolicy;
use App\Policies\Logistics\StockMutationPolicy;
use App\Policies\Logistics\StockOpnamePolicy;
use App\Policies\MasterData\GenericMedicinePolicy;
use App\Policies\MasterData\MedicationSignaPolicy;
use App\Policies\MasterData\MedicineCategoryPolicy;
use App\Policies\MasterData\MedicinePackagePolicy;
use App\Policies\MasterData\MedicinePolicy;
use App\Policies\MasterData\MedicineRoutePolicy;
use App\Policies\MasterData\MedicineUnitPolicy;
use App\Policies\MasterData\PolyclinicPolicy;
use App\Policies\MasterData\RadiologyItemGroupPolicy;
use App\Policies\MasterData\RadiologyTypePolicy;
use App\Policies\MasterData\SupplierPolicy;
use App\Policies\MasterData\WarehousePolicy;
use App\Policies\RadiologyCategoryPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(StockMutation::class, StockMutationPolicy::class);
        Gate::policy(StockOpname::class, StockOpnamePolicy::class);
        Gate::policy(GoodsIssue::class, GoodsIssuePolicy::class);
        Gate::policy(Polyclinic::class, PolyclinicPolicy::class);
        Gate::policy(LegacyPolyclinic::class, PolyclinicPolicy::class);
        Gate::policy(Warehouse::class, WarehousePolicy::class);
        Gate::policy(LegacyWarehouse::class, WarehousePolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(LegacySupplier::class, SupplierPolicy::class);
        Gate::policy(LegacyMedicineCategory::class, MedicineCategoryPolicy::class);
        Gate::policy(MedicineUnit::class, MedicineUnitPolicy::class);
        Gate::policy(LegacyMedicineUnit::class, MedicineUnitPolicy::class);
        Gate::policy(Medicine::class, MedicinePolicy::class);
        Gate::policy(LegacyMedicine::class, MedicinePolicy::class);
        Gate::policy(LegacyMedicineRoute::class, MedicineRoutePolicy::class);
        Gate::policy(LegacyMedicationSigna::class, MedicationSignaPolicy::class);
        Gate::policy(LegacyGenericMedicine::class, GenericMedicinePolicy::class);
        Gate::policy(LegacyMedicinePackage::class, MedicinePackagePolicy::class);
        Gate::policy(LegacyEmployee::class, \App\Policies\EmployeePolicy::class);
        Gate::policy(LegacyLaboratoryGroup::class, LaboratoryGroupPolicy::class);
        Gate::policy(LegacyLaboratoryItem::class, LaboratoryItemPolicy::class);
        Gate::policy(LegacyRadiologyCategory::class, RadiologyCategoryPolicy::class);
        Gate::policy(LegacyRadiologyType::class, RadiologyTypePolicy::class);
        Gate::policy(LegacyRadiologyItemGroup::class, RadiologyItemGroupPolicy::class);
        Gate::policy(\App\Models\Legacy\LegacyFollowUpHandling::class, \App\Policies\MasterData\FollowUpHandlingPolicy::class);
        Gate::policy(\App\Models\Legacy\LegacyDietType::class, \App\Policies\DietTypePolicy::class);
        Gate::policy(\App\Models\Legacy\LegacyLetterType::class, \App\Policies\LetterTypePolicy::class);
        Gate::policy(\App\Models\Legacy\LegacyActivityType::class, \App\Policies\ActivityTypePolicy::class);

        Route::model('stock_opname', StockOpname::class);
        Route::bind('goods_issue', function ($value) {
            if (\App\Support\LegacyAccess::isAvailable('lgs_pengeluaran')) {
                return \App\Models\Legacy\LegacyGoodsIssue::pengeluaran()->find($value) ?? abort(404);
            }
            return GoodsIssue::find($value) ?? abort(404);
        });
    }
}
