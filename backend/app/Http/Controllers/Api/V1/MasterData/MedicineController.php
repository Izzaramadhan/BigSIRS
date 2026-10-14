<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Resources\MasterData\MedicineResource;
use App\Models\MasterData\Medicine;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    public function index(Request $request)
    {
        $query = Medicine::with(['unit', 'category', 'classification', 'route', 'generic']);

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('kfa_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('medicine_category_id', $request->category_id);
        }

        if ($request->filled('classification_id')) {
            $query->where('medicine_classification_id', $request->classification_id);
        }

        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort', $request->input('sortBy', 'name'));
        if ($request->has('direction')) {
            $sortDesc = strtolower($request->input('direction')) === 'desc';
        } else {
            $sortDesc = filter_var($request->input('sortDesc', false), FILTER_VALIDATE_BOOLEAN);
        }

        $allowedSorts = ['id', 'code', 'name', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        if ($perPage === '-1' || $perPage === 'all') {
            return MedicineResource::collection($query->get());
        }

        return MedicineResource::collection($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'kfa_code' => 'nullable|string|max:255',
            'function' => 'nullable|string|max:255',
            'medicine_unit_id' => 'nullable|exists:medicine_units,id',
            'medicine_category_id' => 'nullable|exists:medicine_categories,id',
            'medicine_classification_id' => 'nullable|exists:medicine_classifications,id',
            'medicine_route_id' => 'nullable|exists:medicine_routes,id',
            'generic_medicine_id' => 'nullable|exists:generic_medicines,id',
            'selling_price_per_unit' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if (! isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

        $medicine = Medicine::create($validated);
        $medicine->load(['unit', 'category', 'classification', 'route', 'generic']);

        return (new MedicineResource($medicine))->response()->setStatusCode(201);
    }

    public function show(Medicine $medicine)
    {
        $medicine->load(['unit', 'category', 'classification', 'route', 'generic']);

        return new MedicineResource($medicine);
    }

    public function update(Request $request, Medicine $medicine)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'kfa_code' => 'nullable|string|max:255',
            'function' => 'nullable|string|max:255',
            'medicine_unit_id' => 'nullable|exists:medicine_units,id',
            'medicine_category_id' => 'nullable|exists:medicine_categories,id',
            'medicine_classification_id' => 'nullable|exists:medicine_classifications,id',
            'medicine_route_id' => 'nullable|exists:medicine_routes,id',
            'generic_medicine_id' => 'nullable|exists:generic_medicines,id',
            'selling_price_per_unit' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $medicine->update($validated);
        $medicine->load(['unit', 'category', 'classification', 'route', 'generic']);

        return new MedicineResource($medicine);
    }

    public function destroy(Medicine $medicine)
    {
        $medicine->delete();

        return response()->noContent();
    }

    public function updateStatus(Request $request, Medicine $medicine)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $medicine->update($validated);

        return new MedicineResource($medicine);
    }
}
