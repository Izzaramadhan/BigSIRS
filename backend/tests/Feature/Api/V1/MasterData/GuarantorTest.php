<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\User;
use App\Models\Guarantor;
use App\Enums\GuarantorType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
                    '*' => ['id', 'code', 'name', 'type', 'type_label', 'is_active']
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total']
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
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/guarantors', $payload);

        $response->assertCreated();
        
        $this->assertDatabaseHas('guarantors', [
            'code' => 'ASR-01',
            'name' => 'Asuransi Umum',
            'type' => 'self_pay',
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
        ];

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/guarantors/{$guarantor->id}", $payload);

        $response->assertOk();
        $this->assertDatabaseHas('guarantors', [
            'id' => $guarantor->id,
            'code' => 'ASR-02',
            'name' => 'Asuransi Update',
            'type' => 'government',
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

    public function test_can_soft_delete_guarantor(): void
    {
        $guarantor = Guarantor::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/guarantors/{$guarantor->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('guarantors', ['id' => $guarantor->id]);
    }
}
