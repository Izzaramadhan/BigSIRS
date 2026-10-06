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
}
