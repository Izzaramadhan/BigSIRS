<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreMedicineCategoryRequest;
use App\Http\Requests\MasterData\UpdateMedicineCategoryRequest;
use App\Http\Resources\MasterData\MedicineCategoryResource;
use App\Models\MasterData\MedicineCategory;
use Illuminate\Http\Request;

class MedicineCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MedicineCategory::query();

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }
        
        $sortDesc = filter_var($request->get('sortDesc', false), FILTER_VALIDATE_BOOLEAN);
        $sortBy = $request->get('sortBy', 'created_at');
        $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');

        $perPage = $request->get('per_page', 10);
        
        if ($perPage === '-1' || $perPage === 'all') {
            return MedicineCategoryResource::collection($query->get());
        }

        return MedicineCategoryResource::collection($query->paginate($perPage));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMedicineCategoryRequest $request)
    {
        $category = MedicineCategory::create($request->validated());

        return new MedicineCategoryResource($category);
    }

    /**
     * Display the specified resource.
     */
    public function show(MedicineCategory $medicineCategory)
    {
        return new MedicineCategoryResource($medicineCategory);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMedicineCategoryRequest $request, MedicineCategory $medicineCategory)
    {
        $medicineCategory->update($request->validated());

        return new MedicineCategoryResource($medicineCategory);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MedicineCategory $medicineCategory)
    {
        $medicineCategory->delete();

        return response()->noContent();
    }
}
