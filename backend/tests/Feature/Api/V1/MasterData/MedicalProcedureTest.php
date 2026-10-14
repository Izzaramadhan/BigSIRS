<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\Icd9Cm;
use App\Models\MasterData\MedicalProcedure;
use App\Models\MasterData\ReportGroup;
use App\Models\Polyclinic;
use App\Models\ProcedureCategory;
use App\Models\TariffComponent;
use App\Models\TariffType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalProcedureTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $category;

    protected $tariffType;

    protected $component;

    protected $polyclinic;

    protected $icd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->category = ProcedureCategory::factory()->create();
        $this->tariffType = TariffType::factory()->create();
        $this->component = TariffComponent::factory()->create();
        $this->polyclinic = Polyclinic::factory()->create();
        $this->icd = Icd9Cm::create(['code' => '00.00', 'name' => 'Test ICD']);
    }

    public function test_requires_authentication()
    {
        $response = $this->getJson('/api/v1/master-data/procedures');
        $response->assertStatus(401);
    }

    public function test_can_list_procedures()
    {
        $procedure = MedicalProcedure::create([
            'code' => 'T001',
            'name' => 'Test Procedure',
            'procedure_category_id' => $this->category->id,
            'icd9_cm_id' => $this->icd->id,
            'is_visible' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/procedures');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.code', 'T001')
            ->assertJsonPath('data.0.name', 'Test Procedure');
    }

    public function test_can_create_procedure_with_tariffs()
    {
        $payload = [
            'code' => 'T002',
            'name' => 'New Procedure',
            'procedure_category_id' => $this->category->id,
            'icd9_cm_id' => $this->icd->id,
            'is_visible' => true,
            'polyclinics' => [$this->polyclinic->id],
            'tariffs' => [
                [
                    'tariff_type_id' => $this->tariffType->id,
                    'components' => [
                        [
                            'tariff_component_id' => $this->component->id,
                            'amount' => 50000,
                        ],
                    ],
                ],
            ],
        ];

        // Ensure tariff type has this component
        $this->tariffType->components()->create([
            'tariff_component_id' => $this->component->id,
            'percentage' => 100,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/procedures', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('medical_procedures', ['code' => 'T002']);
        $this->assertDatabaseHas('medical_procedure_tariffs', ['total_amount' => 50000]);
        $this->assertDatabaseHas('medical_procedure_tariff_components', ['amount' => 50000]);
    }

    public function test_backend_recalculates_total_ignoring_frontend_manipulation()
    {
        $this->tariffType->components()->create([
            'tariff_component_id' => $this->component->id,
            'percentage' => 100,
        ]);

        $payload = [
            'code' => 'T003',
            'name' => 'Calc Procedure',
            'procedure_category_id' => $this->category->id,
            'is_visible' => true,
            'tariffs' => [
                [
                    'tariff_type_id' => $this->tariffType->id,
                    'total_amount' => 1000, // Should be ignored!
                    'components' => [
                        [
                            'tariff_component_id' => $this->component->id,
                            'amount' => 50000, // Real amount
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/procedures', $payload);
        $response->assertStatus(201);
        $this->assertDatabaseHas('medical_procedure_tariffs', ['total_amount' => 50000]);
    }

    public function test_validates_negative_amount()
    {
        $this->tariffType->components()->create([
            'tariff_component_id' => $this->component->id,
            'percentage' => 100,
        ]);

        $payload = [
            'name' => 'Neg Procedure',
            'procedure_category_id' => $this->category->id,
            'tariffs' => [
                [
                    'tariff_type_id' => $this->tariffType->id,
                    'components' => [
                        [
                            'tariff_component_id' => $this->component->id,
                            'amount' => -100, // Invalid
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/procedures', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tariffs.0.components.0.amount']);
    }

    public function test_rejects_component_not_in_tariff_type()
    {
        $anotherComponent = TariffComponent::factory()->create();

        $payload = [
            'name' => 'Invalid Comp Procedure',
            'procedure_category_id' => $this->category->id,
            'tariffs' => [
                [
                    'tariff_type_id' => $this->tariffType->id,
                    'components' => [
                        [
                            'tariff_component_id' => $anotherComponent->id, // Not attached to $this->tariffType
                            'amount' => 50000,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/procedures', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tariffs.0.components.0.tariff_component_id']);
    }

    public function test_update_without_report_groups_retains_existing_relations()
    {
        $reportGroup = ReportGroup::create(['name' => 'Test Report Group']);

        $procedure = MedicalProcedure::create([
            'code' => 'T004',
            'name' => 'Preserve RG Procedure',
            'procedure_category_id' => $this->category->id,
            'icd9_cm_id' => $this->icd->id,
            'is_visible' => true,
        ]);

        $procedure->reportGroups()->attach($reportGroup->id);

        $this->tariffType->components()->create([
            'tariff_component_id' => $this->component->id,
            'percentage' => 100,
        ]);

        $tariff = $procedure->tariffs()->create([
            'tariff_type_id' => $this->tariffType->id,
            'total_amount' => 50000,
        ]);

        $tariffComp = $tariff->components()->create([
            'tariff_component_id' => $this->component->id,
            'amount' => 50000,
            'percentage_snapshot' => 100,
        ]);

        // Payload without report_groups
        $payload = [
            'name' => 'Updated Name',
            'procedure_category_id' => $this->category->id,
            'tariffs' => [
                [
                    'id' => $tariff->id,
                    'tariff_type_id' => $this->tariffType->id,
                    'components' => [
                        [
                            'id' => $tariffComp->id,
                            'tariff_component_id' => $this->component->id,
                            'amount' => 60000,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/procedures/{$procedure->id}", $payload);
        $response->assertStatus(200);

        // Check if name is updated
        $this->assertDatabaseHas('medical_procedures', [
            'id' => $procedure->id,
            'name' => 'Updated Name',
        ]);

        // Check if report_groups relation is retained!
        $this->assertDatabaseHas('medical_procedure_report_group', [
            'medical_procedure_id' => $procedure->id,
            'report_group_id' => $reportGroup->id,
        ]);
    }

    public function test_detail_endpoint_loads_tariffs_and_components()
    {
        $procedure = MedicalProcedure::create([
            'code' => 'T005',
            'name' => 'Detail Proc',
            'procedure_category_id' => $this->category->id,
            'icd9_cm_id' => $this->icd->id,
            'is_visible' => true,
        ]);

        $this->tariffType->components()->create([
            'tariff_component_id' => $this->component->id,
            'percentage' => 100,
        ]);

        $tariff = $procedure->tariffs()->create([
            'tariff_type_id' => $this->tariffType->id,
            'total_amount' => 50000,
        ]);

        $tariffComp = $tariff->components()->create([
            'tariff_component_id' => $this->component->id,
            'amount' => 50000,
            'percentage_snapshot' => 100,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/master-data/procedures/{$procedure->id}");
        $response->assertStatus(200);

        $response->assertJsonPath('data.tariffs.0.id', $tariff->id);
        $response->assertJsonPath('data.tariffs.0.components.0.id', $tariffComp->id);
        $response->assertJsonPath('data.tariffs.0.components.0.amount', 50000);
    }

    public function test_can_filter_procedures_by_category_id()
    {
        $category2 = ProcedureCategory::factory()->create();

        $proc1 = MedicalProcedure::create([
            'code' => 'P-CAT1',
            'name' => 'Tindakan Kategori 1',
            'procedure_category_id' => $this->category->id,
            'is_visible' => true,
        ]);

        $proc2 = MedicalProcedure::create([
            'code' => 'P-CAT2',
            'name' => 'Tindakan Kategori 2',
            'procedure_category_id' => $category2->id,
            'is_visible' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/master-data/procedures?category_id={$this->category->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $proc1->id)
            ->assertJsonPath('data.0.code', 'P-CAT1');
    }

    public function test_can_filter_procedures_by_tariff_type_id()
    {
        $tariffType2 = TariffType::factory()->create();

        $proc1 = MedicalProcedure::create([
            'code' => 'P-TT1',
            'name' => 'Tindakan Tarif 1',
            'procedure_category_id' => $this->category->id,
            'is_visible' => true,
        ]);
        $proc1->tariffs()->create([
            'tariff_type_id' => $this->tariffType->id,
            'total_amount' => 10000,
        ]);

        $proc2 = MedicalProcedure::create([
            'code' => 'P-TT2',
            'name' => 'Tindakan Tarif 2',
            'procedure_category_id' => $this->category->id,
            'is_visible' => true,
        ]);
        $proc2->tariffs()->create([
            'tariff_type_id' => $tariffType2->id,
            'total_amount' => 20000,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/master-data/procedures?tariff_type_id={$this->tariffType->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $proc1->id)
            ->assertJsonPath('data.0.code', 'P-TT1');
    }

    public function test_can_filter_by_combination_of_category_and_tariff_type()
    {
        $category2 = ProcedureCategory::factory()->create();
        $tariffType2 = TariffType::factory()->create();

        // Match both
        $proc1 = MedicalProcedure::create([
            'code' => 'P-MATCH',
            'name' => 'Match Both',
            'procedure_category_id' => $this->category->id,
            'is_visible' => true,
        ]);
        $proc1->tariffs()->create(['tariff_type_id' => $this->tariffType->id, 'total_amount' => 10000]);

        // Match category only
        $proc2 = MedicalProcedure::create([
            'code' => 'P-CATONLY',
            'name' => 'Cat Only',
            'procedure_category_id' => $this->category->id,
            'is_visible' => true,
        ]);
        $proc2->tariffs()->create(['tariff_type_id' => $tariffType2->id, 'total_amount' => 10000]);

        // Match tariff only
        $proc3 = MedicalProcedure::create([
            'code' => 'P-TTONLY',
            'name' => 'Tariff Only',
            'procedure_category_id' => $category2->id,
            'is_visible' => true,
        ]);
        $proc3->tariffs()->create(['tariff_type_id' => $this->tariffType->id, 'total_amount' => 10000]);

        $response = $this->actingAs($this->user)->getJson(
            "/api/v1/master-data/procedures?category_id={$this->category->id}&tariff_type_id={$this->tariffType->id}"
        );

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $proc1->id)
            ->assertJsonPath('data.0.code', 'P-MATCH');
    }

    public function test_filter_with_invalid_values_returns_empty_gracefully()
    {
        MedicalProcedure::create([
            'code' => 'P-VAL',
            'name' => 'Valid Proc',
            'procedure_category_id' => $this->category->id,
            'is_visible' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson(
            '/api/v1/master-data/procedures?category_id=non_numeric&tariff_type_id=-99'
        );

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_pagination_and_sorting_with_filters()
    {
        for ($i = 1; $i <= 15; $i++) {
            $p = MedicalProcedure::create([
                'code' => sprintf('P-%03d', $i),
                'name' => "Tindakan {$i}",
                'procedure_category_id' => $this->category->id,
                'is_visible' => true,
            ]);
            $p->tariffs()->create(['tariff_type_id' => $this->tariffType->id, 'total_amount' => 1000 * $i]);
        }

        // Another procedure that shouldn't match filter
        $otherCat = ProcedureCategory::factory()->create();
        MedicalProcedure::create([
            'code' => 'P-OTHER',
            'name' => 'Other Cat',
            'procedure_category_id' => $otherCat->id,
            'is_visible' => true,
        ]);

        // Page 1
        $response1 = $this->actingAs($this->user)->getJson(
            "/api/v1/master-data/procedures?category_id={$this->category->id}&tariff_type_id={$this->tariffType->id}&per_page=10&page=1"
        );
        $response1->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 15)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2);

        // Page 2
        $response2 = $this->actingAs($this->user)->getJson(
            "/api/v1/master-data/procedures?category_id={$this->category->id}&tariff_type_id={$this->tariffType->id}&per_page=10&page=2"
        );
        $response2->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_tariff_type_filter_ignores_soft_deleted_tariffs()
    {
        $proc = MedicalProcedure::create([
            'code' => 'P-SOFTDEL',
            'name' => 'Soft Deleted Tariff Proc',
            'procedure_category_id' => $this->category->id,
            'is_visible' => true,
        ]);
        $tariff = $proc->tariffs()->create([
            'tariff_type_id' => $this->tariffType->id,
            'total_amount' => 10000,
        ]);
        // Soft delete the tariff
        $tariff->delete();

        $response = $this->actingAs($this->user)->getJson("/api/v1/master-data/procedures?tariff_type_id={$this->tariffType->id}");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_export_requires_authentication()
    {
        $response = $this->getJson('/api/v1/master-data/procedures/export');
        $response->assertStatus(401);
    }

    public function test_export_without_filter()
    {
        $procedure = MedicalProcedure::create([
            'code' => 'T-EX-001',
            'name' => 'Procedure Export 1',
            'procedure_category_id' => $this->category->id,
            'icd9_cm_id' => $this->icd->id,
            'is_visible' => true,
        ]);
        $procedure->tariffs()->create([
            'tariff_type_id' => $this->tariffType->id,
            'total_amount' => 75000,
        ]);
        $procedure->polyclinics()->attach($this->polyclinic->id);

        $response = $this->actingAs($this->user)->get('/api/v1/master-data/procedures/export');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $content = $response->streamedContent();
        $this->assertNotEmpty($content);

        // Verify valid zip containing Excel worksheets
        $temp = tempnam(sys_get_temp_dir(), 'test_xlsx');
        file_put_contents($temp, $content);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($temp));
        $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
        $this->assertStringContainsString('T-EX-001', $sharedStrings);
        $this->assertStringContainsString('Procedure Export 1', $sharedStrings);
        $this->assertStringContainsString($this->category->name, $sharedStrings);
        $this->assertStringContainsString($this->tariffType->name, $sharedStrings);
        $this->assertStringContainsString($this->polyclinic->name, $sharedStrings);
        $zip->close();
        unlink($temp);
    }

    public function test_export_with_category_filter()
    {
        $cat2 = ProcedureCategory::factory()->create(['name' => 'Category Two']);

        $proc1 = MedicalProcedure::create([
            'code' => 'P-CAT1',
            'name' => 'Procedure Cat 1',
            'procedure_category_id' => $this->category->id,
            'is_visible' => true,
        ]);
        $proc1->tariffs()->create(['tariff_type_id' => $this->tariffType->id, 'total_amount' => 10000]);

        $proc2 = MedicalProcedure::create([
            'code' => 'P-CAT2',
            'name' => 'Procedure Cat 2',
            'procedure_category_id' => $cat2->id,
            'is_visible' => true,
        ]);
        $proc2->tariffs()->create(['tariff_type_id' => $this->tariffType->id, 'total_amount' => 20000]);

        $response = $this->actingAs($this->user)->get("/api/v1/master-data/procedures/export?category_id={$this->category->id}");

        $response->assertOk();
        $content = $response->streamedContent();

        $temp = tempnam(sys_get_temp_dir(), 'test_xlsx');
        file_put_contents($temp, $content);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($temp));
        $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
        $this->assertStringContainsString('P-CAT1', $sharedStrings);
        $this->assertStringNotContainsString('P-CAT2', $sharedStrings);
        $zip->close();
        unlink($temp);
    }

    public function test_export_with_tariff_type_filter()
    {
        $tariffType2 = TariffType::factory()->create(['name' => 'Tariff Type Two']);

        $proc = MedicalProcedure::create([
            'code' => 'P-MULTI-TAR',
            'name' => 'Multi Tariff Procedure',
            'procedure_category_id' => $this->category->id,
            'is_visible' => true,
        ]);
        $proc->tariffs()->create(['tariff_type_id' => $this->tariffType->id, 'total_amount' => 15000]);
        $proc->tariffs()->create(['tariff_type_id' => $tariffType2->id, 'total_amount' => 30000]);

        $response = $this->actingAs($this->user)->get("/api/v1/master-data/procedures/export?tariff_type_id={$this->tariffType->id}");

        $response->assertOk();
        $content = $response->streamedContent();

        $temp = tempnam(sys_get_temp_dir(), 'test_xlsx');
        file_put_contents($temp, $content);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($temp));
        $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
        $this->assertStringContainsString($this->tariffType->name, $sharedStrings);
        $this->assertStringNotContainsString('Tariff Type Two', $sharedStrings);
        $zip->close();
        unlink($temp);
    }

    public function test_export_with_combined_filters()
    {
        $cat2 = ProcedureCategory::factory()->create(['name' => 'Category Two']);
        $tariffType2 = TariffType::factory()->create(['name' => 'Tariff Type Two']);

        // Match both
        $proc1 = MedicalProcedure::create([
            'code' => 'P-BOTH-MATCH',
            'name' => 'Both Match',
            'procedure_category_id' => $this->category->id,
            'is_visible' => true,
        ]);
        $proc1->tariffs()->create(['tariff_type_id' => $this->tariffType->id, 'total_amount' => 50000]);

        // Category match only
        $proc2 = MedicalProcedure::create([
            'code' => 'P-CAT-ONLY',
            'name' => 'Cat Match Only',
            'procedure_category_id' => $this->category->id,
            'is_visible' => true,
        ]);
        $proc2->tariffs()->create(['tariff_type_id' => $tariffType2->id, 'total_amount' => 50000]);

        // Tariff match only
        $proc3 = MedicalProcedure::create([
            'code' => 'P-TT-ONLY',
            'name' => 'Tariff Match Only',
            'procedure_category_id' => $cat2->id,
            'is_visible' => true,
        ]);
        $proc3->tariffs()->create(['tariff_type_id' => $this->tariffType->id, 'total_amount' => 50000]);

        $response = $this->actingAs($this->user)->get(
            "/api/v1/master-data/procedures/export?category_id={$this->category->id}&tariff_type_id={$this->tariffType->id}"
        );

        $response->assertOk();
        $content = $response->streamedContent();

        $temp = tempnam(sys_get_temp_dir(), 'test_xlsx');
        file_put_contents($temp, $content);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($temp));
        $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
        $this->assertStringContainsString('P-BOTH-MATCH', $sharedStrings);
        $this->assertStringNotContainsString('P-CAT-ONLY', $sharedStrings);
        $this->assertStringNotContainsString('P-TT-ONLY', $sharedStrings);
        $zip->close();
        unlink($temp);
    }

    public function test_export_empty_result()
    {
        $response = $this->actingAs($this->user)->getJson(
            '/api/v1/master-data/procedures/export?search=NON_EXISTENT_PROCEDURE_KEYWORD'
        );

        $response->assertOk();
        $response->assertJson([
            'message' => 'Tidak ada data tindakan yang dapat diunduh.',
        ]);
    }

    public function test_export_header_response_and_filename()
    {
        MedicalProcedure::create([
            'code' => 'P-FILE',
            'name' => 'Filename Procedure',
            'procedure_category_id' => $this->category->id,
            'is_visible' => true,
        ]);

        $response = $this->actingAs($this->user)->get('/api/v1/master-data/procedures/export');

        $response->assertOk();
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertNotNull($disposition);
        $this->assertMatchesRegularExpression('/attachment; filename="?data-tindakan-\d{4}-\d{2}-\d{2}-\d{6}\.xlsx"?/', $disposition);
    }

    public function test_export_excludes_soft_deleted_data()
    {
        // 1. Soft-deleted procedure
        $procDeleted = MedicalProcedure::create([
            'code' => 'P-DELETED-PROC',
            'name' => 'Soft Deleted Procedure',
            'procedure_category_id' => $this->category->id,
            'is_visible' => true,
        ]);
        $procDeleted->delete();

        // 2. Active procedure with one active tariff and one soft-deleted tariff
        $procActive = MedicalProcedure::create([
            'code' => 'P-ACTIVE-PROC',
            'name' => 'Active Procedure',
            'procedure_category_id' => $this->category->id,
            'is_visible' => true,
        ]);
        $activeTariff = $procActive->tariffs()->create([
            'tariff_type_id' => $this->tariffType->id,
            'total_amount' => 12345,
        ]);
        $tariffType2 = TariffType::factory()->create(['name' => 'Deleted Tariff Type']);
        $deletedTariff = $procActive->tariffs()->create([
            'tariff_type_id' => $tariffType2->id,
            'total_amount' => 99999,
        ]);
        $deletedTariff->delete();

        $response = $this->actingAs($this->user)->get('/api/v1/master-data/procedures/export');

        $response->assertOk();
        $content = $response->streamedContent();

        $temp = tempnam(sys_get_temp_dir(), 'test_xlsx');
        file_put_contents($temp, $content);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($temp));
        $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
        $this->assertStringContainsString('P-ACTIVE-PROC', $sharedStrings);
        $this->assertStringNotContainsString('P-DELETED-PROC', $sharedStrings);
        $this->assertStringNotContainsString('Deleted Tariff Type', $sharedStrings);
        $zip->close();
        unlink($temp);
    }
}
