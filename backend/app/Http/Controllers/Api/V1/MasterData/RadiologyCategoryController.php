<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRadiologyCategoryRequest;
use App\Http\Requests\UpdateRadiologyCategoryRequest;
use App\Http\Resources\MasterData\RadiologyCategoryResource;
use App\Models\MasterData\RadiologyCategory;
use Illuminate\Http\Request;

class RadiologyCategoryController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', RadiologyCategory::class);

        $query = RadiologyCategory::query()
            ->search($request->input('search'));

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $sortDesc = filter_var($request->get('sortDesc', false), FILTER_VALIDATE_BOOLEAN);
        $sortBy = $request->get('sortBy', 'name');
        $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');

        $perPage = $request->get('per_page', 10);

        if ($perPage === '-1' || $perPage === 'all') {
            return RadiologyCategoryResource::collection($query->get());
        }

        return RadiologyCategoryResource::collection($query->paginate($perPage));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRadiologyCategoryRequest $request)
    {
        $this->authorize('create', RadiologyCategory::class);

        $category = RadiologyCategory::create($request->validated());

        return new RadiologyCategoryResource($category);
    }

    /**
     * Display the specified resource.
     */
    public function show(RadiologyCategory $radiologyCategory)
    {
        $this->authorize('view', $radiologyCategory);

        return new RadiologyCategoryResource($radiologyCategory);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRadiologyCategoryRequest $request, RadiologyCategory $radiologyCategory)
    {
        $this->authorize('update', $radiologyCategory);

        $radiologyCategory->update($request->validated());

        return new RadiologyCategoryResource($radiologyCategory);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RadiologyCategory $radiologyCategory)
    {
        $this->authorize('delete', $radiologyCategory);

        $radiologyCategory->delete();

        return response()->noContent();
    }
}
