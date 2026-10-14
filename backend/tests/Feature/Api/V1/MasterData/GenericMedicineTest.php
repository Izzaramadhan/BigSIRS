<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\GenericMedicine;
use App\Models\MasterData\Medicine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GenericMedicineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_can_create_new_generic_medicine()
    {
        $response = $this->postJson('/api/v1/master-data/generic-medicines', [
            'name' => 'Cefixime Trihydrate',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Cefixime Trihydrate')
            ->assertJsonPath('data.legacy_id', null)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('generic_medicines', [
            'name' => 'Cefixime Trihydrate',
            'legacy_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_normalizes_whitespace_when_creating_generic_medicine()
    {
        $response = $this->postJson('/api/v1/master-data/generic-medicines', [
            'name' => "  Asam   Mefenamat   500mg \t ",
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Asam Mefenamat 500mg');

        $this->assertDatabaseHas('generic_medicines', [
            'name' => 'Asam Mefenamat 500mg',
        ]);
    }

    public function test_rejects_empty_or_whitespace_only_name()
    {
        $response1 = $this->postJson('/api/v1/master-data/generic-medicines', [
            'name' => '',
        ]);
        $response1->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $response2 = $this->postJson('/api/v1/master-data/generic-medicines', [
            'name' => '    ',
        ]);
        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_rejects_duplicate_name_case_insensitively()
    {
        GenericMedicine::create([
            'name' => 'Ibuprofen 400mg',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/master-data/generic-medicines', [
            'name' => '  ibuprofen   400mg  ',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_also_store_via_lookup_endpoint()
    {
        $response = $this->postJson('/api/v1/lookups/generic-medicines', [
            'name' => 'Lansoprazole 30mg',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Lansoprazole 30mg');

        $this->assertDatabaseHas('generic_medicines', [
            'name' => 'Lansoprazole 30mg',
        ]);
    }

    public function test_legacy_database_is_never_written_when_creating_generic_medicine()
    {
        $legacyCountBefore = null;
        $hasLegacy = false;
        try {
            DB::connection('legacy')->getPdo();
            if (Schema::connection('legacy')->hasTable('ref_generik')) {
                $hasLegacy = true;
                $legacyCountBefore = DB::connection('legacy')->table('ref_generik')->count();
            }
        } catch (\Throwable $e) {
            $hasLegacy = false;
        }

        $response = $this->postJson('/api/v1/master-data/generic-medicines', [
            'name' => 'Simvastatin 20mg',
        ]);

        $response->assertStatus(201);

        if ($hasLegacy) {
            $legacyCountAfter = DB::connection('legacy')->table('ref_generik')->count();
            $this->assertEquals($legacyCountBefore, $legacyCountAfter);
        }
    }

    public function test_medicine_can_be_created_with_newly_created_generic_medicine()
    {
        $genResponse = $this->postJson('/api/v1/master-data/generic-medicines', [
            'name' => 'Omeprazole 20mg',
        ]);
        $genResponse->assertStatus(201);
        $genericId = $genResponse->json('data.id');

        $medResponse = $this->postJson('/api/v1/master-data/medicines', [
            'name' => 'Omeprazole Kapsul',
            'generic_medicine_id' => $genericId,
        ]);

        $medResponse->assertStatus(201)
            ->assertJsonPath('data.generic_medicine_id', $genericId)
            ->assertJsonPath('data.generic.name', 'Omeprazole 20mg');

        $this->assertDatabaseHas('medicines', [
            'name' => 'Omeprazole Kapsul',
            'generic_medicine_id' => $genericId,
        ]);
    }
}
