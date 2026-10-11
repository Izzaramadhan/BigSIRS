<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreSupplierRequest;
use App\Http\Requests\MasterData\UpdateSupplierRequest;
use App\Http\Resources\MasterData\SupplierResource;
use App\Models\MasterData\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Supplier::class);

        $query = Supplier::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
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
        $validated = $request->validated();
        if (! isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

        $supplier = Supplier::create($validated);

        return new SupplierResource($supplier);
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        Gate::authorize('view', $supplier);

        return new SupplierResource($supplier);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $supplier->update($request->validated());

        return new SupplierResource($supplier);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        Gate::authorize('delete', $supplier);
        $supplier->delete();

        return response()->json(['message' => 'Supplier deleted successfully']);
    }
}
