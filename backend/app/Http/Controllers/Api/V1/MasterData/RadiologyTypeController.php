<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRadiologyTypeRequest;
use App\Http\Requests\UpdateRadiologyTypeRequest;
use App\Http\Resources\MasterData\RadiologyTypeResource;
use App\Models\MasterData\RadiologyType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class RadiologyTypeController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', RadiologyType::class);

        $query = RadiologyType::query();

        if ($request->has('search') && trim($request->input('search')) !== '') {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->input('is_active') !== '' && $request->input('is_active') !== null) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = $request->input('per_page', 10);

        $sortBy = $request->get('sortBy') ?? $request->get('sort_by', 'name');
        $sortDesc = filter_var($request->get('sortDesc', false), FILTER_VALIDATE_BOOLEAN)
            || filter_var($request->get('sort_desc', false), FILTER_VALIDATE_BOOLEAN);
        $types = $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc')->paginate($perPage);

        return RadiologyTypeResource::collection($types);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRadiologyTypeRequest $request)
    {
        $this->authorize('create', RadiologyType::class);

        $type = RadiologyType::create($request->validated());

        return new RadiologyTypeResource($type);
    }

    /**
     * Display the specified resource.
     */
    public function show(RadiologyType $radiologyType)
    {
        $this->authorize('view', $radiologyType);

        return new RadiologyTypeResource($radiologyType);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRadiologyTypeRequest $request, RadiologyType $radiologyType)
    {
        $this->authorize('update', $radiologyType);

        $radiologyType->update($request->validated());

        return new RadiologyTypeResource($radiologyType);
    }

    /**
     * Update the active status of the specified resource.
     */
    public function updateStatus(Request $request, RadiologyType $radiologyType)
    {
        $this->authorize('update', $radiologyType);

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $radiologyType->update(['is_active' => $validated['is_active']]);

        return new RadiologyTypeResource($radiologyType);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RadiologyType $radiologyType)
    {
        $this->authorize('delete', $radiologyType);

        $radiologyType->delete();

        return response()->noContent();
    }
}
