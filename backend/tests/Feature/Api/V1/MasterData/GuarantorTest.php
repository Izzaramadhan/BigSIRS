<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Enums\GuarantorType;
use App\Models\Guarantor;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuarantorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthorized_user_cannot_access_endpoints(): void
    {
        $this->getJson('/api/v1/master-data/guarantors')->assertUnauthorized();
        $this->postJson('/api/v1/master-data/guarantors', [])->assertUnauthorized();
    }

    public function test_can_list_guarantors(): void
    {
        Guarantor::factory()->count(20)->create();

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/guarantors?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'name', 'type', 'type_label', 'is_active'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonCount(10, 'data');
    }

    public function test_can_search_and_filter_guarantors(): void
    {
        Guarantor::factory()->create(['name' => 'BPJS Kesehatan', 'code' => 'ASR-01', 'type' => GuarantorType::Bpjs]);
        Guarantor::factory()->create(['name' => 'Asuransi Mandiri', 'code' => 'ASR-02', 'type' => GuarantorType::PrivateInsurance]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/guarantors?type=bpjs');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals('BPJS Kesehatan', $response->json('data.0.name'));
        $this->assertEquals('BPJS', $response->json('data.0.type_label'));
    }

    public function test_can_create_guarantor(): void
    {
        $payload = [
            'code' => ' asr-01 ',
            'name' => ' Asuransi   Umum ',
            'type' => 'self_pay',
            'is_government' => true,
            'inacbg_id' => '12',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/guarantors', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('guarantors', [
            'code' => 'ASR-01',
            'name' => 'Asuransi Umum',
            'type' => 'self_pay',
            'is_government' => true,
            'inacbg_id' => '12',
        ]);
    }

    public function test_cannot_create_with_invalid_type(): void
    {
        $payload = [
            'code' => 'ASR-01',
            'name' => 'Asuransi Umum',
            'type' => 'invalid_type',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/guarantors', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_can_update_guarantor(): void
    {
        $guarantor = Guarantor::factory()->create();

        $payload = [
            'code' => 'ASR-02',
            'name' => 'Asuransi Update',
            'type' => 'government',
            'is_government' => false,
            'inacbg_id' => '0',
        ];

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/guarantors/{$guarantor->id}", $payload);

        $response->assertOk();
        $this->assertDatabaseHas('guarantors', [
            'id' => $guarantor->id,
            'code' => 'ASR-02',
            'name' => 'Asuransi Update',
            'type' => 'government',
            'is_government' => false,
            'inacbg_id' => '0',
        ]);
    }

    public function test_can_update_status(): void
    {
        $guarantor = Guarantor::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->user)->patchJson("/api/v1/master-data/guarantors/{$guarantor->id}/status", [
            'is_active' => false,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('guarantors', [
            'id' => $guarantor->id,
            'is_active' => false,
        ]);
    }

    public function test_can_create_guarantor_with_minimal_fields(): void
    {
        $payload = [
            'name' => 'Asuransi Mandiri Sehat',
            'is_government' => true,
            'inacbg_id' => '5',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/guarantors', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('guarantors', [
            'name' => 'Asuransi Mandiri Sehat',
            'is_government' => true,
            'inacbg_id' => '5',
            'type' => 'bpjs',
        ]);
    }

    public function test_can_update_guarantor_with_minimal_fields(): void
    {
        $guarantor = Guarantor::factory()->create([
            'code' => 'ASR-EXISTING',
            'type' => GuarantorType::PrivateInsurance,
            'name' => 'Old Name',
        ]);

        $payload = [
            'name' => 'New Name',
            'is_government' => false,
            'inacbg_id' => null,
        ];

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/guarantors/{$guarantor->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('data.code', 'ASR-EXISTING')
            ->assertJsonPath('data.type', 'private_insurance')
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.is_government', false)
            ->assertJsonPath('data.inacbg_id', null);

        $this->assertDatabaseHas('guarantors', [
            'id' => $guarantor->id,
            'code' => 'ASR-EXISTING',
            'type' => 'private_insurance',
            'name' => 'New Name',
            'is_government' => false,
            'inacbg_id' => null,
        ]);
    }

    public function test_partial_name_only_update_preserves_all_omitted_fields(): void
    {
        $guarantor = Guarantor::factory()->create([
            'code' => 'ASR-KEEP',
            'type' => GuarantorType::PrivateInsurance,
            'name' => 'Asuransi Lama',
            'is_government' => true,
            'inacbg_id' => '77',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)->putJson(
            "/api/v1/master-data/guarantors/{$guarantor->id}",
            ['name' => '  Asuransi   Diperbarui  '],
        );

        $response->assertOk()
            ->assertJsonPath('data.code', 'ASR-KEEP')
            ->assertJsonPath('data.type', 'private_insurance')
            ->assertJsonPath('data.name', 'Asuransi Diperbarui')
            ->assertJsonPath('data.is_government', true)
            ->assertJsonPath('data.inacbg_id', '77')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('guarantors', [
            'id' => $guarantor->id,
            'code' => 'ASR-KEEP',
            'type' => 'private_insurance',
            'name' => 'Asuransi Diperbarui',
            'is_government' => true,
            'inacbg_id' => '77',
            'is_active' => false,
        ]);
    }

    public function test_update_validates_duplicate_code_empty_code_and_invalid_type(): void
    {
        Guarantor::factory()->create(['code' => 'ASR-TAKEN']);
        $guarantor = Guarantor::factory()->create([
            'code' => 'ASR-OWN',
            'type' => GuarantorType::Bpjs,
            'name' => 'BPJS Lama',
        ]);

        // Keeping own code succeeds
        $this->actingAs($this->user)
            ->putJson("/api/v1/master-data/guarantors/{$guarantor->id}", [
                'code' => 'asr-own',
                'name' => 'BPJS Diperbarui',
            ])
            ->assertOk()
            ->assertJsonPath('data.code', 'ASR-OWN')
            ->assertJsonPath('data.name', 'BPJS Diperbarui');

        // Duplicate code fails 422
        $this->actingAs($this->user)
            ->putJson("/api/v1/master-data/guarantors/{$guarantor->id}", [
                'code' => 'ASR-TAKEN',
                'name' => 'BPJS Duplikat',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);

        // Explicit empty code fails 422 (does not regenerate ASR-...)
        $this->actingAs($this->user)
            ->putJson("/api/v1/master-data/guarantors/{$guarantor->id}", [
                'code' => '   ',
                'name' => 'BPJS Kosong',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);

        // Invalid type fails 422
        $this->actingAs($this->user)
            ->putJson("/api/v1/master-data/guarantors/{$guarantor->id}", [
                'type' => 'invalid_type',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_can_soft_delete_guarantor(): void
    {
        $guarantor = Guarantor::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/guarantors/{$guarantor->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('guarantors', ['id' => $guarantor->id]);
    }

    public function test_cannot_delete_internal_guarantor_when_referenced_by_internal_admission_or_patient_table(): void
    {
        $guarantor = Guarantor::factory()->create();

        Schema::create('outpatient_admissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guarantor_id')->nullable();
            $table->softDeletes();
        });

        try {
            DB::table('outpatient_admissions')->insert([
                'guarantor_id' => $guarantor->id,
                'deleted_at' => null,
            ]);

            $blockedResponse = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/guarantors/{$guarantor->id}");
            $blockedResponse->assertStatus(422)
                ->assertJsonPath('message', 'Penjamin tidak dapat dihapus karena sudah digunakan pada data pasien atau admisi.');

            $this->assertDatabaseHas('guarantors', [
                'id' => $guarantor->id,
                'deleted_at' => null,
            ]);

            DB::table('outpatient_admissions')->where('guarantor_id', $guarantor->id)->delete();

            $allowedResponse = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/guarantors/{$guarantor->id}");
            $allowedResponse->assertNoContent();
        } finally {
            Schema::dropIfExists('outpatient_admissions');
        }
    }
}
