<?php

namespace Tests\Feature;

use App\Models\Legacy\LegacyDoctor;
use App\Models\Legacy\LegacyEmployee;
use App\Models\Legacy\LegacyGenericMedicine;
use App\Models\Legacy\LegacyIcd10;
use App\Models\Legacy\LegacyInvoice;
use App\Models\Legacy\LegacyMedicationSigna;
use App\Models\Legacy\LegacyMedicine;
use App\Models\Legacy\LegacyMedicineCategory;
use App\Models\Legacy\LegacyMedicineClassification;
use App\Models\Legacy\LegacyMedicinePackage;
use App\Models\Legacy\LegacyMedicinePackageItem;
use App\Models\Legacy\LegacyMedicineRoute;
use App\Models\Legacy\LegacyMedicineUnit;
use App\Models\Legacy\LegacyPolyclinic;
use App\Models\Legacy\LegacyProvince;
use App\Models\Legacy\LegacyRegency;
use App\Models\Legacy\LegacySupplier;
use App\Models\Legacy\LegacyVillage;
use App\Models\Legacy\LegacyWarehouse;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DirectAccessVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::factory()->create([
            'username' => 'admin_test',
            'email' => 'admin_test@simrs.test',
        ]);
    }

    public function test_legacy_models_are_strictly_read_only(): void
    {
        $model = new LegacyMedicineClassification();
        $model->name = 'Klasifikasi Coba Tulis';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Operasi tulis ke database legacy dinonaktifkan untuk menjaga integritas data sumber.');
        $model->save();
    }

    public function test_legacy_delete_is_strictly_forbidden(): void
    {
        $model = new LegacyMedicineClassification();
        $model->id = 999999;
        $model->exists = true;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Operasi hapus ke database legacy dinonaktifkan untuk menjaga integritas data sumber.');
        $model->delete();
    }

    public function test_legacy_polyclinic_warehouse_supplier_and_medicine_meta_are_unlocked_for_controlled_crud(): void
    {
        $poly = new LegacyPolyclinic();
        $this->assertFalse($poly->isReadOnly());

        $warehouse = new LegacyWarehouse();
        $this->assertFalse($warehouse->isReadOnly());

        $supplier = new LegacySupplier();
        $this->assertFalse($supplier->isReadOnly());

        $medCat = new LegacyMedicineCategory();
        $this->assertFalse($medCat->isReadOnly());

        $medUnit = new LegacyMedicineUnit();
        $this->assertFalse($medUnit->isReadOnly());

        $medicine = new LegacyMedicine();
        $this->assertFalse($medicine->isReadOnly());

        $route = new LegacyMedicineRoute();
        $this->assertFalse($route->isReadOnly());

        $signa = new LegacyMedicationSigna();
        $this->assertFalse($signa->isReadOnly());

        $generic = new LegacyGenericMedicine();
        $this->assertFalse($generic->isReadOnly());

        $package = new LegacyMedicinePackage();
        $this->assertFalse($package->isReadOnly());

        $packageItem = new LegacyMedicinePackageItem();
        $this->assertFalse($packageItem->isReadOnly());

        $icd10 = new LegacyIcd10();
        $this->assertFalse($icd10->isReadOnly());

        $icd9 = new \App\Models\Legacy\LegacyIcd9();
        $this->assertFalse($icd9->isReadOnly());

        // Unaudited reference models remain read-only
        $classification = new LegacyMedicineClassification();
        $this->assertTrue($classification->isReadOnly());
    }

    public function test_direct_access_counts_when_legacy_db_connected(): void
    {
        if (! LegacyAccess::isAvailable('ref_poliklinik')) {
            $this->markTestSkipped('Legacy MySQL database is not connected in this environment.');
        }

        // Verify direct queries against simrs_legacy
        $this->assertGreaterThan(100, LegacyPolyclinic::count(), 'Polyclinic count should be > 100');
        $this->assertGreaterThan(500, LegacyRegency::count(), 'Regency count should be > 500');
        $this->assertGreaterThan(70000, LegacyVillage::count(), 'Village count should be > 70000');
        $this->assertGreaterThan(80, LegacyEmployee::count(), 'Employee count should be > 80');
        $this->assertGreaterThan(20, LegacyDoctor::count(), 'Doctor count should be > 20');
        $this->assertGreaterThan(1000, LegacyMedicine::count(), 'Medicine count should be > 1000');
        $this->assertGreaterThan(1000, LegacyInvoice::count(), 'Invoice count should be > 1000');
    }

    public function test_lookup_endpoints_with_direct_access(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/lookups/provinces');
        $response->assertStatus(200);

        $response = $this->getJson('/api/v1/lookups/medicines');
        $response->assertStatus(200);

        $response = $this->getJson('/api/v1/lookups/warehouses');
        $response->assertStatus(200);
    }

    public function test_master_data_polyclinic_direct_access(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/master-data/polyclinics?per_page=10');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'code', 'is_active'],
            ],
            'meta' => ['current_page', 'per_page', 'total'],
        ]);
    }

    public function test_master_data_district_direct_access(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/master-data/districts?per_page=10');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'regency_id', 'is_active'],
            ],
        ]);
    }
}
