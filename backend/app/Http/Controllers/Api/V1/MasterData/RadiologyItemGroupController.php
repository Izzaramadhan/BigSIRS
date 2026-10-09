<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRadiologyItemGroupRequest;
use App\Http\Requests\UpdateRadiologyItemGroupRequest;
use App\Http\Resources\MasterData\RadiologyItemGroupResource;
use App\Models\MasterData\RadiologyItemGroup;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RadiologyItemGroupController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', RadiologyItemGroup::class);

        $query = RadiologyItemGroup::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 10);
        $groups = $query->orderBy('name')->paginate($perPage);

        return RadiologyItemGroupResource::collection($groups);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRadiologyItemGroupRequest $request)
    {
        $this->authorize('create', RadiologyItemGroup::class);

        $group = RadiologyItemGroup::create($request->validated());

        return response()->json([
            'message' => 'Kelompok Item Rad berhasil dibuat',
            'data' => new RadiologyItemGroupResource($group),
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(RadiologyItemGroup $radiologyItemGroup)
    {
        $this->authorize('view', $radiologyItemGroup);

        return new RadiologyItemGroupResource($radiologyItemGroup);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRadiologyItemGroupRequest $request, RadiologyItemGroup $radiologyItemGroup)
    {
        $this->authorize('update', $radiologyItemGroup);

        $radiologyItemGroup->update($request->validated());

        return response()->json([
            'message' => 'Kelompok Item Rad berhasil diperbarui',
            'data' => new RadiologyItemGroupResource($radiologyItemGroup),
        ]);
    }

    /**
     * Update status of the specified resource.
     */
    public function updateStatus(Request $request, RadiologyItemGroup $radiologyItemGroup)
    {
        $this->authorize('update', $radiologyItemGroup);

        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $radiologyItemGroup->update([
            'is_active' => $request->is_active,
        ]);

        return response()->json([
            'message' => 'Status Kelompok Item Rad berhasil diperbarui',
            'data' => new RadiologyItemGroupResource($radiologyItemGroup),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RadiologyItemGroup $radiologyItemGroup)
    {
        $this->authorize('delete', $radiologyItemGroup);

        $radiologyItemGroup->delete();

        return response()->json([
            'message' => 'Kelompok Item Rad berhasil dihapus',
        ]);
    }
}
