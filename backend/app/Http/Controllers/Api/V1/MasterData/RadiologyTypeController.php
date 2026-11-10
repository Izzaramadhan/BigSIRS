<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRadiologyTypeRequest;
use App\Http\Requests\UpdateRadiologyTypeRequest;
use App\Http\Resources\MasterData\RadiologyTypeResource;
use App\Models\Legacy\LegacyRadiologyType;
use App\Models\MasterData\RadiologyType;
use App\Support\LegacyAccess;
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

        $perPage = (int) $request->input('per_page', 10);

        if (LegacyAccess::isAvailable('ref_tipe_rad')) {
            $query = LegacyRadiologyType::query()->orderBy('nama', 'asc');

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->has('is_active') && $request->input('is_active') !== '' && $request->input('is_active') !== null) {
                $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? 1 : 0);
            }

            return RadiologyTypeResource::collection($query->paginate($perPage));
        }

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

        if (LegacyAccess::isAvailable('ref_tipe_rad')) {
            $data = $request->validated();
            $type = LegacyRadiologyType::create([
                'nama' => $data['name'],
                'deskripsi' => $data['description'] ?? null,
                'status' => array_key_exists('is_active', $data) ? ($data['is_active'] ? 1 : 0) : 1,
                'id_user' => auth()->id() ?? 1,
            ]);

            return (new RadiologyTypeResource($type))->response()->setStatusCode(201);
        }

        $type = RadiologyType::create($request->validated());

        return new RadiologyTypeResource($type);
    }

    /**
     * Display the specified resource.
     */
    public function show($radiologyType)
    {
        $id = $radiologyType instanceof LegacyRadiologyType || $radiologyType instanceof RadiologyType
            ? $radiologyType->id
            : $radiologyType;

        if (LegacyAccess::isAvailable('ref_tipe_rad')) {
            $legacy = LegacyRadiologyType::findOrFail($id);
            return new RadiologyTypeResource($legacy);
        }

        if (! ($radiologyType instanceof RadiologyType)) {
            $radiologyType = RadiologyType::findOrFail($id);
        }

        $this->authorize('view', $radiologyType);

        return new RadiologyTypeResource($radiologyType);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRadiologyTypeRequest $request, $radiologyType)
    {
        $id = $radiologyType instanceof LegacyRadiologyType || $radiologyType instanceof RadiologyType
            ? $radiologyType->id
            : $radiologyType;

        if (LegacyAccess::isAvailable('ref_tipe_rad')) {
            $legacy = LegacyRadiologyType::findOrFail($id);
            $this->authorize('update', $legacy);

            $data = $request->validated();
            $updateData = [
                'nama' => $data['name'],
                'deskripsi' => $data['description'] ?? null,
            ];
            if (array_key_exists('is_active', $data)) {
                $updateData['status'] = $data['is_active'] ? 1 : 0;
            }
            $legacy->update($updateData);

            return new RadiologyTypeResource($legacy);
        }

        if (! ($radiologyType instanceof RadiologyType)) {
            $radiologyType = RadiologyType::findOrFail($id);
        }

        $this->authorize('update', $radiologyType);

        $radiologyType->update($request->validated());

        return new RadiologyTypeResource($radiologyType);
    }

    /**
     * Update the active status of the specified resource.
     */
    public function updateStatus(Request $request, $radiologyType)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $id = $radiologyType instanceof LegacyRadiologyType || $radiologyType instanceof RadiologyType
            ? $radiologyType->id
            : $radiologyType;

        if (LegacyAccess::isAvailable('ref_tipe_rad')) {
            $legacy = LegacyRadiologyType::findOrFail($id);
            $this->authorize('update', $legacy);

            $legacy->update(['status' => $validated['is_active'] ? 1 : 0]);

            return new RadiologyTypeResource($legacy);
        }

        if (! ($radiologyType instanceof RadiologyType)) {
            $radiologyType = RadiologyType::findOrFail($id);
        }

        $this->authorize('update', $radiologyType);

        $radiologyType->update(['is_active' => $validated['is_active']]);

        return new RadiologyTypeResource($radiologyType);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($radiologyType)
    {
        $id = $radiologyType instanceof LegacyRadiologyType || $radiologyType instanceof RadiologyType
            ? $radiologyType->id
            : $radiologyType;

        if (LegacyAccess::isAvailable('ref_tipe_rad')) {
            $legacy = LegacyRadiologyType::findOrFail($id);
            $this->authorize('delete', $legacy);

            $legacy->delete();

            return response()->noContent();
        }

        if (! ($radiologyType instanceof RadiologyType)) {
            $radiologyType = RadiologyType::findOrFail($id);
        }

        $this->authorize('delete', $radiologyType);

        $radiologyType->delete();

        return response()->noContent();
    }
}
