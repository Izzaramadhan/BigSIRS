<?php

namespace Tests\Feature\Logistics;

use App\Models\Employee;
use App\Models\Logistics\GoodsIssue;
use App\Models\Logistics\GoodsIssueItem;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\MedicineUnit;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GoodsIssueTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Warehouse $warehouseMain;

    protected Warehouse $warehouseOther;

    protected Medicine $medicineA;

    protected Medicine $medicineB;

    protected WarehouseStock $stockA;

    protected WarehouseStock $stockB;

    protected Employee $officer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Apt. Budi Santoso',
            'email' => 'budi.santoso@simrs.test',
        ]);

        $this->warehouseMain = Warehouse::create([
            'code' => 'GDF-01',
            'name' => 'Gudang Utama Farmasi',
            'description' => 'Gudang logistik farmasi pusat',
        ]);

        $this->warehouseOther = Warehouse::create([
            'code' => 'AP-RJ',
            'name' => 'Apotek Rawat Jalan',
            'description' => 'Apotek rawat jalan',
        ]);

        $unit = MedicineUnit::create(['name' => 'Tablet']);

        $this->medicineA = Medicine::create([
            'code' => 'MED-001',
            'name' => 'Paracetamol 500 mg Tablet',
            'medicine_unit_id' => $unit->id,
            'selling_price_per_unit' => 1000,
            'is_active' => true,
        ]);

        $this->medicineB = Medicine::create([
            'code' => 'MED-002',
            'name' => 'Amoxicillin 500 mg Kapsul',
            'medicine_unit_id' => $unit->id,
            'selling_price_per_unit' => 2500,
            'is_active' => true,
        ]);

        $this->stockA = WarehouseStock::create([
            'warehouse_id' => $this->warehouseMain->id,
            'medicine_id' => $this->medicineA->id,
            'quantity' => 100,
            'selling_price' => 1000,
            'batch_number' => 'PCT-202610',
            'expired_date' => Carbon::now()->addYear(),
        ]);

        $this->stockB = WarehouseStock::create([
            'warehouse_id' => $this->warehouseMain->id,
            'medicine_id' => $this->medicineB->id,
            'quantity' => 50,
            'selling_price' => 2500,
            'batch_number' => 'AMX-202611',
            'expired_date' => Carbon::now()->addYear(),
        ]);

        $this->officer = Employee::create([
            'name' => 'Apt. Budi Santoso, S.Farm',
            'position' => 'Apoteker Penanggung Jawab',
            'status' => 'active',
        ]);
    }

    /**
     * 1. Daftar pengeluaran membutuhkan autentikasi.
     */
    public function test_goods_issues_listing_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/logistics/goods-issues');
        $response->assertStatus(401);
    }

    /**
     * 2. Daftar mendukung filter dan pagination.
     */
    public function test_goods_issues_listing_supports_filtering_and_pagination(): void
    {
        Sanctum::actingAs($this->user);

        for ($i = 1; $i <= 15; $i++) {
            GoodsIssue::create([
                'issue_number' => sprintf('OUT-202610-%04d', $i),
                'issue_date' => Carbon::now()->subDays($i),
                'warehouse_id' => $this->warehouseMain->id,
                'officer_name' => $i % 2 === 0 ? 'Apt. Budi Santoso' : 'Siti Rahmawati',
                'purpose' => $i % 3 === 0 ? 'Barang Rusak' : 'Kebutuhan Internal',
                'purpose_code' => $i % 3 === 0 ? 'damaged' : 'internal',
                'status' => 'completed',
                'total_quantity' => 10,
                'total_value' => 10000,
                'created_by' => $this->user->id,
            ]);
        }

        // Test pagination limit 10
        $response = $this->getJson('/api/v1/logistics/goods-issues?per_page=10');
        $response->assertStatus(200);
        $response->assertJsonPath('meta.per_page', 10);
        $response->assertJsonPath('meta.total', 15);
        $this->assertCount(10, $response->json('data'));

        // Test filter by purpose
        $filterResponse = $this->getJson('/api/v1/logistics/goods-issues?purpose=Barang Rusak');
        $filterResponse->assertStatus(200);
        foreach ($filterResponse->json('data') as $row) {
            $this->assertEquals('Barang Rusak', $row['purpose']);
        }
    }

    /**
     * 3. Summary menghitung transaksi selesai dengan benar.
     */
    public function test_summary_calculates_completed_transactions_correctly(): void
    {
        Sanctum::actingAs($this->user);

        // Completed issue today
        $issueToday = GoodsIssue::create([
            'issue_number' => 'OUT-202610-0101',
            'issue_date' => Carbon::today()->setTime(10, 0),
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Kebutuhan Internal',
            'purpose_code' => 'internal',
            'status' => 'completed',
            'total_quantity' => 20,
            'created_by' => $this->user->id,
        ]);
        GoodsIssueItem::create([
            'goods_issue_id' => $issueToday->id,
            'warehouse_stock_id' => $this->stockA->id,
            'medicine_id' => $this->medicineA->id,
            'medicine_name' => $this->medicineA->name,
            'quantity' => 20,
            'unit' => 'Tablet',
        ]);

        // Completed damaged issue this month
        $issueDamaged = GoodsIssue::create([
            'issue_number' => 'OUT-202610-0102',
            'issue_date' => Carbon::now()->subDays(2),
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Barang Rusak',
            'purpose_code' => 'damaged',
            'status' => 'completed',
            'total_quantity' => 5,
            'created_by' => $this->user->id,
        ]);
        GoodsIssueItem::create([
            'goods_issue_id' => $issueDamaged->id,
            'warehouse_stock_id' => $this->stockA->id,
            'medicine_id' => $this->medicineA->id,
            'medicine_name' => $this->medicineA->name,
            'quantity' => 5,
            'unit' => 'Tablet',
        ]);

        // Draft issue should NOT be counted in summary
        $issueDraft = GoodsIssue::create([
            'issue_number' => 'OUT-202610-0103',
            'issue_date' => Carbon::today()->setTime(11, 0),
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Kebutuhan Internal',
            'status' => 'draft',
            'total_quantity' => 50,
            'created_by' => $this->user->id,
        ]);
        GoodsIssueItem::create([
            'goods_issue_id' => $issueDraft->id,
            'warehouse_stock_id' => $this->stockA->id,
            'medicine_id' => $this->medicineA->id,
            'medicine_name' => $this->medicineA->name,
            'quantity' => 50,
            'unit' => 'Tablet',
        ]);

        $response = $this->getJson('/api/v1/logistics/goods-issues/summary');
        $response->assertStatus(200);
        $response->assertJsonPath('data.todayIssues', '1');
        $response->assertJsonPath('data.totalItemOut', '25');
        $response->assertJsonPath('data.damagedItem', '5');
    }

    /**
     * 4. Draft dapat dibuat tanpa mengurangi stok.
     */
    public function test_draft_can_be_created(): void
    {
        Sanctum::actingAs($this->user);

        $initialStock = (float) $this->stockA->quantity;

        $payload = [
            'warehouse_id' => $this->warehouseMain->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'time' => '09:00',
            'officer_name' => 'Apt. Budi Santoso, S.Farm',
            'purpose' => 'Kebutuhan Internal',
            'reference_number' => 'REQ-001',
            'notes' => 'Draft pengeluaran',
            'is_draft' => true,
            'items' => [
                [
                    'warehouse_stock_id' => $this->stockA->id,
                    'medicine_id' => $this->medicineA->id,
                    'batch_number' => $this->stockA->batch_number,
                    'quantity' => 15,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/logistics/goods-issues', $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'Draft');

        // Verify stock is untouched
        $this->assertEquals($initialStock, (float) $this->stockA->fresh()->quantity);
        // Verify no stock movements recorded
        $this->assertDatabaseMissing('stock_movements', [
            'goods_issue_id' => $response->json('data.id'),
        ]);
    }

    /**
     * 5. Draft dapat diedit.
     */
    public function test_draft_can_be_edited(): void
    {
        Sanctum::actingAs($this->user);

        $issue = GoodsIssue::create([
            'issue_number' => 'OUT-202610-0201',
            'issue_date' => Carbon::now(),
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Kebutuhan Internal',
            'status' => 'draft',
            'total_quantity' => 10,
            'created_by' => $this->user->id,
        ]);
        GoodsIssueItem::create([
            'goods_issue_id' => $issue->id,
            'warehouse_stock_id' => $this->stockA->id,
            'medicine_id' => $this->medicineA->id,
            'medicine_name' => $this->medicineA->name,
            'quantity' => 10,
        ]);

        $updatePayload = [
            'warehouse_id' => $this->warehouseMain->id,
            'purpose' => 'Kegiatan Sosial',
            'officer_name' => 'Apt. Budi Santoso',
            'is_draft' => true,
            'items' => [
                [
                    'warehouse_stock_id' => $this->stockA->id,
                    'medicine_id' => $this->medicineA->id,
                    'batch_number' => $this->stockA->batch_number,
                    'quantity' => 25,
                ],
            ],
        ];

        $response = $this->putJson("/api/v1/logistics/goods-issues/{$issue->id}", $updatePayload);
        $response->assertStatus(200);
        $response->assertJsonPath('data.purpose', 'Kegiatan Sosial');
        $response->assertJsonPath('data.totalQuantity', 25);
    }

    /**
     * 6. Draft dapat dihapus atau dibatalkan sesuai aturan.
     */
    public function test_draft_can_be_deleted_or_cancelled_according_to_rules(): void
    {
        Sanctum::actingAs($this->user);

        $issue = GoodsIssue::create([
            'issue_number' => 'OUT-202610-0202',
            'issue_date' => Carbon::now(),
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Kebutuhan Internal',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);
        GoodsIssueItem::create([
            'goods_issue_id' => $issue->id,
            'warehouse_stock_id' => $this->stockA->id,
            'medicine_id' => $this->medicineA->id,
            'medicine_name' => $this->medicineA->name,
            'quantity' => 10,
        ]);

        $deleteResponse = $this->deleteJson("/api/v1/logistics/goods-issues/{$issue->id}");
        $deleteResponse->assertStatus(200);

        $this->assertDatabaseMissing('goods_issues', ['id' => $issue->id]);
        $this->assertDatabaseMissing('goods_issue_items', ['goods_issue_id' => $issue->id]);
    }

    /**
     * 7. Finalisasi mengurangi stok yang benar.
     */
    public function test_finalization_reduces_the_correct_stock(): void
    {
        Sanctum::actingAs($this->user);

        $initialStock = (float) $this->stockA->quantity; // 100

        $issue = GoodsIssue::create([
            'issue_number' => 'OUT-202610-0203',
            'issue_date' => Carbon::now(),
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Kebutuhan Internal',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);
        GoodsIssueItem::create([
            'goods_issue_id' => $issue->id,
            'warehouse_stock_id' => $this->stockA->id,
            'medicine_id' => $this->medicineA->id,
            'medicine_name' => $this->medicineA->name,
            'batch_number' => $this->stockA->batch_number,
            'quantity' => 30,
        ]);

        $response = $this->postJson("/api/v1/logistics/goods-issues/{$issue->id}/finalize");
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'Selesai');

        $this->assertEquals($initialStock - 30, (float) $this->stockA->fresh()->quantity);
    }

    /**
     * 8. Finalisasi mencatat inventory movement/kartu stok.
     */
    public function test_finalization_records_inventory_movement_kartu_stok(): void
    {
        Sanctum::actingAs($this->user);

        $issue = GoodsIssue::create([
            'issue_number' => 'OUT-202610-0204',
            'issue_date' => Carbon::now(),
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Kebutuhan Internal',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);
        $item = GoodsIssueItem::create([
            'goods_issue_id' => $issue->id,
            'warehouse_stock_id' => $this->stockA->id,
            'medicine_id' => $this->medicineA->id,
            'medicine_name' => $this->medicineA->name,
            'batch_number' => $this->stockA->batch_number,
            'quantity' => 12,
        ]);

        $this->postJson("/api/v1/logistics/goods-issues/{$issue->id}/finalize")->assertStatus(200);

        $this->assertDatabaseHas('stock_movements', [
            'goods_issue_id' => $issue->id,
            'goods_issue_item_id' => $item->id,
            'movement_type' => 'goods_issue',
            'direction' => 'OUT',
            'quantity' => 12,
            'balance_before' => 100,
            'balance_after' => 88,
        ]);
    }

    /**
     * 9. Finalisasi ditolak jika stok tidak mencukupi.
     */
    public function test_finalization_rejected_if_stock_is_insufficient(): void
    {
        Sanctum::actingAs($this->user);

        $issue = GoodsIssue::create([
            'issue_number' => 'OUT-202610-0205',
            'issue_date' => Carbon::now(),
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Kebutuhan Internal',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);
        GoodsIssueItem::create([
            'goods_issue_id' => $issue->id,
            'warehouse_stock_id' => $this->stockA->id,
            'medicine_id' => $this->medicineA->id,
            'medicine_name' => $this->medicineA->name,
            'batch_number' => $this->stockA->batch_number,
            'quantity' => 500, // Stock only 100
        ]);

        $response = $this->postJson("/api/v1/logistics/goods-issues/{$issue->id}/finalize");
        $response->assertStatus(422);

        // Verify stock unchanged
        $this->assertEquals(100, (float) $this->stockA->fresh()->quantity);
        $this->assertEquals('draft', $issue->fresh()->status);
    }

    /**
     * 10. Finalisasi gagal melakukan rollback seluruh transaksi.
     */
    public function test_failed_finalization_rolls_back_entire_transaction(): void
    {
        Sanctum::actingAs($this->user);

        $issue = GoodsIssue::create([
            'issue_number' => 'OUT-202610-0206',
            'issue_date' => Carbon::now(),
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Kebutuhan Internal',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);
        // Valid item 1
        GoodsIssueItem::create([
            'goods_issue_id' => $issue->id,
            'warehouse_stock_id' => $this->stockA->id,
            'medicine_id' => $this->medicineA->id,
            'medicine_name' => $this->medicineA->name,
            'batch_number' => $this->stockA->batch_number,
            'quantity' => 10,
        ]);
        // Invalid item 2 (excessive quantity)
        GoodsIssueItem::create([
            'goods_issue_id' => $issue->id,
            'warehouse_stock_id' => $this->stockB->id,
            'medicine_id' => $this->medicineB->id,
            'medicine_name' => $this->medicineB->name,
            'batch_number' => $this->stockB->batch_number,
            'quantity' => 9999,
        ]);

        $response = $this->postJson("/api/v1/logistics/goods-issues/{$issue->id}/finalize");
        $response->assertStatus(422);

        // Item 1 stock must NOT be reduced due to rollback
        $this->assertEquals(100, (float) $this->stockA->fresh()->quantity);
        $this->assertEquals(50, (float) $this->stockB->fresh()->quantity);
        $this->assertDatabaseMissing('stock_movements', ['goods_issue_id' => $issue->id]);
    }

    /**
     * 11. Request finalisasi ganda tidak mengurangi stok dua kali.
     */
    public function test_duplicate_finalization_request_does_not_reduce_stock_twice(): void
    {
        Sanctum::actingAs($this->user);

        $issue = GoodsIssue::create([
            'issue_number' => 'OUT-202610-0207',
            'issue_date' => Carbon::now(),
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Kebutuhan Internal',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);
        GoodsIssueItem::create([
            'goods_issue_id' => $issue->id,
            'warehouse_stock_id' => $this->stockA->id,
            'medicine_id' => $this->medicineA->id,
            'medicine_name' => $this->medicineA->name,
            'batch_number' => $this->stockA->batch_number,
            'quantity' => 20,
        ]);

        // First finalization
        $this->postJson("/api/v1/logistics/goods-issues/{$issue->id}/finalize")->assertStatus(200);
        $this->assertEquals(80, (float) $this->stockA->fresh()->quantity);

        // Second duplicate finalization attempt
        $secondResponse = $this->postJson("/api/v1/logistics/goods-issues/{$issue->id}/finalize");
        // Status policy rejects finalize on non-draft or service handles idempotently
        $this->assertTrue(in_array($secondResponse->status(), [200, 403]));

        // Stock remains 80, not 60
        $this->assertEquals(80, (float) $this->stockA->fresh()->quantity);
    }

    /**
     * 12. Dokumen selesai tidak dapat diedit.
     */
    public function test_completed_document_cannot_be_edited(): void
    {
        Sanctum::actingAs($this->user);

        $issue = GoodsIssue::create([
            'issue_number' => 'OUT-202610-0208',
            'issue_date' => Carbon::now(),
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Kebutuhan Internal',
            'status' => 'completed',
            'created_by' => $this->user->id,
        ]);

        $response = $this->putJson("/api/v1/logistics/goods-issues/{$issue->id}", [
            'purpose' => 'Barang Rusak',
        ]);
        $this->assertContains($response->status(), [403, 422]);
    }

    /**
     * 13. Pembatalan dokumen selesai mengembalikan stok.
     */
    public function test_cancellation_of_completed_document_restores_stock(): void
    {
        Sanctum::actingAs($this->user);

        // Create and finalize issue
        $issue = GoodsIssue::create([
            'issue_number' => 'OUT-202610-0209',
            'issue_date' => Carbon::now(),
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Kebutuhan Internal',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);
        GoodsIssueItem::create([
            'goods_issue_id' => $issue->id,
            'warehouse_stock_id' => $this->stockA->id,
            'medicine_id' => $this->medicineA->id,
            'medicine_name' => $this->medicineA->name,
            'batch_number' => $this->stockA->batch_number,
            'quantity' => 15,
        ]);
        $this->postJson("/api/v1/logistics/goods-issues/{$issue->id}/finalize")->assertStatus(200);
        $this->assertEquals(85, (float) $this->stockA->fresh()->quantity);

        // Cancel the completed issue
        $cancelResponse = $this->postJson("/api/v1/logistics/goods-issues/{$issue->id}/cancel", [
            'reason' => 'Salah alokasi unit tujuan',
        ]);
        $cancelResponse->assertStatus(200);
        $cancelResponse->assertJsonPath('data.status', 'Dibatalkan');

        // Stock restored to 100
        $this->assertEquals(100, (float) $this->stockA->fresh()->quantity);

        // Verify reversal movement
        $this->assertDatabaseHas('stock_movements', [
            'goods_issue_id' => $issue->id,
            'movement_type' => 'goods_issue_cancellation',
            'direction' => 'IN',
            'quantity' => 15,
            'balance_after' => 100,
        ]);
    }

    /**
     * 14. Transaksi dibatalkan tidak dapat dibatalkan kembali.
     */
    public function test_cancelled_transaction_cannot_be_cancelled_again(): void
    {
        Sanctum::actingAs($this->user);

        $issue = GoodsIssue::create([
            'issue_number' => 'OUT-202610-0210',
            'issue_date' => Carbon::now(),
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Kebutuhan Internal',
            'status' => 'cancelled',
            'cancellation_reason' => 'Sudah dibatalkan sebelumnya',
            'created_by' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/logistics/goods-issues/{$issue->id}/cancel", [
            'reason' => 'Batalkan lagi',
        ]);
        $this->assertContains($response->status(), [403, 422]);
    }

    /**
     * 15. Sediaan dari gudang lain ditolak.
     */
    public function test_item_from_another_warehouse_is_rejected(): void
    {
        Sanctum::actingAs($this->user);

        // Create stock in warehouseOther
        $stockOther = WarehouseStock::create([
            'warehouse_id' => $this->warehouseOther->id,
            'medicine_id' => $this->medicineA->id,
            'quantity' => 50,
            'selling_price' => 1000,
            'batch_number' => 'OTHER-BATCH-01',
            'expired_date' => Carbon::now()->addYear(),
        ]);

        // Attempt to create and finalize with stock from warehouseOther while header is warehouseMain
        $issue = GoodsIssue::create([
            'issue_number' => 'OUT-202610-0211',
            'issue_date' => Carbon::now(),
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Kebutuhan Internal',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);
        GoodsIssueItem::create([
            'goods_issue_id' => $issue->id,
            'warehouse_stock_id' => $stockOther->id,
            'medicine_id' => $this->medicineA->id,
            'medicine_name' => $this->medicineA->name,
            'batch_number' => $stockOther->batch_number,
            'quantity' => 10,
        ]);

        $response = $this->postJson("/api/v1/logistics/goods-issues/{$issue->id}/finalize");
        $response->assertStatus(422);
    }

    /**
     * 16. Quantity nol atau negatif ditolak.
     */
    public function test_zero_or_negative_quantity_is_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $payloadZero = [
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Kebutuhan Internal',
            'items' => [
                [
                    'medicine_id' => $this->medicineA->id,
                    'quantity' => 0,
                ],
            ],
        ];

        $resZero = $this->postJson('/api/v1/logistics/goods-issues', $payloadZero);
        $resZero->assertStatus(422);

        $payloadNegative = [
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Kebutuhan Internal',
            'items' => [
                [
                    'medicine_id' => $this->medicineA->id,
                    'quantity' => -5,
                ],
            ],
        ];

        $resNeg = $this->postJson('/api/v1/logistics/goods-issues', $payloadNegative);
        $resNeg->assertStatus(422);
    }

    /**
     * 17. Header pengeluaran dapat dibuat tanpa items.
     */
    public function test_goods_issue_header_can_be_created_without_items(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'issue_date' => Carbon::now()->format('Y-m-d'),
            'officer_id' => $this->officer->id,
            'notes' => 'Keperluan kegiatan donor darah',
        ];

        $res = $this->postJson('/api/v1/logistics/goods-issues', $payload);
        $res->assertStatus(201);
        $res->assertJsonPath('data.officerName', $this->officer->name);
        $res->assertJsonPath('data.notes', 'Keperluan kegiatan donor darah');
        $res->assertJsonPath('data.totalQuantity', 0);
    }

    /**
     * 18. Item pengeluaran dapat ditambahkan, diperbarui, dan dihapus.
     */
    public function test_goods_issue_item_can_be_added_updated_and_deleted(): void
    {
        Sanctum::actingAs($this->user);

        $issue = GoodsIssue::create([
            'issue_number' => 'OUT-202610-0999',
            'issue_date' => Carbon::now(),
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Kebutuhan Internal',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        // Add item
        $addRes = $this->postJson("/api/v1/logistics/goods-issues/{$issue->id}/items", [
            'medicine_id' => $this->medicineA->id,
            'warehouse_id' => $this->warehouseMain->id,
            'quantity' => 5,
        ]);
        $addRes->assertStatus(201);
        $itemId = $addRes->json('data.id');

        $this->assertEquals(5, (float) $issue->fresh()->total_quantity);
        $this->assertEquals(5000, (float) $issue->fresh()->total_value);

        // Update item quantity
        $updateRes = $this->putJson("/api/v1/logistics/goods-issues/{$issue->id}/items/{$itemId}", [
            'quantity' => 8,
        ]);
        $updateRes->assertStatus(200);
        $this->assertEquals(8, (float) $issue->fresh()->total_quantity);
        $this->assertEquals(8000, (float) $issue->fresh()->total_value);

        // Delete item
        $delRes = $this->deleteJson("/api/v1/logistics/goods-issues/{$issue->id}/items/{$itemId}");
        $delRes->assertStatus(200);
        $this->assertEquals(0, (float) $issue->fresh()->total_quantity);
    }

    /**
     * 19. Tambah item ditolak jika stok tidak mencukupi.
     */
    public function test_adding_item_with_insufficient_stock_is_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $issue = GoodsIssue::create([
            'issue_number' => 'OUT-202610-1000',
            'issue_date' => Carbon::now(),
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Kebutuhan Internal',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        // Stock A only has 100
        $res = $this->postJson("/api/v1/logistics/goods-issues/{$issue->id}/items", [
            'medicine_id' => $this->medicineA->id,
            'warehouse_id' => $this->warehouseMain->id,
            'quantity' => 9999,
        ]);
        $res->assertStatus(422);
    }

    /**
     * 20. Endpoint available-stocks mengembalikan persediaan gudang beserta field lengkap.
     */
    public function test_available_stocks_endpoint_returns_stocks_for_selected_warehouse(): void
    {
        Sanctum::actingAs($this->user);

        // Fetch stocks for warehouseMain
        $res = $this->getJson("/api/v1/logistics/warehouses/{$this->warehouseMain->id}/available-stocks");
        $res->assertStatus(200);
        $res->assertJsonStructure([
            'data' => [
                '*' => [
                    'stock_id',
                    'item_id',
                    'item_code',
                    'item_name',
                    'unit_id',
                    'unit_name',
                    'batch_number',
                    'expiry_date',
                    'available_stock',
                    'selling_price',
                    'warehouse_id',
                    'warehouse_name',
                ],
            ],
        ]);

        $this->assertCount(2, $res->json('data'));

        // Search by medicine name
        $searchRes = $this->getJson("/api/v1/logistics/warehouses/{$this->warehouseMain->id}/available-stocks?search=Paracetamol");
        $searchRes->assertStatus(200);
        $this->assertCount(1, $searchRes->json('data'));
        $this->assertEquals('Paracetamol 500 mg Tablet', $searchRes->json('data.0.item_name'));

        // Search by batch
        $batchRes = $this->getJson("/api/v1/logistics/warehouses/{$this->warehouseMain->id}/available-stocks?search=AMX-202611");
        $batchRes->assertStatus(200);
        $this->assertCount(1, $batchRes->json('data'));
        $this->assertEquals('Amoxicillin 500 mg Kapsul', $batchRes->json('data.0.item_name'));

        // Warehouse with zero stock
        $emptyRes = $this->getJson("/api/v1/logistics/warehouses/{$this->warehouseOther->id}/available-stocks");
        $emptyRes->assertStatus(200);
        $this->assertCount(0, $emptyRes->json('data'));
    }

    /**
     * 21. Penambahan item dengan stock_id memvalidasi kepemilikan gudang.
     */
    public function test_adding_item_with_stock_id_validates_warehouse_ownership(): void
    {
        Sanctum::actingAs($this->user);

        // Create stock in warehouseOther
        $otherStock = WarehouseStock::create([
            'warehouse_id' => $this->warehouseOther->id,
            'medicine_id' => $this->medicineA->id,
            'quantity' => 20,
            'selling_price' => 1200,
            'batch_number' => 'OTHER-BATCH',
            'expired_date' => Carbon::now()->addYear(),
        ]);

        $issue = GoodsIssue::create([
            'issue_number' => 'OUT-202610-1001',
            'issue_date' => Carbon::now(),
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Kebutuhan Internal',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        // Trying to use otherStock (which belongs to warehouseOther) for warehouseMain issue should fail
        $failRes = $this->postJson("/api/v1/logistics/goods-issues/{$issue->id}/items", [
            'medicine_id' => $this->medicineA->id,
            'stock_id' => $otherStock->id,
            'warehouse_id' => $this->warehouseMain->id,
            'quantity' => 2,
        ]);
        $failRes->assertStatus(422);

        // Using valid stockA for warehouseMain succeeds and preserves batch and price
        $okRes = $this->postJson("/api/v1/logistics/goods-issues/{$issue->id}/items", [
            'medicine_id' => $this->medicineA->id,
            'stock_id' => $this->stockA->id,
            'warehouse_id' => $this->warehouseMain->id,
            'quantity' => 3,
        ]);
        $okRes->assertStatus(201);
        $this->assertEquals('PCT-202610', $okRes->json('data.batchNumber'));
        $this->assertEquals(1000, $okRes->json('data.unitPrice'));
    }

    /**
     * 22. Endpoint available-stocks memfilter stok kedaluwarsa terhadap tanggal transaksi.
     */
    public function test_available_stocks_endpoint_filters_out_expired_stocks(): void
    {
        Sanctum::actingAs($this->user);

        // Create expired stock in warehouseMain
        $expiredStock = WarehouseStock::create([
            'warehouse_id' => $this->warehouseMain->id,
            'medicine_id' => $this->medicineA->id,
            'quantity' => 10,
            'selling_price' => 1000,
            'batch_number' => 'EXP-20231231',
            'expired_date' => Carbon::parse('2023-12-31'),
        ]);

        // When requesting for transaction year 2026, the expired stock must not be returned
        $res = $this->getJson("/api/v1/logistics/warehouses/{$this->warehouseMain->id}/available-stocks?issue_date=2026-10-30");
        $res->assertStatus(200);

        $returnedBatches = collect($res->json('data'))->pluck('batch_number')->all();
        $this->assertNotContains('EXP-20231231', $returnedBatches);
        $this->assertContains('PCT-202610', $returnedBatches);
    }

    /**
     * 23. Penambahan item dengan stok kedaluwarsa ditolak backend.
     */
    public function test_adding_expired_stock_item_is_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $expiredStock = WarehouseStock::create([
            'warehouse_id' => $this->warehouseMain->id,
            'medicine_id' => $this->medicineA->id,
            'quantity' => 10,
            'selling_price' => 1000,
            'batch_number' => 'EXP-BATCH',
            'expired_date' => Carbon::parse('2023-12-31'),
        ]);

        $issue = GoodsIssue::create([
            'issue_number' => 'OUT-202610-1002',
            'issue_date' => Carbon::parse('2026-10-30'),
            'warehouse_id' => $this->warehouseMain->id,
            'officer_name' => 'Apt. Budi Santoso',
            'purpose' => 'Kebutuhan Internal',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $res = $this->postJson("/api/v1/logistics/goods-issues/{$issue->id}/items", [
            'medicine_id' => $this->medicineA->id,
            'stock_id' => $expiredStock->id,
            'warehouse_id' => $this->warehouseMain->id,
            'quantity' => 1,
        ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['stock_id']);
    }
}
