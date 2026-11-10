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
use App\Models\Legacy\LegacyEmployee;
use App\Models\Legacy\LegacyGoodsIssue;
use App\Models\Legacy\LegacyGoodsIssueItem;
use App\Models\Legacy\LegacyStockLog;
use App\Models\Legacy\LegacyWarehouse;
use App\Models\Logistics\GoodsIssue;
use App\Models\Logistics\GoodsIssueItem;
use App\Models\Logistics\PosSale;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Warehouse;
use App\Services\Logistics\GoodsIssueService;
use App\Support\LegacyAccess;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
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

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $query = LegacyGoodsIssue::pengeluaran()->with([
                'employee',
                'items.stockLog.warehouse',
                'items.stockLog.fakturDetail.medicine.unit',
            ]);

            $search = $request->get('search') ?? $request->get('issueNumber') ?? $request->get('q');
            if (! empty($search)) {
                $query->search($search);
            }

            if ($startDate = ($request->get('startDate') ?? $request->get('start_date'))) {
                $query->whereDate('tanggal', '>=', $startDate);
            }
            if ($endDate = ($request->get('endDate') ?? $request->get('end_date'))) {
                $query->whereDate('tanggal', '<=', $endDate);
            }

            $officer = $request->get('officer') ?? $request->get('officerName') ?? $request->get('officer_name');
            if (! empty($officer) && $officer !== 'Semua Petugas') {
                $query->whereHas('employee.person', fn ($eq) => $eq->where('nama', 'like', "%{$officer}%"));
            }

            $purpose = $request->get('purpose');
            if (! empty($purpose) && $purpose !== 'Semua Keperluan') {
                $query->where('keperluan', 'like', "%{$purpose}%");
            }

            $perPage = (int) ($request->get('per_page') ?? $request->get('perPage') ?? 10);
            $paginator = $query->orderByDesc('tanggal')->orderByDesc('id')->paginate($perPage);

            return GoodsIssueResource::collection($paginator);
        }

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

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $summary = [
                'total_completed_issues' => LegacyGoodsIssue::pengeluaran()->where('status', 1)->count(),
                'total_completed_value' => (float) DB::connection('legacy')->table('lgs_pengeluaran_detail as d')
                    ->join('lgs_pengeluaran as p', 'd.id_lgs_pengeluaran', '=', 'p.id')
                    ->join('lgs_log_stok as s', 'd.id_lgs_log_stok', '=', 's.id')
                    ->where(fn ($q) => $q->where('p.flag', 'pengeluaran')->orWhereNull('p.flag'))
                    ->where('p.status', 1)
                    ->whereNull('p.deleted_at')
                    ->sum(DB::raw('d.jumlah * s.harga_jual')),
                'total_draft_issues' => 0,
                'total_cancelled_issues' => LegacyGoodsIssue::pengeluaran()->where('status', 0)->count(),
                'damaged_goods_count' => LegacyGoodsIssue::pengeluaran()->where('keperluan', 'like', '%rusak%')->count(),
                'expired_goods_count' => LegacyGoodsIssue::pengeluaran()->where(fn ($q) => $q->where('keperluan', 'like', '%kedaluwarsa%')->orWhere('keperluan', 'like', '%pemusnahan%'))->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        }

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

        if (LegacyAccess::isAvailable('ref_gudang') || LegacyAccess::isAvailable('ref_pegawai')) {
            $warehouses = LegacyAccess::isAvailable('ref_gudang')
                ? LegacyWarehouse::orderBy('nama')->get(['id', 'nama', 'kode'])
                    ->map(fn ($w) => [
                        'id' => $w->id,
                        'name' => $w->name,
                        'code' => $w->code,
                    ])
                    ->values()
                : Warehouse::orderBy('name')->get(['id', 'name', 'code']);

            if (LegacyAccess::isAvailable('ref_pegawai') && LegacyAccess::isAvailable('master_person_index')) {
                $officers = LegacyEmployee::with('person')
                    ->where('ref_pegawai.status', '1')
                    ->get()
                    ->filter(fn ($emp) => ! empty($emp->person?->nama))
                    ->sortBy(fn ($emp) => strtolower($emp->person->nama))
                    ->map(fn ($emp) => [
                        'id' => $emp->id,
                        'name' => $emp->person->nama,
                        'nip' => $emp->nip,
                    ])
                    ->values();
            } elseif (LegacyAccess::isAvailable('ref_pegawai')) {
                $officers = LegacyEmployee::all()->map(fn ($emp) => ['id' => $emp->id, 'name' => $emp->name]);
            } else {
                $officers = Employee::orderBy('name')->get(['id', 'name']);
            }

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
    public function show($goodsIssue): GoodsIssueResource
    {
        $id = $goodsIssue instanceof GoodsIssue ? $goodsIssue->id : ($goodsIssue instanceof LegacyGoodsIssue ? $goodsIssue->id : $goodsIssue);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $legacy = LegacyGoodsIssue::pengeluaran()->with([
                'employee',
                'items.stockLog.warehouse',
                'items.stockLog.fakturDetail.medicine.unit',
            ])->find($id);

            if ($legacy) {
                return new GoodsIssueResource($legacy);
            }
        }

        $local = GoodsIssue::with([
            'warehouse',
            'officer',
            'creator',
            'finalizer',
            'canceller',
            'items.medicine.unit',
            'items.warehouseStock',
        ])->find($id);

        if ($local) {
            Gate::authorize('view', $local);
            return new GoodsIssueResource($local);
        }

        abort(404, 'Goods issue not found.');
    }

    /**
     * Store a newly created goods issue document.
     */
    public function store(StoreGoodsIssueRequest $request): JsonResponse
    {
        Gate::authorize('create', GoodsIssue::class);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $validated = $request->validated();
            $legacyUserId = $request->user()?->getLegacyUserId() ?? 1;

            $issue = DB::connection('legacy')->transaction(function () use ($validated, $legacyUserId, $request) {
                $isDraft = $request->boolean('is_draft', false);
                $issueDate = $validated['issue_date'] ?? date('Y-m-d');
                $officerId = $validated['officer_id'] ?? $validated['officerId'] ?? null;
                $officerName = $validated['officer_name'] ?? $validated['officerName'] ?? null;

                if (! $officerId && ! empty($officerName)) {
                    if (LegacyAccess::isAvailable('ref_pegawai') && LegacyAccess::isAvailable('master_person_index')) {
                        $emp = LegacyEmployee::join('master_person_index', 'ref_pegawai.id_mpi', '=', 'master_person_index.id')
                            ->where('master_person_index.nama', $officerName)
                            ->select('ref_pegawai.id')
                            ->first();
                        if ($emp) {
                            $officerId = $emp->id;
                        }
                    }
                }

                $purpose = $validated['purpose'] ?? 'Kebutuhan Internal';
                $notes = $validated['notes'] ?? null;
                $keperluan = $notes ?: $purpose;

                $goodsIssue = LegacyGoodsIssue::create([
                    'tanggal' => $issueDate,
                    'id_pegawai' => $officerId ?: 0,
                    'keperluan' => $keperluan,
                    'flag' => 'pengeluaran',
                    'id_user' => $legacyUserId,
                    'status' => $isDraft ? 0 : 1,
                    'kode_billing' => $validated['reference_number'] ?? null,
                ]);

                foreach ($validated['items'] ?? [] as $item) {
                    $stockId = (int) ($item['warehouse_stock_id'] ?? $item['warehouseStockId'] ?? $item['stock_id'] ?? 0);
                    $qty = (float) ($item['quantity'] ?? 0);

                    LegacyGoodsIssueItem::create([
                        'id_lgs_pengeluaran' => $goodsIssue->id,
                        'id_lgs_log_stok' => $stockId,
                        'jumlah' => $qty,
                        'id_user' => $legacyUserId,
                        'status' => 1,
                    ]);

                    if (! $isDraft && $stockId) {
                        $stock = LegacyStockLog::lockForUpdate()->find($stockId);
                        if ($stock) {
                            $stock->qty = max(0, $stock->qty - $qty);
                            $stock->save();
                        }
                    }
                }

                return $goodsIssue;
            });

            return (new GoodsIssueResource($issue->load(['employee', 'items.stockLog.warehouse', 'items.stockLog.fakturDetail.medicine.unit'])))
                ->response()
                ->setStatusCode(201);
        }

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
    public function update(UpdateGoodsIssueRequest $request, $goodsIssue): GoodsIssueResource
    {
        $id = $goodsIssue instanceof GoodsIssue ? $goodsIssue->id : ($goodsIssue instanceof LegacyGoodsIssue ? $goodsIssue->id : $goodsIssue);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $legacy = LegacyGoodsIssue::pengeluaran()->findOrFail($id);
            $validated = $request->validated();
            $legacyUserId = $request->user()?->getLegacyUserId() ?? 1;

            $updated = DB::connection('legacy')->transaction(function () use ($legacy, $validated, $legacyUserId) {
                $officerId = $validated['officer_id'] ?? $validated['officerId'] ?? null;
                $officerName = $validated['officer_name'] ?? $validated['officerName'] ?? null;
                if (! $officerId && ! empty($officerName)) {
                    if (LegacyAccess::isAvailable('ref_pegawai') && LegacyAccess::isAvailable('master_person_index')) {
                        $emp = LegacyEmployee::join('master_person_index', 'ref_pegawai.id_mpi', '=', 'master_person_index.id')
                            ->where('master_person_index.nama', $officerName)
                            ->select('ref_pegawai.id')
                            ->first();
                        if ($emp) {
                            $officerId = $emp->id;
                        }
                    }
                }

                $purpose = $validated['purpose'] ?? $legacy->keperluan;
                $notes = $validated['notes'] ?? null;
                $keperluan = $notes ?: ($purpose ?: $legacy->keperluan);

                $legacy->update([
                    'tanggal' => $validated['issue_date'] ?? $legacy->tanggal,
                    'id_pegawai' => $officerId ?? $legacy->id_pegawai,
                    'keperluan' => $keperluan,
                    'id_user' => $legacyUserId,
                ]);

                return $legacy;
            });

            return new GoodsIssueResource($updated->load(['employee', 'items.stockLog.warehouse', 'items.stockLog.fakturDetail.medicine.unit']));
        }

        $local = GoodsIssue::findOrFail($id);
        Gate::authorize('update', $local);

        $isDraft = $request->boolean('is_draft', true);
        $issue = $this->service->updateDraft(
            $local,
            $request->validated(),
            (int) $request->user()->id,
            ! $isDraft
        );

        return new GoodsIssueResource($issue);
    }

    /**
     * Finalize a draft goods issue document.
     */
    public function finalize(Request $request, $goodsIssue): GoodsIssueResource
    {
        $id = $goodsIssue instanceof GoodsIssue ? $goodsIssue->id : ($goodsIssue instanceof LegacyGoodsIssue ? $goodsIssue->id : $goodsIssue);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $legacy = LegacyGoodsIssue::pengeluaran()->findOrFail($id);
            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->update(['status' => 1]);
                foreach ($legacy->items as $item) {
                    if ($item->id_lgs_log_stok) {
                        $stock = LegacyStockLog::lockForUpdate()->find($item->id_lgs_log_stok);
                        if ($stock) {
                            $stock->qty = max(0, $stock->qty - (float) $item->jumlah);
                            $stock->save();
                        }
                    }
                }
            });

            return new GoodsIssueResource($legacy->load(['employee', 'items.stockLog.warehouse', 'items.stockLog.fakturDetail.medicine.unit']));
        }

        $local = GoodsIssue::findOrFail($id);
        Gate::authorize('finalize', $local);

        $issue = $this->service->finalize($local, (int) $request->user()->id);

        return new GoodsIssueResource($issue);
    }

    /**
     * Cancel a goods issue document.
     */
    public function cancel(CancelGoodsIssueRequest $request, $goodsIssue): GoodsIssueResource
    {
        $id = $goodsIssue instanceof GoodsIssue ? $goodsIssue->id : ($goodsIssue instanceof LegacyGoodsIssue ? $goodsIssue->id : $goodsIssue);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $legacy = LegacyGoodsIssue::pengeluaran()->findOrFail($id);
            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->update(['status' => 0]);
                foreach ($legacy->items as $item) {
                    if ($item->id_lgs_log_stok) {
                        $stock = LegacyStockLog::lockForUpdate()->find($item->id_lgs_log_stok);
                        if ($stock) {
                            $stock->qty += (float) $item->jumlah;
                            $stock->save();
                        }
                    }
                }
            });

            return new GoodsIssueResource($legacy->load(['employee', 'items.stockLog.warehouse', 'items.stockLog.fakturDetail.medicine.unit']));
        }

        $local = GoodsIssue::findOrFail($id);
        Gate::authorize('cancel', $local);

        $reason = $request->validated('reason');
        $issue = $this->service->cancel($local, $reason, (int) $request->user()->id);

        return new GoodsIssueResource($issue);
    }

    /**
     * Delete a draft goods issue document.
     */
    public function destroy($goodsIssue): JsonResponse
    {
        $id = $goodsIssue instanceof GoodsIssue ? $goodsIssue->id : ($goodsIssue instanceof LegacyGoodsIssue ? $goodsIssue->id : $goodsIssue);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $legacy = LegacyGoodsIssue::pengeluaran()->findOrFail($id);
            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->items()->delete();
                $legacy->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Draft pengeluaran barang berhasil dihapus.',
            ]);
        }

        $local = GoodsIssue::findOrFail($id);
        Gate::authorize('delete', $local);

        $this->service->deleteDraft($local);

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
            : (Warehouse::where('name', $warehouse)->value('id') ?? 1);

        if (LegacyAccess::isAvailable('lgs_log_stok')) {
            $query = LegacyStockLog::with(['warehouse', 'fakturDetail.medicine.unit'])
                ->where('id_gudang', $warehouseId)
                ->where('qty', '>', 0);

            $refDate = $request->input('issue_date') ?? $request->input('date') ?? Carbon::today()->toDateString();
            try {
                $refDate = Carbon::parse($refDate)->toDateString();
            } catch (\Throwable) {
                $refDate = Carbon::today()->toDateString();
            }

            $query->whereHas('fakturDetail', function ($q) use ($refDate) {
                $q->whereNull('ed')
                    ->orWhere('ed', '>=', $refDate);
            });

            if ($search = trim((string) $request->input('search', ''))) {
                $query->search($search);
            }

            if ($request->has('all') || $request->input('all') == 1) {
                $stocks = $query->get();
                return WarehouseStockResource::collection($stocks);
            }

            $perPage = (int) ($request->input('per_page') ?? $request->input('perPage') ?? 20);
            $stocks = $query->paginate($perPage);

            return WarehouseStockResource::collection($stocks);
        }

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
    public function addItem(Request $request, $goodsIssue): JsonResponse
    {
        $id = $goodsIssue instanceof GoodsIssue ? $goodsIssue->id : ($goodsIssue instanceof LegacyGoodsIssue ? $goodsIssue->id : $goodsIssue);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $legacyIssue = LegacyGoodsIssue::pengeluaran()->findOrFail($id);
            $request->validate([
                'medicine_id' => ['required', 'integer'],
                'quantity' => ['required', 'numeric', 'gt:0'],
                'stock_id' => ['nullable', 'integer'],
                'warehouse_stock_id' => ['nullable', 'integer'],
            ], [
                'medicine_id.required' => 'Sediaan obat wajib dipilih.',
                'quantity.required' => 'Jumlah sediaan wajib diisi.',
                'quantity.gt' => 'Jumlah sediaan harus lebih besar dari 0.',
            ]);

            $legacyUserId = $request->user()?->getLegacyUserId() ?? 1;
            $stockId = (int) ($request->input('warehouse_stock_id') ?? $request->input('stock_id'));
            $qty = (float) $request->input('quantity');

            $item = DB::connection('legacy')->transaction(function () use ($id, $stockId, $qty, $legacyUserId, $legacyIssue) {
                $detail = LegacyGoodsIssueItem::create([
                    'id_lgs_pengeluaran' => $id,
                    'id_lgs_log_stok' => $stockId,
                    'jumlah' => $qty,
                    'id_user' => $legacyUserId,
                    'status' => 1,
                ]);

                if ($legacyIssue->status == 1 && $stockId) {
                    $stock = LegacyStockLog::lockForUpdate()->find($stockId);
                    if ($stock) {
                        $stock->qty = max(0, $stock->qty - $qty);
                        $stock->save();
                    }
                }

                return $detail;
            });

            return (new GoodsIssueItemResource($item->load(['stockLog.warehouse', 'stockLog.fakturDetail.medicine.unit'])))
                ->response()
                ->setStatusCode(201);
        }

        $local = GoodsIssue::findOrFail($id);
        Gate::authorize('view', $local);

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

        $item = $this->service->addItem($local, $request->all(), (int) $request->user()->id);

        return (new GoodsIssueItemResource($item))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an item in goods issue.
     */
    public function updateItem(Request $request, $goodsIssue, $item): JsonResponse
    {
        $id = $goodsIssue instanceof GoodsIssue ? $goodsIssue->id : ($goodsIssue instanceof LegacyGoodsIssue ? $goodsIssue->id : $goodsIssue);
        $itemId = $item instanceof GoodsIssueItem ? $item->id : ($item instanceof LegacyGoodsIssueItem ? $item->id : $item);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $legacyIssue = LegacyGoodsIssue::pengeluaran()->findOrFail($id);
            $legacyItem = LegacyGoodsIssueItem::where('id_lgs_pengeluaran', $id)->findOrFail($itemId);

            $request->validate([
                'quantity' => ['required', 'numeric', 'gt:0'],
            ], [
                'quantity.required' => 'Jumlah sediaan wajib diisi.',
                'quantity.gt' => 'Jumlah sediaan harus lebih besar dari 0.',
            ]);

            $newQty = (float) $request->input('quantity');
            $diff = $newQty - (float) $legacyItem->jumlah;

            $updated = DB::connection('legacy')->transaction(function () use ($legacyIssue, $legacyItem, $newQty, $diff) {
                $legacyItem->update(['jumlah' => $newQty]);

                if ($legacyIssue->status == 1 && $legacyItem->id_lgs_log_stok) {
                    $stock = LegacyStockLog::lockForUpdate()->find($legacyItem->id_lgs_log_stok);
                    if ($stock) {
                        $stock->qty = max(0, $stock->qty - $diff);
                        $stock->save();
                    }
                }

                return $legacyItem;
            });

            return (new GoodsIssueItemResource($updated->load(['stockLog.warehouse', 'stockLog.fakturDetail.medicine.unit'])))
                ->response()
                ->setStatusCode(200);
        }

        $local = GoodsIssue::findOrFail($id);
        $localItem = GoodsIssueItem::where('goods_issue_id', $id)->findOrFail($itemId);
        Gate::authorize('view', $local);

        $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0'],
        ], [
            'quantity.required' => 'Jumlah sediaan wajib diisi.',
            'quantity.gt' => 'Jumlah sediaan harus lebih besar dari 0.',
        ]);

        $updatedItem = $this->service->updateItem($local, $localItem, $request->all(), (int) $request->user()->id);

        return (new GoodsIssueItemResource($updatedItem))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Delete an item from goods issue.
     */
    public function deleteItem($goodsIssue, $item): JsonResponse
    {
        $id = $goodsIssue instanceof GoodsIssue ? $goodsIssue->id : ($goodsIssue instanceof LegacyGoodsIssue ? $goodsIssue->id : $goodsIssue);
        $itemId = $item instanceof GoodsIssueItem ? $item->id : ($item instanceof LegacyGoodsIssueItem ? $item->id : $item);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $legacyIssue = LegacyGoodsIssue::pengeluaran()->findOrFail($id);
            $legacyItem = LegacyGoodsIssueItem::where('id_lgs_pengeluaran', $id)->findOrFail($itemId);

            DB::connection('legacy')->transaction(function () use ($legacyIssue, $legacyItem) {
                if ($legacyIssue->status == 1 && $legacyItem->id_lgs_log_stok) {
                    $stock = LegacyStockLog::lockForUpdate()->find($legacyItem->id_lgs_log_stok);
                    if ($stock) {
                        $stock->qty = $stock->qty + (float) $legacyItem->jumlah;
                        $stock->save();
                    }
                }
                $legacyItem->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Item detail pengeluaran berhasil dihapus.',
            ]);
        }

        $local = GoodsIssue::findOrFail($id);
        $localItem = GoodsIssueItem::where('goods_issue_id', $id)->findOrFail($itemId);
        Gate::authorize('view', $local);

        $this->service->deleteItem($local, $localItem, (int) auth()->id());

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

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $query = LegacyGoodsIssue::pengeluaran()->with([
                'employee',
                'items.stockLog.warehouse',
                'items.stockLog.fakturDetail.medicine.unit',
            ]);

            $issues = $query->orderByDesc('tanggal')->get();

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
                    $date = $it->tanggal ? Carbon::parse($it->tanggal)->format('d M Y H:i').' WIB' : '-';
                    fputcsv($handle, [
                        $idx + 1,
                        $it->issue_number,
                        $it->reference_number ?? '-',
                        $date,
                        $it->warehouse_name ?? 'Gudang Utama',
                        $it->officer_name,
                        $it->purpose,
                        $it->items->count(),
                        (float) $it->items->sum('jumlah'),
                        ucfirst($it->status),
                        $it->notes ?? '-',
                    ]);
                }

                fclose($handle);
            }, 200, $headers);
        }

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
