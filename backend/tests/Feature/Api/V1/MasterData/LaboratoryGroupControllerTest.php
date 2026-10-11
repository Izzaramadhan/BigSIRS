<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\LaboratoryCategory;
use App\Models\MasterData\LaboratoryGroup;
use App\Models\MasterData\LaboratoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaboratoryGroupControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_can_list_laboratory_groups_with_relations(): void
    {
        $category = LaboratoryCategory::factory()->create(['name' => 'Hematologi']);
        $item = LaboratoryItem::factory()->create(['name' => 'Hemoglobin', 'unit' => 'g/dL']);
        $group = LaboratoryGroup::factory()->create([
            'laboratory_category_id' => $category->id,
            'name' => 'Darah Lengkap',
            'price' => 50000,
        ]);
        $group->items()->attach($item->id);

        $response = $this->getJson('/api/v1/master-data/laboratory-groups');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'price',
                        'is_active',
                        'category' => ['id', 'name'],
                        'items' => [
                            '*' => ['id', 'name', 'unit'],
                        ],
                    ],
                ],
                'meta' => ['current_page', 'last_page', 'total'],
            ])
            ->assertJsonPath('data.0.name', 'Darah Lengkap')
            ->assertJsonPath('data.0.category.name', 'Hematologi')
            ->assertJsonPath('data.0.items.0.name', 'Hemoglobin');
    }

    public function test_can_create_laboratory_group_with_items(): void
    {
        $category = LaboratoryCategory::factory()->create();
        $item1 = LaboratoryItem::factory()->create();
        $item2 = LaboratoryItem::factory()->create();

        $payload = [
            'laboratory_category_id' => $category->id,
            'name' => 'Paket Anemia',
            'description' => 'Pemeriksaan anemia',
            'price' => 75000,
            'harga_jaspel' => 25000,
            'is_active' => true,
            'loinc_code' => '58410-2',
            'loinc_url' => 'https://loinc.org/58410-2/',
            'snomed_code' => '439958008',
            'snomed_url' => 'https://browser.ihtsdotools.org/?perspective=full&conceptId1=439958008',
            'laboratory_item_ids' => [$item1->id, $item2->id],
        ];

        $response = $this->postJson('/api/v1/master-data/laboratory-groups', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Paket Anemia')
            ->assertJsonPath('data.harga_jaspel', '25000.00')
            ->assertJsonPath('data.loinc_code', '58410-2')
            ->assertJsonCount(2, 'data.items');

        $this->assertDatabaseHas('laboratory_groups', [
            'name' => 'Paket Anemia',
            'laboratory_category_id' => $category->id,
            'loinc_code' => '58410-2',
        ]);
    }

    public function test_can_update_laboratory_group(): void
    {
        $category = LaboratoryCategory::factory()->create();
        $group = LaboratoryGroup::factory()->create([
            'laboratory_category_id' => $category->id,
            'name' => 'Nama Lama',
        ]);
        $item = LaboratoryItem::factory()->create();

        $payload = [
            'laboratory_category_id' => $category->id,
            'name' => 'Nama Baru',
            'description' => 'Deskripsi update',
            'price' => 120000,
            'harga_jaspel' => 40000,
            'is_active' => true,
            'loinc_code' => '12345-6',
            'loinc_url' => 'https://loinc.org/12345-6/',
            'snomed_code' => '987654321',
            'snomed_url' => 'https://snomed.info/id/987654321',
            'laboratory_item_id' => $item->id,
        ];

        $response = $this->putJson("/api/v1/master-data/laboratory-groups/{$group->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Nama Baru')
            ->assertJsonPath('data.harga_jaspel', '40000.00')
            ->assertJsonPath('data.loinc_code', '12345-6');

        $this->assertDatabaseHas('laboratory_groups', [
            'id' => $group->id,
            'name' => 'Nama Baru',
            'loinc_code' => '12345-6',
        ]);
    }

    public function test_can_delete_laboratory_group(): void
    {
        $group = LaboratoryGroup::factory()->create();

        $response = $this->deleteJson("/api/v1/master-data/laboratory-groups/{$group->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('laboratory_groups', [
            'id' => $group->id,
        ]);
    }
}
