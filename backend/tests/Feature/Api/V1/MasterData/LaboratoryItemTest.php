<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\LaboratoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaboratoryItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_is_required(): void
    {
        $this->getJson('/api/v1/master-data/laboratory-items')->assertUnauthorized();
    }

    public function test_can_list_and_paginate_items(): void
    {
        $this->authenticate();
        LaboratoryItem::factory()->count(12)->create();

        $this->getJson('/api/v1/master-data/laboratory-items?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 12)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_can_search_name_unit_and_reference_value(): void
    {
        $this->authenticate();
        LaboratoryItem::factory()->create(['name' => 'KOLESTEROL HDL', 'reference_value' => '30-70', 'unit' => 'mg/dL']);
        LaboratoryItem::factory()->create(['name' => 'HBSAG', 'reference_value' => 'Negatif', 'unit' => null]);

        $this->getJson('/api/v1/master-data/laboratory-items?search=30-70')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'KOLESTEROL HDL');

        $this->getJson('/api/v1/master-data/laboratory-items?search=mg%2FdL')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_show_item_detail(): void
    {
        $this->authenticate();
        $item = LaboratoryItem::factory()->create(['reference_value' => '<200']);

        $this->getJson("/api/v1/master-data/laboratory-items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.reference_value', '<200');
    }

    public function test_can_create_item_with_text_reference_and_nullable_unit(): void
    {
        $this->authenticate();

        $this->postJson('/api/v1/master-data/laboratory-items', [
            'name' => '  KOLESTEROL   TOTAL  ',
            'reference_value' => 'L: 45, P: 50',
            'unit' => '',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'KOLESTEROL TOTAL')
            ->assertJsonPath('data.reference_value', 'L: 45, P: 50')
            ->assertJsonPath('data.unit', null);
    }

    public function test_name_is_required(): void
    {
        $this->authenticate();

        $this->postJson('/api/v1/master-data/laboratory-items', ['reference_value' => 'Negatif'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_can_update_item(): void
    {
        $this->authenticate();
        $item = LaboratoryItem::factory()->create();

        $this->putJson("/api/v1/master-data/laboratory-items/{$item->id}", [
            'name' => 'HBSAG',
            'reference_value' => 'Negatif',
            'unit' => null,
        ])->assertOk()
            ->assertJsonPath('data.name', 'HBSAG');

        $this->assertDatabaseHas('laboratory_items', ['id' => $item->id, 'reference_value' => 'Negatif']);
    }

    public function test_can_archive_item(): void
    {
        $this->authenticate();
        $item = LaboratoryItem::factory()->create();

        $this->deleteJson("/api/v1/master-data/laboratory-items/{$item->id}")->assertOk();

        $this->assertSoftDeleted('laboratory_items', ['id' => $item->id]);
    }

    private function authenticate(): void
    {
        $this->actingAs(User::factory()->create());
    }
}
