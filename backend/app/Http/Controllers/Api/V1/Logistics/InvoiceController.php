<?php

namespace App\Http\Controllers\Api\V1\Logistics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\StoreInvoiceRequest;
use App\Http\Requests\Logistics\UpdateInvoiceRequest;
use App\Http\Resources\Logistics\InvoiceResource;
use App\Models\Logistics\Invoice;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\Supplier;
use App\Models\MasterData\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices with filters, search, and pagination.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Invoice::class);

        $query = Invoice::with(['supplier', 'warehouse', 'items.medicine.unit']);

        // 1. Text Search across invoice number, reference, supplier name, notes, and medicine names
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('supplier_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('medicine_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('supplier', function ($supQuery) use ($search) {
                        $supQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // 2. Specific field filters
        $invoiceNumber = $request->input('invoice_number', $request->input('invoiceNumber'));
        if ($invoiceNumber) {
            $query->where(function ($q) use ($invoiceNumber) {
                $q->where('invoice_number', 'like', "%{$invoiceNumber}%")
                    ->orWhere('reference_number', 'like', "%{$invoiceNumber}%");
            });
        }

        $supplierId = $request->input('supplier_id', $request->input('supplierId'));
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $warehouseId = $request->input('warehouse_id', $request->input('warehouseId'));
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $invoiceDateStart = $request->input('invoice_date_start', $request->input('invoiceDateStart'));
        if ($invoiceDateStart) {
            $query->whereDate('invoice_date', '>=', $invoiceDateStart);
        }

        $invoiceDateEnd = $request->input('invoice_date_end', $request->input('invoiceDateEnd'));
        if ($invoiceDateEnd) {
            $query->whereDate('invoice_date', '<=', $invoiceDateEnd);
        }

        $dueDateStart = $request->input('due_date_start', $request->input('dueDateStart'));
        if ($dueDateStart) {
            $query->whereDate('due_date', '>=', $dueDateStart);
        }

        $dueDateEnd = $request->input('due_date_end', $request->input('dueDateEnd'));
        if ($dueDateEnd) {
            $query->whereDate('due_date', '<=', $dueDateEnd);
        }

        // 3. Sorting
        $column = $request->input('sort', $request->input('sortBy', $request->input('column', 'invoice_date')));
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        $columnMap = [
            'invoiceDate' => 'invoice_date',
            'invoice_date' => 'invoice_date',
            'invoiceNumber' => 'invoice_number',
            'invoice_number' => 'invoice_number',
            'dueDate' => 'due_date',
            'due_date' => 'due_date',
            'totalAmount' => 'total_amount',
            'total_amount' => 'total_amount',
            'status' => 'status',
            'supplierName' => 'supplier_name',
            'supplier_name' => 'supplier_name',
            'id' => 'id',
            'created_at' => 'created_at',
        ];

        $sortColumn = $columnMap[$column] ?? 'invoice_date';
        $query->orderBy($sortColumn, $direction);

        // 4. Pagination
        $perPage = $request->input('per_page', $request->input('perPage', 10));
        if ($perPage === '-1' || $perPage === 'all') {
            return InvoiceResource::collection($query->get());
        }

        return InvoiceResource::collection($query->paginate((int) $perPage));
    }

    /**
     * Get aggregate summary statistics for invoices.
     */
    public function summary(): JsonResponse
    {
        Gate::authorize('viewAny', Invoice::class);

        $today = Carbon::today()->toDateString();

        $stats = DB::table('invoices')
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total_count,
                COALESCE(SUM(total_amount), 0) as total_amount,
                COUNT(CASE WHEN status = 'Lunas' THEN 1 END) as paid_count,
                COALESCE(SUM(CASE WHEN status = 'Lunas' THEN total_amount ELSE 0 END), 0) as paid_amount,
                COUNT(CASE WHEN status != 'Lunas' THEN 1 END) as unpaid_count,
                COALESCE(SUM(CASE WHEN status != 'Lunas' THEN total_amount ELSE 0 END), 0) as unpaid_amount,
                COUNT(CASE WHEN status != 'Lunas' AND due_date IS NOT NULL AND due_date < ? THEN 1 END) as overdue_count
            ", [$today])
            ->first();

        $totalCount = (int) ($stats->total_count ?? 0);
        $totalAmount = (float) ($stats->total_amount ?? 0);
        $paidCount = (int) ($stats->paid_count ?? 0);
        $paidAmount = (float) ($stats->paid_amount ?? 0);
        $unpaidCount = (int) ($stats->unpaid_count ?? 0);
        $unpaidAmount = (float) ($stats->unpaid_amount ?? 0);
        $overdueCount = (int) ($stats->overdue_count ?? 0);

        $paidPercentage = $totalCount > 0 ? number_format(($paidCount / $totalCount) * 100, 1) : '0';
        $unpaidPercentage = $totalCount > 0 ? number_format(($unpaidCount / $totalCount) * 100, 1) : '0';

        return response()->json([
            'totalCount' => $totalCount,
            'total_count' => $totalCount,
            'totalAmount' => $totalAmount,
            'total_amount' => $totalAmount,
            'paidCount' => $paidCount,
            'paid_count' => $paidCount,
            'paidAmount' => $paidAmount,
            'paid_amount' => $paidAmount,
            'paidPercentage' => $paidPercentage,
            'paid_percentage' => $paidPercentage,
            'unpaidCount' => $unpaidCount,
            'unpaid_count' => $unpaidCount,
            'unpaidAmount' => $unpaidAmount,
            'unpaid_amount' => $unpaidAmount,
            'unpaidPercentage' => $unpaidPercentage,
            'unpaid_percentage' => $unpaidPercentage,
            'overdueCount' => $overdueCount,
            'overdue_count' => $overdueCount,
        ]);
    }

    /**
     * Store a newly created invoice in storage.
     */
    public function store(StoreInvoiceRequest $request)
    {
        Gate::authorize('create', Invoice::class);

        $validated = $request->validated();

        $invoice = DB::transaction(function () use ($validated, $request) {
            $supplier = Supplier::find($validated['supplier_id']);

            // Resolve warehouse: use passed warehouse or default to Gudang Utama
            $warehouseId = $validated['warehouse_id'] ?? null;
            if (! $warehouseId) {
                $defaultWarehouse = Warehouse::where('name', 'like', '%Utama%')->first() ?? Warehouse::first();
                $warehouseId = $defaultWarehouse?->id;
            }

            $rawStatus = $validated['status'] ?? 'Belum Lunas';
            $status = in_array(strtolower($rawStatus), ['lunas', 'paid'], true) ? 'Lunas' : 'Belum Lunas';
            $paidAt = $status === 'Lunas' ? Carbon::today()->toDateString() : null;

            // Compute subtotal, discount, tax, and total from items server-side
            $itemsData = $validated['items'] ?? [];
            $computedSubtotal = 0;
            $computedDiscount = 0;
            $preparedItems = [];

            foreach ($itemsData as $it) {
                $medId = $it['medicineId'] ?? $it['medicine_id'] ?? $it['item_id'] ?? null;
                $med = $medId ? Medicine::with('unit')->find($medId) : null;
                $medName = $it['medicineName'] ?? $it['medicine_name'] ?? ($med ? $med->name : 'Obat');

                $qty = (float) ($it['quantity'] ?? 1);
                $unit = $it['unit'] ?? ($med?->unit?->name ?? 'Box');
                $price = (float) ($it['price'] ?? $it['unit_price'] ?? $it['unitPrice'] ?? ($med?->selling_price_per_unit ?? 0));

                $gross = $qty * $price;
                $computedSubtotal += $gross;

                $discType = $it['discount_type'] ?? $it['discountType'] ?? 'percentage';
                $rawDisc = (float) ($it['discount'] ?? $it['discount_value'] ?? 0);
                $itemDiscount = 0;

                if ($discType === 'nominal') {
                    $itemDiscount = min($rawDisc, $gross);
                } else {
                    // Percentage: support both decimal < 1 (legacy e.g. 0.10) and integer percentage (10)
                    if ($rawDisc > 0 && $rawDisc < 1) {
                        $itemDiscount = round($gross * $rawDisc, 2);
                    } else {
                        $itemDiscount = round($gross * (min($rawDisc, 100) / 100), 2);
                    }
                }

                $itemDiscount = min($itemDiscount, $gross);
                $computedDiscount += $itemDiscount;
                $itemSubtotal = round($gross - $itemDiscount, 2);

                $margin = isset($it['margin']) ? (float) $it['margin'] : 0.2800;
                $isRepack = filter_var($it['is_repack'] ?? $it['isRepack'] ?? false, FILTER_VALIDATE_BOOLEAN);

                $preparedItems[] = [
                    'medicine_id' => $medId,
                    'medicine_name' => $medName ?: 'Obat',
                    'quantity' => $qty,
                    'unit' => $unit,
                    'price' => $price,
                    'discount_type' => $discType,
                    'discount' => $rawDisc,
                    'subtotal' => $itemSubtotal,
                    'is_repack' => $isRepack,
                    'margin' => $margin,
                    'batch_number' => isset($it['batch_number']) ? trim((string) $it['batch_number']) : (isset($it['batchNumber']) ? trim((string) $it['batchNumber']) : null),
                    'expired_date' => $it['expired_date'] ?? $it['expiredDate'] ?? null,
                    'notes' => $it['notes'] ?? null,
                ];
            }

            $dpp = max(0, $computedSubtotal - $computedDiscount);

            $taxType = $validated['tax_type'] ?? 'non_tax';
            $isPpn = in_array($taxType, ['ppn', 'ppn_11', 'tax'], true);
            $taxRate = $isPpn ? 11.00 : 0.00;
            $taxAmount = $isPpn ? round($dpp * 0.11, 2) : 0.00;

            $totalAmount = round($dpp + $taxAmount, 2);
            $paidAmount = $status === 'Lunas' ? $totalAmount : 0.00;

            $invoice = Invoice::create([
                'invoice_number' => $validated['invoice_number'],
                'reference_number' => $validated['reference_number'] ?? null,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $status === 'Lunas' ? null : ($validated['due_date'] ?? null),
                'supplier_id' => $supplier ? $supplier->id : $validated['supplier_id'],
                'warehouse_id' => $warehouseId,
                'supplier_name' => $supplier ? $supplier->name : ($validated['supplier_name'] ?? 'Supplier'),
                'supplier_city' => $supplier ? $supplier->address : null,
                'supplier_npwp' => $supplier ? $supplier->phone : null,
                'status' => $status,
                'tax_type' => $isPpn ? 'ppn_11' : 'non_tax',
                'tax_rate' => $taxRate,
                'notes' => $validated['notes'] ?? null,
                'paid_at' => $paidAt,
                'subtotal_amount' => $computedSubtotal,
                'discount_amount' => $computedDiscount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'created_by' => $request->user()?->id,
            ]);

            foreach ($preparedItems as $item) {
                $invoice->items()->create($item);
            }

            return $invoice;
        });

        return (new InvoiceResource($invoice->load(['supplier', 'warehouse', 'items.medicine.unit'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice)
    {
        Gate::authorize('view', $invoice);

        return new InvoiceResource($invoice->load(['supplier', 'warehouse', 'items.medicine.unit']));
    }

    /**
     * Update the specified invoice in storage.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        Gate::authorize('update', $invoice);

        $validated = $request->validated();

        $invoice = DB::transaction(function () use ($validated, $invoice) {
            $supplier = Supplier::find($validated['supplier_id']);

            $warehouseId = $validated['warehouse_id'] ?? $invoice->warehouse_id;
            if (! $warehouseId) {
                $defaultWarehouse = Warehouse::where('name', 'like', '%Utama%')->first() ?? Warehouse::first();
                $warehouseId = $defaultWarehouse?->id;
            }

            $rawStatus = $validated['status'] ?? $invoice->status;
            $status = in_array(strtolower($rawStatus), ['lunas', 'paid'], true) ? 'Lunas' : 'Belum Lunas';
            $paidAt = $invoice->paid_at;
            if ($status === 'Lunas' && ! $paidAt) {
                $paidAt = Carbon::today()->toDateString();
            } elseif ($status !== 'Lunas') {
                $paidAt = null;
            }

            // Recalculate totals server-side
            $itemsData = $validated['items'] ?? [];
            $computedSubtotal = 0;
            $computedDiscount = 0;
            $preparedItems = [];

            foreach ($itemsData as $it) {
                $medId = $it['medicineId'] ?? $it['medicine_id'] ?? $it['item_id'] ?? null;
                $med = $medId ? Medicine::with('unit')->find($medId) : null;
                $medName = $it['medicineName'] ?? $it['medicine_name'] ?? ($med ? $med->name : 'Obat');

                $qty = (float) ($it['quantity'] ?? 1);
                $unit = $it['unit'] ?? ($med?->unit?->name ?? 'Box');
                $price = (float) ($it['price'] ?? $it['unit_price'] ?? $it['unitPrice'] ?? ($med?->selling_price_per_unit ?? 0));

                $gross = $qty * $price;
                $computedSubtotal += $gross;

                $discType = $it['discount_type'] ?? $it['discountType'] ?? 'percentage';
                $rawDisc = (float) ($it['discount'] ?? $it['discount_value'] ?? 0);
                $itemDiscount = 0;

                if ($discType === 'nominal') {
                    $itemDiscount = min($rawDisc, $gross);
                } else {
                    if ($rawDisc > 0 && $rawDisc < 1) {
                        $itemDiscount = round($gross * $rawDisc, 2);
                    } else {
                        $itemDiscount = round($gross * (min($rawDisc, 100) / 100), 2);
                    }
                }

                $itemDiscount = min($itemDiscount, $gross);
                $computedDiscount += $itemDiscount;
                $itemSubtotal = round($gross - $itemDiscount, 2);

                $margin = isset($it['margin']) ? (float) $it['margin'] : 0.2800;
                $isRepack = filter_var($it['is_repack'] ?? $it['isRepack'] ?? false, FILTER_VALIDATE_BOOLEAN);

                $preparedItems[] = [
                    'medicine_id' => $medId,
                    'medicine_name' => $medName ?: 'Obat',
                    'quantity' => $qty,
                    'unit' => $unit,
                    'price' => $price,
                    'discount_type' => $discType,
                    'discount' => $rawDisc,
                    'subtotal' => $itemSubtotal,
                    'is_repack' => $isRepack,
                    'margin' => $margin,
                    'batch_number' => isset($it['batch_number']) ? trim((string) $it['batch_number']) : (isset($it['batchNumber']) ? trim((string) $it['batchNumber']) : null),
                    'expired_date' => $it['expired_date'] ?? $it['expiredDate'] ?? null,
                    'notes' => $it['notes'] ?? null,
                ];
            }

            $dpp = max(0, $computedSubtotal - $computedDiscount);

            $taxType = $validated['tax_type'] ?? $invoice->tax_type ?? 'non_tax';
            $isPpn = in_array($taxType, ['ppn', 'ppn_11', 'tax'], true);
            $taxRate = $isPpn ? 11.00 : 0.00;
            $taxAmount = $isPpn ? round($dpp * 0.11, 2) : 0.00;

            $totalAmount = round($dpp + $taxAmount, 2);
            $paidAmount = $status === 'Lunas' ? $totalAmount : 0.00;

            $invoice->update([
                'invoice_number' => $validated['invoice_number'],
                'reference_number' => $validated['reference_number'] ?? null,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $status === 'Lunas' ? null : ($validated['due_date'] ?? null),
                'supplier_id' => $supplier ? $supplier->id : $validated['supplier_id'],
                'warehouse_id' => $warehouseId,
                'supplier_name' => $supplier ? $supplier->name : $invoice->supplier_name,
                'supplier_city' => $supplier ? $supplier->address : $invoice->supplier_city,
                'status' => $status,
                'tax_type' => $isPpn ? 'ppn_11' : 'non_tax',
                'tax_rate' => $taxRate,
                'notes' => $validated['notes'] ?? null,
                'paid_at' => $paidAt,
                'subtotal_amount' => $computedSubtotal,
                'discount_amount' => $computedDiscount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
            ]);

            // Sync items
            $invoice->items()->delete();
            foreach ($preparedItems as $item) {
                $invoice->items()->create($item);
            }

            return $invoice;
        });

        return new InvoiceResource($invoice->load(['supplier', 'warehouse', 'items.medicine.unit']));
    }

    /**
     * Remove the specified invoice from storage.
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        Gate::authorize('delete', $invoice);

        DB::transaction(function () use ($invoice) {
            $invoice->items()->delete();
            $invoice->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Data Faktur berhasil dihapus.',
        ]);
    }

    /**
     * Export invoices to CSV.
     */
    public function export(Request $request)
    {
        Gate::authorize('viewAny', Invoice::class);

        $query = Invoice::with(['supplier', 'warehouse', 'items'])->orderBy('invoice_date', 'desc');

        $invoices = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="Data_Faktur_'.date('Y-m-d').'.csv"',
        ];

        $callback = function () use ($invoices) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['No', 'Nomor Faktur', 'Nomor PO', 'Tanggal Faktur', 'Supplier', 'NPWP', 'Total (IDR)', 'Status', 'Jatuh Tempo']);

            foreach ($invoices as $idx => $inv) {
                fputcsv($file, [
                    $idx + 1,
                    $inv->invoice_number,
                    $inv->reference_number ?: '-',
                    $inv->invoice_date?->format('Y-m-d'),
                    $inv->supplier_name,
                    $inv->supplier_npwp ?: '-',
                    $inv->total_amount,
                    $inv->status,
                    $inv->due_date?->format('Y-m-d'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
