<?php

namespace App\Http\Controllers\Api\V1\Logistics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\CancelGoodsIssueRequest;
use App\Http\Requests\Logistics\StoreGoodsIssueRequest;
use App\Http\Requests\Logistics\UpdateGoodsIssueRequest;
use App\Http\Resources\Logistics\GoodsIssueItemResource;
use App\Http\Resources\Logistics\GoodsIssueResource;
use App\Http\Resources\Logistics\WarehouseStockResource;
use App\Models\Employee;
use App\Models\Logistics\GoodsIssue;
use App\Models\Logistics\GoodsIssueItem;
use App\Models\Logistics\PosSale;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Warehouse;
use App\Services\Logistics\GoodsIssueService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoodsIssueController extends Controller
{
    public function __construct(
        protected GoodsIssueService $service
    ) {}

    /**
     * Display a listing of goods issues with filtering and pagination.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', GoodsIssue::class);

        $query = GoodsIssue::with([
            'warehouse',
            'officer',
            'creator',
            'finalizer',
            'canceller',
            'items.medicine.unit',
            'items.warehouseStock',
        ]);

        // Search / Issue Number / Reference / Officer / Notes / Purpose / Date
        $search = $request->get('search') ?? $request->get('issueNumber') ?? $request->get('q');
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('issue_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('officer_name', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%");

                try {
                    $parsed = Carbon::parse($search);
                    $q->orWhereDate('issue_date', $parsed->format('Y-m-d'));
                } catch (\Throwable $e) {
                    // Not a date string
                }
            });
        }

        // Date Range
        if ($startDate = ($request->get('startDate') ?? $request->get('start_date'))) {
            $query->whereDate('issue_date', '>=', $startDate);
        }
        if ($endDate = ($request->get('endDate') ?? $request->get('end_date'))) {
            $query->whereDate('issue_date', '<=', $endDate);
        }

        // Warehouse filter
        $warehouse = $request->get('warehouseId') ?? $request->get('warehouse_id') ?? $request->get('warehouse');
        if (! empty($warehouse) && $warehouse !== 'Semua Gudang Asal') {
            if (is_numeric($warehouse)) {
                $query->where('warehouse_id', (int) $warehouse);
            } else {
                $query->whereHas('warehouse', function ($q) use ($warehouse) {
                    $q->where('name', 'like', "%{$warehouse}%");
                });
            }
        }

        // Officer filter
        $officer = $request->get('officer') ?? $request->get('officerName') ?? $request->get('officer_name');
        if (! empty($officer) && $officer !== 'Semua Petugas') {
            $query->where('officer_name', 'like', "%{$officer}%");
        }

        // Purpose filter
        $purpose = $request->get('purpose');
        if (! empty($purpose) && $purpose !== 'Semua Keperluan') {
            $query->where(function ($q) use ($purpose) {
                $q->where('purpose', 'like', "%{$purpose}%")
                    ->orWhere('purpose_code', 'like', "%{$purpose}%");
            });
        }

        // Notes filter
        if ($notes = $request->get('notes')) {
            $query->where(function ($q) use ($notes) {
                $q->where('notes', 'like', "%{$notes}%")
                    ->orWhere('cancellation_reason', 'like', "%{$notes}%");
            });
        }

        // Status filter
        if ($status = ($request->get('status') ?? $request->get('statusCode'))) {
            $statusNormalized = match (strtolower($status)) {
                'draft' => 'draft',
                'selesai', 'completed' => 'completed',
                'dibatalkan', 'cancelled' => 'cancelled',
                default => null,
            };
            if ($statusNormalized) {
                $query->where('status', $statusNormalized);
            }
        }

        $perPage = (int) ($request->get('per_page') ?? $request->get('perPage') ?? 10);
        $paginator = $query->orderByDesc('issue_date')->orderByDesc('id')->paginate($perPage);

        return GoodsIssueResource::collection($paginator);
    }

    /**
     * Get summary metrics for cards.
     */
    public function summary(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', GoodsIssue::class);

        $summary = $this->service->getSummary($request->all());

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Provide reference options for form dropdowns.
     */
    public function formOptions(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', GoodsIssue::class);

        $warehouses = Warehouse::orderBy('name')->get(['id', 'name', 'code']);
        $officers = Employee::orderBy('name')->get(['id', 'name']);

        if ($officers->isEmpty()) {
            $officers = collect([
                ['id' => 1, 'name' => 'Apt. Budi Santoso, S.Farm'],
                ['id' => 2, 'name' => 'Siti Rahmawati, A.Md.Farm'],
                ['id' => 3, 'name' => 'dr. Hendra Kusuma, Sp.PD'],
            ]);
        }

        $purposes = [
            'Kebutuhan Internal',
            'Barang Rusak',
            'Kegiatan Sosial',
            'Kedaluwarsa/Pemusnahan',
            'Buffer Ruangan',
            'Penelitian/Uji Mutu',
            'Lainnya',
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'warehouses' => $warehouses,
                'officers' => $officers,
                'purposes' => $purposes,
            ],
        ]);
    }

    /**
     * Display a specific goods issue document.
     */
    public function show(GoodsIssue $goodsIssue): GoodsIssueResource
    {
        Gate::authorize('view', $goodsIssue);

        $goodsIssue->load([
            'warehouse',
            'officer',
            'creator',
            'finalizer',
            'canceller',
            'items.medicine.unit',
            'items.warehouseStock',
        ]);

        return new GoodsIssueResource($goodsIssue);
    }

    /**
     * Store a newly created goods issue document.
     */
    public function store(StoreGoodsIssueRequest $request): JsonResponse
    {
        Gate::authorize('create', GoodsIssue::class);

        $isDraft = $request->boolean('is_draft', false);
        $issue = $this->service->createIssue(
            $request->validated(),
            (int) $request->user()->id,
            ! $isDraft
        );

        return (new GoodsIssueResource($issue))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing draft goods issue document.
     */
    public function update(UpdateGoodsIssueRequest $request, GoodsIssue $goodsIssue): GoodsIssueResource
    {
        Gate::authorize('update', $goodsIssue);

        $isDraft = $request->boolean('is_draft', true);
        $issue = $this->service->updateDraft(
            $goodsIssue,
            $request->validated(),
            (int) $request->user()->id,
            ! $isDraft
        );

        return new GoodsIssueResource($issue);
    }

    /**
     * Finalize a draft goods issue document.
     */
    public function finalize(Request $request, GoodsIssue $goodsIssue): GoodsIssueResource
    {
        Gate::authorize('finalize', $goodsIssue);

        $issue = $this->service->finalize($goodsIssue, (int) $request->user()->id);

        return new GoodsIssueResource($issue);
    }

    /**
     * Cancel a goods issue document.
     */
    public function cancel(CancelGoodsIssueRequest $request, GoodsIssue $goodsIssue): GoodsIssueResource
    {
        Gate::authorize('cancel', $goodsIssue);

        $reason = $request->validated('reason');
        $issue = $this->service->cancel($goodsIssue, $reason, (int) $request->user()->id);

        return new GoodsIssueResource($issue);
    }

    /**
     * Delete a draft goods issue document.
     */
    public function destroy(GoodsIssue $goodsIssue): JsonResponse
    {
        Gate::authorize('delete', $goodsIssue);

        $this->service->deleteDraft($goodsIssue);

        return response()->json([
            'success' => true,
            'message' => 'Draft pengeluaran barang berhasil dihapus.',
        ]);
    }

    /**
     * Retrieve available stocks for a specific warehouse.
     */
    public function availableStocks(Request $request, $warehouse): JsonResponse|AnonymousResourceCollection
    {
        if (! $request->user()) {
            abort(401, 'Unauthenticated.');
        }

        if (! Gate::allows('viewAny', GoodsIssue::class) && ! Gate::allows('viewAny', PosSale::class)) {
            abort(403, 'Unauthorized.');
        }

        $warehouseId = is_numeric($warehouse)
            ? (int) $warehouse
            : Warehouse::where('name', $warehouse)->value('id');

        if (! $warehouseId) {
            return response()->json([
                'message' => 'Gudang tidak ditemukan.',
                'errors' => ['warehouse' => ['Gudang tidak ditemukan.']],
            ], 404);
        }

        $query = WarehouseStock::with(['medicine.unit', 'warehouse'])
            ->where('warehouse_id', $warehouseId)
            ->where('quantity', '>', 0);

        // Filter out expired stocks relative to issue date (or current date for normal issue)
        $refDate = $request->input('issue_date') ?? $request->input('date') ?? Carbon::today()->toDateString();
        try {
            $refDate = Carbon::parse($refDate)->toDateString();
        } catch (\Throwable) {
            $refDate = Carbon::today()->toDateString();
        }

        $query->where(function ($q) use ($refDate) {
            $q->whereNull('expired_date')
                ->orWhereDate('expired_date', '>=', $refDate);
        });

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                    ->orWhereHas('medicine', function ($mq) use ($search) {
                        $mq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        // FEFO order: earliest expiring batch first, items without expiration date last, then ID
        $query->orderByRaw('CASE WHEN expired_date IS NULL THEN 1 ELSE 0 END ASC, expired_date ASC, id ASC');

        if ($request->has('all') || $request->input('all') == 1) {
            $stocks = $query->get();
            return WarehouseStockResource::collection($stocks);
        }

        $perPage = (int) ($request->input('per_page') ?? $request->input('perPage') ?? 20);
        $stocks = $query->paginate($perPage);

        return WarehouseStockResource::collection($stocks);
    }

    /**
     * Add single item to goods issue.
     */
    public function addItem(Request $request, GoodsIssue $goodsIssue): JsonResponse
    {
        Gate::authorize('view', $goodsIssue);

        $request->validate([
            'medicine_id' => ['required', 'integer', 'exists:medicines,id'],
            'stock_id' => ['nullable', 'integer', 'exists:warehouse_stocks,id'],
            'warehouse_stock_id' => ['nullable', 'integer', 'exists:warehouse_stocks,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'medicine_id.required' => 'Sediaan obat wajib dipilih.',
            'medicine_id.exists' => 'Sediaan obat tidak ditemukan.',
            'stock_id.exists' => 'Data persediaan stok tidak valid.',
            'warehouse_stock_id.exists' => 'Data persediaan stok tidak valid.',
            'quantity.required' => 'Jumlah sediaan wajib diisi.',
            'quantity.gt' => 'Jumlah sediaan harus lebih besar dari 0.',
        ]);

        $item = $this->service->addItem($goodsIssue, $request->all(), (int) $request->user()->id);

        return (new GoodsIssueItemResource($item))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an item in goods issue.
     */
    public function updateItem(Request $request, GoodsIssue $goodsIssue, GoodsIssueItem $item): JsonResponse
    {
        Gate::authorize('view', $goodsIssue);

        $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0'],
        ], [
            'quantity.required' => 'Jumlah sediaan wajib diisi.',
            'quantity.gt' => 'Jumlah sediaan harus lebih besar dari 0.',
        ]);

        $updatedItem = $this->service->updateItem($goodsIssue, $item, $request->all(), (int) $request->user()->id);

        return (new GoodsIssueItemResource($updatedItem))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Delete an item from goods issue.
     */
    public function deleteItem(GoodsIssue $goodsIssue, GoodsIssueItem $item): JsonResponse
    {
        Gate::authorize('view', $goodsIssue);

        $this->service->deleteItem($goodsIssue, $item, (int) auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Item detail pengeluaran berhasil dihapus.',
        ]);
    }

    /**
     * Export goods issues as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', GoodsIssue::class);

        $query = GoodsIssue::with([
            'warehouse',
            'officer',
            'creator',
            'items',
        ]);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('issue_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }
        if ($startDate = $request->get('start_date')) {
            $query->whereDate('issue_date', '>=', $startDate);
        }
        if ($endDate = $request->get('end_date')) {
            $query->whereDate('issue_date', '<=', $endDate);
        }

        $issues = $query->orderByDesc('issue_date')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Daftar_Pengeluaran_Barang_'.date('Ymd_His').'.csv"',
        ];

        return response()->stream(function () use ($issues) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'No',
                'Nomor Pengeluaran',
                'Referensi/BAP',
                'Tanggal Waktu',
                'Gudang Asal',
                'Petugas Otorisasi',
                'Keperluan',
                'Total Jenis',
                'Total Kuantitas',
                'Status',
                'Catatan',
            ]);

            foreach ($issues as $idx => $it) {
                $date = $it->issue_date ? Carbon::parse($it->issue_date)->format('d M Y H:i').' WIB' : '-';
                fputcsv($handle, [
                    $idx + 1,
                    $it->issue_number,
                    $it->reference_number ?? '-',
                    $date,
                    $it->warehouse?->name ?? '-',
                    $it->officer_name,
                    $it->purpose,
                    $it->items->count(),
                    (float) $it->total_quantity,
                    ucfirst($it->status),
                    $it->notes ?? '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
