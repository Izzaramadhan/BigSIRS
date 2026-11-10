<?php

namespace App\Http\Controllers\Api\V1\Logistics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\UpdateStockOpnameRequest;
use App\Http\Resources\Logistics\StockOpnameResource;
use App\Models\Legacy\LegacyStockLog;
use App\Models\Legacy\LegacyStockOpname;
use App\Models\Legacy\LegacyStockOpnameDetail;
use App\Models\Logistics\StockMovement;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Warehouse;
use App\Support\LegacyAccess;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockOpnameController extends Controller
{
    /**
     * Display a listing of real logistics warehouse stocks.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        if (LegacyAccess::isAvailable('lgs_log_stok')) {
            $query = LegacyStockLog::with(['warehouse', 'fakturDetail.medicine.unit']);

            // 1. Filter by Warehouse
            if ($request->filled('warehouse_id') && $request->input('warehouse_id') !== 'all') {
                $query->where('id_gudang', (int) $request->input('warehouse_id'));
            }

            // 2. Filter hide zero stock (default is true: hide 0 stock)
            $hideZero = true;
            if ($request->has('hide_zero_stock')) {
                $val = $request->input('hide_zero_stock');
                $hideZero = ! ($val === '0' || $val === 'false' || $val === false || $val === 0);
            }

            if ($hideZero) {
                $query->where('qty', '>', 0);
            }

            // 3. Search
            if ($search = trim((string) $request->input('search'))) {
                $query->search($search);
            }

            // 4. Sorting
            $sort = $request->input('sort', 'id');
            $direction = strtolower($request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

            $legacySortMap = [
                'id' => 'id',
                'quantity' => 'qty',
                'selling_price' => 'harga_jual',
            ];

            if (isset($legacySortMap[$sort])) {
                $query->orderBy($legacySortMap[$sort], $direction);
            } else {
                $query->orderBy('id', 'asc');
            }

            // 5. Pagination
            $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
            $paginated = $query->paginate($perPage);

            return StockOpnameResource::collection($paginated);
        }

        $query = WarehouseStock::with(['warehouse', 'medicine.unit']);

        // 1. Filter by Warehouse
        if ($request->filled('warehouse_id') && $request->input('warehouse_id') !== 'all') {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }

        // 2. Filter hide zero stock (default is true: hide 0 stock)
        $hideZero = true;
        if ($request->has('hide_zero_stock')) {
            $val = $request->input('hide_zero_stock');
            $hideZero = ! ($val === '0' || $val === 'false' || $val === false || $val === 0);
        }

        if ($hideZero) {
            $query->where('quantity', '>', 0);
        }

        // 3. Search by medicine name, code, batch number, or unit
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('medicine', function ($mq) use ($search) {
                    $mq->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhereHas('unit', function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%");
                        });
                })
                    ->orWhere('batch_number', 'like', "%{$search}%");
            });
        }

        // 4. Sorting
        $sort = $request->input('sort', 'id');
        $direction = strtolower($request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (in_array($sort, ['id', 'quantity', 'selling_price', 'expired_date', 'batch_number'])) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('id', 'asc');
        }

        // 5. Pagination
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $paginated = $query->paginate($perPage);

        return StockOpnameResource::collection($paginated);
    }

    /**
     * Display the specified warehouse stock.
     */
    public function show(int|string|WarehouseStock $stockOpname): JsonResponse
    {
        $stockId = $stockOpname instanceof WarehouseStock ? $stockOpname->id : ($stockOpname instanceof LegacyStockLog ? $stockOpname->id : (int) $stockOpname);

        if (LegacyAccess::isAvailable('lgs_log_stok')) {
            $legacyStock = LegacyStockLog::with(['warehouse', 'fakturDetail.medicine.unit'])->find($stockId);
            if ($legacyStock) {
                return (new StockOpnameResource($legacyStock))->response();
            }
        }

        $stockRecord = WarehouseStock::with(['warehouse', 'medicine.unit'])->findOrFail($stockId);

        return (new StockOpnameResource($stockRecord))->response();
    }

    /**
     * Update warehouse stock details (expired date, selling price, and final stock).
     * Performs atomic transaction with row locking and records stock movement ledger.
     */
    public function update(UpdateStockOpnameRequest $request, int|string|WarehouseStock $stockOpname): JsonResponse
    {
        $stockId = $stockOpname instanceof WarehouseStock ? $stockOpname->id : ($stockOpname instanceof LegacyStockLog ? $stockOpname->id : (int) $stockOpname);

        if (LegacyAccess::isAvailable('lgs_log_stok')) {
            $legacyUserId = $request->user()?->getLegacyUserId() ?? 1;

            $updatedStock = DB::connection('legacy')->transaction(function () use ($request, $stockId, $legacyUserId) {
                /** @var LegacyStockLog $stockRecord */
                $stockRecord = LegacyStockLog::where('id', $stockId)->lockForUpdate()->firstOrFail();

                // Guard: Warehouse is strictly read-only in stock opname (cannot change warehouse)
                if ($request->filled('warehouse_id')) {
                    $requestedWarehouseId = (int) $request->input('warehouse_id');
                    if ($requestedWarehouseId !== (int) $stockRecord->id_gudang) {
                        throw ValidationException::withMessages([
                            'warehouse_id' => [
                                'Gudang pada stok sediaan bersifat read-only dan tidak dapat dipindahkan melalui form stok opname. Gunakan modul Mutasi Barang untuk memindahkan stok antar gudang.',
                            ],
                        ]);
                    }
                }

                $oldStock = (float) $stockRecord->qty;
                $newStock = (float) $request->input('stock');
                $difference = round($newStock - $oldStock, 2);

                $newSellingPrice = (float) $request->input('selling_price');

                $newExpDate = null;
                if ($request->filled('expiration_date')) {
                    try {
                        $newExpDate = Carbon::parse($request->input('expiration_date'))->format('Y-m-d');
                    } catch (\Throwable) {
                        $newExpDate = null;
                    }
                }

                // Update stock log
                $stockRecord->qty = $newStock;
                $stockRecord->harga_jual = $newSellingPrice;
                $stockRecord->id_user = $legacyUserId;
                $stockRecord->save();

                // Update expiration date on faktur detail if present
                if ($newExpDate && $stockRecord->fakturDetail) {
                    $stockRecord->fakturDetail->update([
                        'ed' => $newExpDate,
                        'id_user' => $legacyUserId,
                    ]);
                }

                // Record stock opname transaction
                if (LegacyAccess::hasTable('lgs_stok_opname')) {
                    $opname = LegacyStockOpname::create([
                        'tanggal' => Carbon::today()->toDateString(),
                        'id_gudang' => $stockRecord->id_gudang,
                        'id_user' => $legacyUserId,
                        'status' => 3, // Selesai
                    ]);

                    if (LegacyAccess::hasTable('lgs_stok_opname_detail')) {
                        LegacyStockOpnameDetail::create([
                            'id_lgs_stok_opname' => $opname->id,
                            'id_lgs_log_stok' => $stockRecord->id,
                            'stok_awal' => $oldStock,
                            'pemasukan' => $difference > 0 ? $difference : 0,
                            'pengeluaran' => $difference < 0 ? abs($difference) : 0,
                            'stok_akhir' => $newStock,
                            'selisih' => $difference,
                            'fisik' => $newStock,
                            'id_user' => $legacyUserId,
                            'status' => 1,
                        ]);
                    }
                }

                return $stockRecord->fresh(['warehouse', 'fakturDetail.medicine.unit']);
            });

            return (new StockOpnameResource($updatedStock))
                ->additional([
                    'message' => 'Stok opname berhasil diperbarui.',
                ])
                ->response();
        }

        $updatedStock = DB::transaction(function () use ($request, $stockId) {
            /** @var WarehouseStock $stockRecord */
            $stockRecord = WarehouseStock::where('id', $stockId)->lockForUpdate()->firstOrFail();

            // Guard: Warehouse is strictly read-only in stock opname (cannot change warehouse)
            if ($request->filled('warehouse_id')) {
                $requestedWarehouseId = (int) $request->input('warehouse_id');
                if ($requestedWarehouseId !== (int) $stockRecord->warehouse_id) {
                    throw ValidationException::withMessages([
                        'warehouse_id' => [
                            'Gudang pada stok sediaan bersifat read-only dan tidak dapat dipindahkan melalui form stok opname. Gunakan modul Mutasi Barang untuk memindahkan stok antar gudang.',
                        ],
                    ]);
                }
            }

            $oldStock = (float) $stockRecord->quantity;
            $newStock = (float) $request->input('stock');
            $difference = round($newStock - $oldStock, 2);

            $newSellingPrice = (float) $request->input('selling_price');

            $newExpDate = null;
            if ($request->filled('expiration_date')) {
                try {
                    $newExpDate = Carbon::parse($request->input('expiration_date'))->format('Y-m-d');
                } catch (\Throwable) {
                    $newExpDate = null;
                }
            }

            // Update warehouse stock
            $stockRecord->quantity = $newStock;
            $stockRecord->selling_price = $newSellingPrice;
            $stockRecord->expired_date = $newExpDate;
            $stockRecord->save();

            // Record stock movement adjustment if quantity changed
            if (abs($difference) > 0.0001) {
                StockMovement::create([
                    'warehouse_id' => $stockRecord->warehouse_id,
                    'medicine_id' => $stockRecord->medicine_id,
                    'warehouse_stock_id' => $stockRecord->id,
                    'movement_type' => 'stock_opname_adjustment',
                    'direction' => $difference > 0 ? 'in' : 'out',
                    'quantity' => abs($difference),
                    'balance_before' => $oldStock,
                    'balance_after' => $newStock,
                    'batch_number' => $stockRecord->batch_number,
                    'expired_date' => $stockRecord->expired_date,
                    'occurred_at' => Carbon::now(),
                    'created_by' => Auth::id() ?? 1,
                ]);
            }

            return $stockRecord->load(['warehouse', 'medicine.unit']);
        });

        return (new StockOpnameResource($updatedStock))
            ->additional([
                'message' => 'Stok opname berhasil diperbarui.',
            ])
            ->response();
    }

    /**
     * Export warehouse stocks dataset to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        if (LegacyAccess::isAvailable('lgs_log_stok')) {
            $query = LegacyStockLog::with(['warehouse', 'fakturDetail.medicine.unit']);

            if ($request->filled('warehouse_id') && $request->input('warehouse_id') !== 'all') {
                $query->where('id_gudang', (int) $request->input('warehouse_id'));
            }

            $hideZero = true;
            if ($request->has('hide_zero_stock')) {
                $val = $request->input('hide_zero_stock');
                $hideZero = ! ($val === '0' || $val === 'false' || $val === false || $val === 0);
            }
            if ($hideZero) {
                $query->where('qty', '>', 0);
            }

            if ($search = trim((string) $request->input('search'))) {
                $query->search($search);
            }

            $stocks = $query->orderBy('id', 'asc')->get();

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="stok_opname_'.Carbon::now()->format('Y-m-d_His').'.csv"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            return response()->stream(function () use ($stocks) {
                $handle = fopen('php://output', 'w');
                fputs($handle, "\xEF\xBB\xBF");
                fputcsv($handle, ['ID', 'Gudang', 'Kode Obat', 'Nama Obat', 'Satuan', 'Nomor Batch', 'Kedaluwarsa', 'Harga Jual', 'Stok Saat Ini']);

                foreach ($stocks as $stock) {
                    $med = $stock->medicine;
                    $expFormatted = '-';
                    if (!empty($stock->expired_date) && !str_starts_with((string) $stock->expired_date, '0000')) {
                        try {
                            $expFormatted = Carbon::parse($stock->expired_date)->format('Y-m-d');
                        } catch (\Throwable) {
                            $expFormatted = '-';
                        }
                    }

                    fputcsv($handle, [
                        $stock->id,
                        $stock->warehouse_name ?? '-',
                        $med?->code ?? '-',
                        $med?->name ?? '-',
                        $med?->unit?->name ?? '-',
                        $stock->batch_number ?? '-',
                        $expFormatted,
                        $stock->selling_price,
                        $stock->quantity,
                    ]);
                }

                fclose($handle);
            }, 200, $headers);
        }

        $query = WarehouseStock::with(['warehouse', 'medicine.unit']);

        if ($request->filled('warehouse_id') && $request->input('warehouse_id') !== 'all') {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }

        $hideZero = true;
        if ($request->has('hide_zero_stock')) {
            $val = $request->input('hide_zero_stock');
            $hideZero = ! ($val === '0' || $val === 'false' || $val === false || $val === 0);
        }
        if ($hideZero) {
            $query->where('quantity', '>', 0);
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('medicine', function ($mq) use ($search) {
                    $mq->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhereHas('unit', function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%");
                        });
                })
                    ->orWhere('batch_number', 'like', "%{$search}%");
            });
        }

        $stocks = $query->orderBy('id', 'asc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="stok_opname_'.Carbon::now()->format('Y-m-d_His').'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($stocks) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', 'Gudang', 'Kode Obat', 'Nama Obat', 'Satuan', 'Nomor Batch', 'Kedaluwarsa', 'Harga Jual', 'Stok Saat Ini']);

            foreach ($stocks as $stock) {
                fputcsv($handle, [
                    $stock->id,
                    $stock->warehouse?->name ?? '-',
                    $stock->medicine?->code ?? '-',
                    $stock->medicine?->name ?? '-',
                    $stock->medicine?->unit?->name ?? '-',
                    $stock->batch_number ?? '-',
                    $stock->expired_date ? Carbon::parse($stock->expired_date)->format('Y-m-d') : '-',
                    $stock->selling_price,
                    $stock->quantity,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
