<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreWarehouseRequest;
use App\Http\Requests\MasterData\UpdateWarehouseRequest;
use App\Http\Resources\MasterData\WarehouseResource;
use App\Models\MasterData\Warehouse;
use App\Models\Polyclinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Warehouse::class);

        $query = Warehouse::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 10);
        $warehouses = $query->latest()->paginate($perPage);

        return WarehouseResource::collection($warehouses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWarehouseRequest $request)
    {
        Gate::authorize('create', Warehouse::class);

        $warehouse = Warehouse::create($request->validated());

        return new WarehouseResource($warehouse);
    }

    /**
     * Display the specified resource.
     */
    public function show(Warehouse $warehouse)
    {
        Gate::authorize('view', $warehouse);

        return new WarehouseResource($warehouse);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        Gate::authorize('update', $warehouse);

        $warehouse->update($request->validated());

        return new WarehouseResource($warehouse);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warehouse $warehouse)
    {
        Gate::authorize('delete', $warehouse);

        // Check if warehouse is used in polyclinics
        // Note: Currently Polyclinic uses legacy_default_warehouse_id,
        // we check if warehouse->legacy_id matches any polyclinic's legacy_default_warehouse_id.
        // If we later map warehouse_id directly, we should check that as well.
        if ($warehouse->legacy_id) {
            $isUsedInPolyclinic = Polyclinic::where('legacy_default_warehouse_id', $warehouse->legacy_id)->exists();
            if ($isUsedInPolyclinic) {
                return response()->json([
                    'message' => 'Gudang tidak dapat dihapus karena masih digunakan oleh unit pelayanan atau transaksi logistik.',
                ], 403);
            }
        }

        // As a safeguard if we added warehouse_id to Polyclinic table in the future
        if (Schema::hasColumn('polyclinics', 'warehouse_id')) {
            $isUsedInPolyclinicDirectly = Polyclinic::where('warehouse_id', $warehouse->id)->exists();
            if ($isUsedInPolyclinicDirectly) {
                return response()->json([
                    'message' => 'Gudang tidak dapat dihapus karena masih digunakan oleh unit pelayanan atau transaksi logistik.',
                ], 403);
            }
        }

        $warehouse->delete();

        return response()->noContent();
    }
}
