<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreLaboratoryCategoryRequest;
use App\Http\Requests\MasterData\UpdateLaboratoryCategoryRequest;
use App\Http\Resources\MasterData\LaboratoryCategoryResource;
use App\Models\MasterData\LaboratoryCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LaboratoryCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = LaboratoryCategory::query();

        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $sortDesc = filter_var($request->get('sortDesc', false), FILTER_VALIDATE_BOOLEAN);
        $sortBy = $request->get('sortBy', 'created_at');
        $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');

        $perPage = $request->get('per_page', 10);
        
        if ($perPage === '-1' || $perPage === 'all') {
            return LaboratoryCategoryResource::collection($query->get());
        }

        return LaboratoryCategoryResource::collection($query->paginate($perPage));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLaboratoryCategoryRequest $request)
    {
        $category = LaboratoryCategory::create($request->validated());

        return new LaboratoryCategoryResource($category);
    }

    /**
     * Display the specified resource.
     */
    public function show(LaboratoryCategory $laboratoryCategory)
    {
        return new LaboratoryCategoryResource($laboratoryCategory);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLaboratoryCategoryRequest $request, LaboratoryCategory $laboratoryCategory)
    {
        $laboratoryCategory->update($request->validated());

        return new LaboratoryCategoryResource($laboratoryCategory);
    }
    
    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(Request $request, LaboratoryCategory $laboratoryCategory)
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $laboratoryCategory->update([
            'is_active' => $request->is_active,
        ]);

        return new LaboratoryCategoryResource($laboratoryCategory);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LaboratoryCategory $laboratoryCategory)
    {
        // TODO: Check if it's used by lab items or something later. Right now we don't have the lab items model.
        // Once the next part of the module is built, this should prevent deletion if used.
        $laboratoryCategory->delete();

        return response()->noContent();
    }
}
