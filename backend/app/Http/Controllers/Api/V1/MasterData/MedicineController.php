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

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('kfa_code', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id') && $request->category_id != '') {
            $query->where('medicine_category_id', $request->category_id);
        }

        if ($request->has('classification_id') && $request->classification_id != '') {
            $query->where('medicine_classification_id', $request->classification_id);
        }

        $perPage = $request->input('per_page', 10);
        $sortDesc = $request->input('sortDesc', 'false') === 'true';
        $sortBy = $request->input('sortBy', 'created_at');

        // Handle sorting for generic 'name' property
        if ($sortBy == 'name' || $sortBy == 'code') {
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $medicines = $query->paginate($perPage);

        return MedicineResource::collection($medicines);
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
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $medicine = Medicine::create($validated);

        return new MedicineResource($medicine);
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
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $medicine->update($validated);

        return new MedicineResource($medicine);
    }

    public function destroy(Medicine $medicine)
    {
        // Add protection logic if medicine is used in transactions later.
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
