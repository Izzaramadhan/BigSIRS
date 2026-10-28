<?php

namespace Tests\Feature\Logistics;

use App\Models\Logistics\Invoice;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\Supplier;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Supplier $supplier;

    protected Warehouse $warehouse;

    protected Medicine $medicine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->supplier = Supplier::create([
            'name' => 'PT Kalbe Farma Tbk',
            'phone' => '021-89907333',
            'address' => 'Cikarang Delta Silicon',
            'is_active' => true,
        ]);
        $this->warehouse = Warehouse::create([
            'code' => 'GDF',
            'name' => 'Gudang Utama',
            'description' => 'Gudang Utama Farmasi',
        ]);
        $this->medicine = Medicine::create([
            'name' => 'Paracetamol 500 mg Tablet',
            'code' => 'MED-001',
            'selling_price_per_unit' => 50000,
            'is_active' => true,
        ]);
    }

    // 1. Pengguna tanpa autentikasi tidak dapat menyimpan Faktur.
    public function test_unauthenticated_user_cannot_store_invoice(): void
    {
        $response = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-UNAUTH',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 1, 'price' => 10000],
            ],
        ]);

        $response->assertStatus(401);
    }

    // 2. Supplier tidak valid ditolak.
    public function test_invalid_supplier_is_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-INVALID-SUP',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => 999999,
            'items' => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 1, 'price' => 10000],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['supplier_id']);
    }

    // 3. Gudang tidak valid ditolak jika digunakan.
    public function test_invalid_warehouse_is_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-INVALID-WH',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => 999999,
            'items' => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 1, 'price' => 10000],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['warehouse_id']);
    }

    // 4. Faktur tanpa detail ditolak.
    public function test_invoice_without_items_is_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-NO-ITEMS',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'items' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    // 5. Obat tidak valid ditolak.
    public function test_invalid_medicine_in_items_is_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-INVALID-MED',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['medicine_id' => 888888, 'quantity' => 5, 'price' => 10000],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.medicine_id']);
    }

    // 6. Quantity nol atau negatif ditolak.
    public function test_zero_or_negative_quantity_is_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $responseZero = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-ZERO-QTY',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 0, 'price' => 10000],
            ],
        ]);
        $responseZero->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);

        $responseNegative = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-NEG-QTY',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['medicine_id' => $this->medicine->id, 'quantity' => -5, 'price' => 10000],
            ],
        ]);
        $responseNegative->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    // 7. Tanggal jatuh tempo sebelum Tanggal Faktur ditolak.
    public function test_due_date_before_invoice_date_is_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-BEFORE-DUE',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-10-20',
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 1, 'price' => 10000],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }

    // 8. Tanggal kedaluwarsa tidak valid ditolak.
    public function test_invalid_expired_date_format_is_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-BAD-EXP',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'items' => [
                [
                    'medicine_id' => $this->medicine->id,
                    'quantity' => 1,
                    'price' => 10000,
                    'expired_date' => 'not-a-valid-date',
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.expired_date']);
    }

    // 9. Total dihitung ulang oleh backend (mengabaikan manipulasi frontend).
    public function test_total_amount_is_recalculated_by_backend(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-RECALC-001',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'total_amount' => 1, // Manipulated frontend total
            'items' => [
                [
                    'medicine_id' => $this->medicine->id,
                    'quantity' => 10,
                    'price' => 50000,
                    'discount' => 0,
                ],
            ],
        ]);

        $response->assertStatus(201);
        // Correct backend computed total is 10 * 50,000 = 500,000
        $this->assertEquals(500000, (float) $response->json('data.totalAmount'));
    }

    // 10. PPN dihitung dengan benar (11%).
    public function test_ppn_tax_is_calculated_correctly(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-PPN-001',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'tax_type' => 'ppn_11',
            'items' => [
                [
                    'medicine_id' => $this->medicine->id,
                    'quantity' => 10,
                    'price' => 100000,
                    'discount' => 0,
                ],
            ],
        ]);

        $response->assertStatus(201);
        // Subtotal = 1,000,000. PPN 11% = 110,000. Total = 1,110,000
        $this->assertEquals(1000000, (float) $response->json('data.subtotalAmount'));
        $this->assertEquals(110000, (float) $response->json('data.taxAmount'));
        $this->assertEquals(1110000, (float) $response->json('data.totalAmount'));
    }

    // 11. Diskon dihitung dengan benar (persentase dan nominal).
    public function test_discount_is_calculated_correctly(): void
    {
        Sanctum::actingAs($this->user);

        // Test percentage discount: 10 * 50,000 = 500,000 with 10% discount = 450,000
        $resPct = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-DISC-PCT',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'items' => [
                [
                    'medicine_id' => $this->medicine->id,
                    'quantity' => 10,
                    'price' => 50000,
                    'discount_type' => 'percentage',
                    'discount' => 10,
                ],
            ],
        ]);

        $resPct->assertStatus(201);
        $this->assertEquals(50000, (float) $resPct->json('data.discountAmount'));
        $this->assertEquals(450000, (float) $resPct->json('data.totalAmount'));

        // Test nominal discount: 5 * 100,000 = 500,000 with 50,000 nominal discount = 450,000
        $resNom = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-DISC-NOM',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'items' => [
                [
                    'medicine_id' => $this->medicine->id,
                    'quantity' => 5,
                    'price' => 100000,
                    'discount_type' => 'nominal',
                    'discount' => 50000,
                ],
            ],
        ]);

        $resNom->assertStatus(201);
        $this->assertEquals(50000, (float) $resNom->json('data.discountAmount'));
        $this->assertEquals(450000, (float) $resNom->json('data.totalAmount'));
    }

    // 12. Header dan detail tersimpan dalam satu transaction.
    public function test_header_and_items_saved_in_transaction(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-TX-001',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'medicine_id' => $this->medicine->id,
                    'quantity' => 2,
                    'price' => 25000,
                    'batch_number' => 'BATCH-TX',
                    'expired_date' => '2028-10-28',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $invoiceId = $response->json('data.id');

        $this->assertDatabaseHas('invoices', ['id' => $invoiceId, 'invoice_number' => 'FKT-TX-001']);
        $this->assertDatabaseHas('invoice_items', ['invoice_id' => $invoiceId, 'batch_number' => 'BATCH-TX']);
    }

    // 13. Kegagalan satu detail melakukan rollback seluruh transaksi.
    public function test_failure_in_items_rolls_back_transaction(): void
    {
        Sanctum::actingAs($this->user);

        $beforeCount = Invoice::count();

        // One valid item, one invalid quantity
        $response = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-FAIL-TX',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 5, 'price' => 20000],
                ['medicine_id' => $this->medicine->id, 'quantity' => -1, 'price' => 20000],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertEquals($beforeCount, Invoice::count());
        $this->assertDatabaseMissing('invoices', ['invoice_number' => 'FKT-FAIL-TX']);
    }

    // 14. Update Faktur mempertahankan konsistensi detail.
    public function test_update_invoice_maintains_detail_consistency(): void
    {
        Sanctum::actingAs($this->user);

        $invoice = Invoice::create([
            'invoice_number' => 'FKT-ORIGINAL',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'total_amount' => 100000,
        ]);
        $invoice->items()->create([
            'medicine_id' => $this->medicine->id,
            'medicine_name' => 'Old Item',
            'quantity' => 1,
            'price' => 100000,
            'subtotal' => 100000,
        ]);

        $response = $this->putJson("/api/v1/logistics/invoices/{$invoice->id}", [
            'invoice_number' => 'FKT-ORIGINAL',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'status' => 'Lunas',
            'items' => [
                [
                    'medicine_id' => $this->medicine->id,
                    'medicine_name' => 'Updated Item',
                    'quantity' => 4,
                    'price' => 50000,
                    'discount' => 0,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertEquals(200000, (float) $response->json('data.totalAmount'));
        $this->assertEquals(1, $invoice->items()->count());
        $this->assertEquals(4, (float) $invoice->items()->first()->quantity);
    }

    // 15. Status Lunas dan sisa tagihan konsisten.
    public function test_payment_status_consistency(): void
    {
        Sanctum::actingAs($this->user);

        // When status is Lunas: paid_amount equals total_amount, remaining is 0
        $resLunas = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-STATUS-LUNAS',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'status' => 'Lunas',
            'items' => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 2, 'price' => 50000],
            ],
        ]);

        $resLunas->assertStatus(201);
        $this->assertEquals('Lunas', $resLunas->json('data.status'));
        $this->assertEquals($resLunas->json('data.totalAmount'), $resLunas->json('data.paidAmount'));

        // When status is Belum Lunas: paid_amount is 0
        $resBelum = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-STATUS-BELUM',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'status' => 'Belum Lunas',
            'items' => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 2, 'price' => 50000],
            ],
        ]);

        $resBelum->assertStatus(201);
        $this->assertEquals('Belum Lunas', $resBelum->json('data.status'));
        $this->assertEquals(0, (float) $resBelum->json('data.paidAmount'));
    }

    // 16. Faktur Belum Lunas tanpa Jatuh Tempo ditolak.
    public function test_unpaid_invoice_without_due_date_is_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-UNPAID-NODUE',
            'invoice_date' => '2026-10-28',
            'status' => 'Belum Lunas',
            // due_date omitted
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 1, 'price' => 10000],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }

    // 17. Faktur Belum Lunas dengan Jatuh Tempo valid diterima.
    public function test_unpaid_invoice_with_valid_due_date_is_accepted(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-UNPAID-VALID',
            'invoice_date' => '2026-10-28',
            'status' => 'Belum Lunas',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 1, 'price' => 10000],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertEquals('2026-11-28', $response->json('data.dueDate'));
        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'FKT-UNPAID-VALID',
            'due_date' => '2026-11-28',
            'status' => 'Belum Lunas',
        ]);
    }

    // 18. Faktur Lunas tanpa Jatuh Tempo diterima.
    public function test_paid_invoice_without_due_date_is_accepted(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-PAID-NODUE',
            'invoice_date' => '2026-10-28',
            'status' => 'Lunas',
            // due_date omitted
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 1, 'price' => 10000],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertNull($response->json('data.dueDate'));
        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'FKT-PAID-NODUE',
            'due_date' => null,
            'status' => 'Lunas',
        ]);
    }

    // 19. Jika Faktur Lunas menerima due_date, backend menormalisasikannya menjadi null.
    public function test_paid_invoice_with_due_date_is_normalized_to_null(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-PAID-WITHDUE',
            'invoice_date' => '2026-10-28',
            'status' => 'Lunas',
            'due_date' => '2026-11-28', // Old date sent by client
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 1, 'price' => 10000],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertNull($response->json('data.dueDate'));
        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'FKT-PAID-WITHDUE',
            'due_date' => null,
            'status' => 'Lunas',
        ]);
    }

    // 20. Faktur Lunas tidak dianggap overdue dalam kalkulasi summary.
    public function test_paid_invoice_is_never_considered_overdue(): void
    {
        Sanctum::actingAs($this->user);

        // Paid invoice with past date
        Invoice::create([
            'invoice_number' => 'FKT-PAID-OLD',
            'invoice_date' => '2020-01-01',
            'due_date' => null,
            'supplier_id' => $this->supplier->id,
            'status' => 'Lunas',
            'total_amount' => 50000,
            'paid_amount' => 50000,
        ]);

        // Unpaid invoice with past date
        Invoice::create([
            'invoice_number' => 'FKT-UNPAID-OLD',
            'invoice_date' => '2020-01-01',
            'due_date' => '2020-01-15',
            'supplier_id' => $this->supplier->id,
            'status' => 'Belum Lunas',
            'total_amount' => 50000,
            'paid_amount' => 0,
        ]);

        $response = $this->getJson('/api/v1/logistics/invoices/summary');
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('overdue_count'));
    }

    // 21. Request tidak lagi mewajibkan Gudang Tujuan.
    public function test_invoice_creation_without_warehouse_id_succeeds(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-NO-WH',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            // warehouse_id omitted
            'items' => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 1, 'price' => 10000],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'FKT-NO-WH',
        ]);
    }

    // 22. Request tidak lagi mewajibkan Nomor PO.
    public function test_invoice_creation_without_po_number_succeeds(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/logistics/invoices', [
            'invoice_number' => 'FKT-NO-PO',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            // reference_number / po omitted
            'items' => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 1, 'price' => 10000],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'FKT-NO-PO',
        ]);
    }

    // 23. Update dari Belum Lunas menjadi Lunas membersihkan Jatuh Tempo.
    public function test_update_from_unpaid_to_paid_clears_due_date(): void
    {
        Sanctum::actingAs($this->user);

        $invoice = Invoice::create([
            'invoice_number' => 'FKT-TO-PAID',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'status' => 'Belum Lunas',
            'total_amount' => 10000,
        ]);
        $invoice->items()->create([
            'medicine_id' => $this->medicine->id,
            'medicine_name' => 'Item',
            'quantity' => 1,
            'price' => 10000,
            'subtotal' => 10000,
        ]);

        $response = $this->putJson("/api/v1/logistics/invoices/{$invoice->id}", [
            'invoice_number' => 'FKT-TO-PAID',
            'invoice_date' => '2026-10-28',
            'due_date' => '2026-11-28', // Old due_date sent
            'status' => 'Lunas',
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 1, 'price' => 10000],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertNull($response->json('data.dueDate'));
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'Lunas',
            'due_date' => null,
        ]);
    }

    // 24. Update dari Lunas menjadi Belum Lunas mewajibkan Jatuh Tempo.
    public function test_update_from_paid_to_unpaid_requires_due_date(): void
    {
        Sanctum::actingAs($this->user);

        $invoice = Invoice::create([
            'invoice_number' => 'FKT-TO-UNPAID',
            'invoice_date' => '2026-10-28',
            'due_date' => null,
            'supplier_id' => $this->supplier->id,
            'status' => 'Lunas',
            'total_amount' => 10000,
            'paid_amount' => 10000,
        ]);
        $invoice->items()->create([
            'medicine_id' => $this->medicine->id,
            'medicine_name' => 'Item',
            'quantity' => 1,
            'price' => 10000,
            'subtotal' => 10000,
        ]);

        // Attempt update to Belum Lunas without due_date -> rejected
        $response = $this->putJson("/api/v1/logistics/invoices/{$invoice->id}", [
            'invoice_number' => 'FKT-TO-UNPAID',
            'invoice_date' => '2026-10-28',
            'status' => 'Belum Lunas',
            // due_date omitted
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 1, 'price' => 10000],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);

        // Update with valid due_date -> accepted
        $responseValid = $this->putJson("/api/v1/logistics/invoices/{$invoice->id}", [
            'invoice_number' => 'FKT-TO-UNPAID',
            'invoice_date' => '2026-10-28',
            'status' => 'Belum Lunas',
            'due_date' => '2026-11-28',
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 1, 'price' => 10000],
            ],
        ]);

        $responseValid->assertStatus(200);
        $this->assertEquals('2026-11-28', $responseValid->json('data.dueDate'));
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'Belum Lunas',
            'due_date' => '2026-11-28',
        ]);
    }
}
