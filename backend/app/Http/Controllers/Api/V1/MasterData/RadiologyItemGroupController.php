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

        $allowedSorts = ['id', 'name', 'description', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'name';
        }

        $groups = $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc')->paginate($perPage);

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
