<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreSupplierRequest;
use App\Http\Requests\MasterData\UpdateSupplierRequest;
use App\Http\Resources\MasterData\SupplierResource;
use App\Models\Legacy\LegacySupplier;
use App\Models\MasterData\Supplier;
use App\Support\LegacyAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $authModel = LegacyAccess::isAvailable('ref_supplier') ? LegacySupplier::class : Supplier::class;
        Gate::authorize('viewAny', $authModel);

        $perPage = $request->input('per_page', 10);

        if (LegacyAccess::isAvailable('ref_supplier')) {
            $query = LegacySupplier::query();

            if ($search = $request->get('search')) {
                $query->search($search);
            }

            if ($request->has('is_active') && $request->is_active !== null && $request->is_active !== '') {
                $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? 1 : 0);
            }

            $sortBy = $request->input('sort', $request->input('sortBy', 'name'));
            if ($request->has('direction')) {
                $sortDesc = strtolower($request->input('direction')) === 'desc';
            } else {
                $sortDesc = filter_var($request->input('sortDesc', false), FILTER_VALIDATE_BOOLEAN);
            }

            $columnMap = [
                'id' => 'id',
                'name' => 'nama',
                'phone' => 'telp',
                'address' => 'alamat',
                'is_active' => 'status',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
            ];
            $sortCol = $columnMap[$sortBy] ?? 'nama';
            $query->orderBy($sortCol, $sortDesc ? 'desc' : 'asc');

            if ($perPage === '-1' || $perPage === 'all') {
                return SupplierResource::collection($query->get());
            }

            return SupplierResource::collection($query->paginate((int) $perPage));
        }

        $query = Supplier::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->is_active !== null && $request->is_active !== '') {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort', $request->input('sortBy', 'name'));
        if ($request->has('direction')) {
            $sortDesc = strtolower($request->input('direction')) === 'desc';
        } else {
            $sortDesc = filter_var($request->input('sortDesc', false), FILTER_VALIDATE_BOOLEAN);
        }

        $allowedSorts = ['id', 'name', 'phone', 'address', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        if ($perPage === '-1' || $perPage === 'all') {
            return SupplierResource::collection($query->get());
        }

        return SupplierResource::collection(
            $query->paginate((int) $perPage)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupplierRequest $request)
    {
        $authModel = LegacyAccess::isAvailable('ref_supplier') ? LegacySupplier::class : Supplier::class;
        Gate::authorize('create', $authModel);

        $validated = $request->validated();
        if (! isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

        if (LegacyAccess::isAvailable('ref_supplier')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], 403);
            }

            return DB::connection('legacy')->transaction(function () use ($validated, $legacyUserId) {
                $supplier = new LegacySupplier();
                $supplier->name = $validated['name'];
                if (array_key_exists('phone', $validated)) {
                    $supplier->phone = $validated['phone'];
                }
                if (array_key_exists('address', $validated)) {
                    $supplier->address = $validated['address'];
                }
                $supplier->is_active = $validated['is_active'];
                if ($legacyUserId) {
                    $supplier->id_user = $legacyUserId;
                }
                $supplier->save();

                return (new SupplierResource($supplier))->response()->setStatusCode(201);
            });
        }

        $supplier = Supplier::create($validated);

        return (new SupplierResource($supplier))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show($supplier)
    {
        $id = $supplier instanceof \Illuminate\Database\Eloquent\Model ? $supplier->id : $supplier;

        if (LegacyAccess::isAvailable('ref_supplier')) {
            $legacy = LegacySupplier::findOrFail($id);
            Gate::authorize('view', $legacy);

            return new SupplierResource($legacy);
        }

        if (! ($supplier instanceof Supplier)) {
            $supplier = Supplier::findOrFail($id);
        }

        Gate::authorize('view', $supplier);

        return new SupplierResource($supplier);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupplierRequest $request, $supplier)
    {
        $id = $supplier instanceof \Illuminate\Database\Eloquent\Model ? $supplier->id : $supplier;

        if (LegacyAccess::isAvailable('ref_supplier')) {
            $legacy = LegacySupplier::findOrFail($id);
            Gate::authorize('update', $legacy);

            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], 403);
            }

            $validated = $request->validated();

            return DB::connection('legacy')->transaction(function () use ($legacy, $validated, $legacyUserId) {
                $legacy->name = $validated['name'];
                if (array_key_exists('phone', $validated)) {
                    $legacy->phone = $validated['phone'];
                }
                if (array_key_exists('address', $validated)) {
                    $legacy->address = $validated['address'];
                }
                if (isset($validated['is_active'])) {
                    $legacy->is_active = $validated['is_active'];
                }
                if ($legacyUserId) {
                    $legacy->id_user = $legacyUserId;
                }
                $legacy->save();

                return new SupplierResource($legacy);
            });
        }

        if (! ($supplier instanceof Supplier)) {
            $supplier = Supplier::findOrFail($id);
        }

        Gate::authorize('update', $supplier);

        $supplier->update($request->validated());

        return new SupplierResource($supplier);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($supplier): JsonResponse
    {
        $id = $supplier instanceof \Illuminate\Database\Eloquent\Model ? $supplier->id : $supplier;

        if (LegacyAccess::isAvailable('ref_supplier')) {
            $legacy = LegacySupplier::findOrFail($id);
            Gate::authorize('delete', $legacy);

            $user = request()->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], 403);
            }

            if ($legacy->isReferencedInTransactions()) {
                return response()->json([
                    'message' => 'Supplier tidak dapat dihapus karena sudah memiliki riwayat faktur pembelian.',
                ], 409);
            }

            DB::connection('legacy')->transaction(function () use ($legacy, $legacyUserId) {
                $legacy->performSoftDelete($legacyUserId);
            });

            return response()->json(['message' => 'Supplier deleted successfully']);
        }

        if (! ($supplier instanceof Supplier)) {
            $supplier = Supplier::findOrFail($id);
        }

        Gate::authorize('delete', $supplier);
        $supplier->delete();

        return response()->json(['message' => 'Supplier deleted successfully']);
    }
}
