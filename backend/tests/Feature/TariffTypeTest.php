<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\TariffType;
use App\Models\TariffComponent;
use App\Models\TariffTypeComponent;

class TariffTypeTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_tariff_types()
    {
        $type = TariffType::factory()->create();
        
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/tariff-types');

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => $type->name]);
    }

    public function test_can_create_tariff_type_with_components()
    {
        $component1 = TariffComponent::factory()->create();
        $component2 = TariffComponent::factory()->create();

        $data = [
            'name' => 'Jenis Tarif Baru',
            'code' => 'JTB',
            'description' => 'Test Desc',
            'components' => [
                [
                    'tariff_component_id' => $component1->id,
                    'percentage' => 40
                ],
                [
                    'tariff_component_id' => $component2->id,
                    'percentage' => 60
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/tariff-types', $data);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'Jenis Tarif Baru']);

        $this->assertDatabaseHas('tariff_types', [
            'name' => 'Jenis Tarif Baru',
            'code' => 'JTB',
            'needs_review' => 0
        ]);

        $this->assertDatabaseHas('tariff_type_components', [
            'tariff_component_id' => $component1->id,
            'percentage' => 40
        ]);
    }

    public function test_create_requires_unique_components()
    {
        $component1 = TariffComponent::factory()->create();

        $data = [
            'name' => 'Jenis Tarif Baru',
            'code' => 'JTB',
            'components' => [
                [
                    'tariff_component_id' => $component1->id,
                    'percentage' => 50
                ],
                [
                    'tariff_component_id' => $component1->id, // Duplicate
                    'percentage' => 50
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/tariff-types', $data);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['components.0.tariff_component_id']);
    }

    public function test_create_rejects_percentage_above_100()
    {
        $component1 = TariffComponent::factory()->create();

        $data = [
            'name' => 'Jenis Tarif Baru',
            'code' => 'JTB',
            'components' => [
                [
                    'tariff_component_id' => $component1->id,
                    'percentage' => 150
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/tariff-types', $data);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['components.0.percentage']);
    }
}
