<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRadiologyCategoryRequest;
use App\Http\Requests\UpdateRadiologyCategoryRequest;
use App\Http\Resources\MasterData\RadiologyCategoryResource;
use App\Models\Legacy\LegacyRadiologyCategory;
use App\Models\MasterData\RadiologyCategory;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class RadiologyCategoryController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', RadiologyCategory::class);

        if (LegacyAccess::isAvailable('ref_kategori_rad')) {
            $query = LegacyRadiologyCategory::query();

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->has('is_active') && $request->input('is_active') !== '' && $request->input('is_active') !== null) {
                $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? 1 : 0);
            }

            $query->orderBy('nama', 'asc');

            $perPage = $request->get('per_page', 10);
            if ($perPage === '-1' || $perPage === 'all') {
                return RadiologyCategoryResource::collection($query->get());
            }

            return RadiologyCategoryResource::collection($query->paginate((int) $perPage));
        }

        $query = RadiologyCategory::query()
            ->search($request->input('search'));

        if ($request->has('is_active') && $request->input('is_active') !== '' && $request->input('is_active') !== null) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $sortDesc = filter_var($request->get('sortDesc', false), FILTER_VALIDATE_BOOLEAN)
            || filter_var($request->get('sort_desc', false), FILTER_VALIDATE_BOOLEAN);
        $sortBy = $request->get('sortBy') ?? $request->get('sort_by', 'name');
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

        if (LegacyAccess::isAvailable('ref_kategori_rad')) {
            $data = $request->validated();
            $category = LegacyRadiologyCategory::create([
                'nama' => $data['name'],
                'deskripsi' => $data['description'] ?? null,
                'status' => array_key_exists('is_active', $data) ? ($data['is_active'] ? '1' : '0') : '1',
                'loinc_code' => $data['loinc_code'] ?? null,
                'loinc_url' => $data['loinc_url'] ?? null,
                'snomed_code' => $data['snomed_code'] ?? null,
                'snomed_url' => $data['snomed_url'] ?? null,
                'id_user' => auth()->id() ?? 1,
            ]);

            return (new RadiologyCategoryResource($category))->response()->setStatusCode(201);
        }

        $category = RadiologyCategory::create($request->validated());

        return new RadiologyCategoryResource($category);
    }

    /**
     * Display the specified resource.
     */
    public function show($radiologyCategory)
    {
        $id = $radiologyCategory instanceof LegacyRadiologyCategory || $radiologyCategory instanceof RadiologyCategory
            ? $radiologyCategory->id
            : $radiologyCategory;

        if (LegacyAccess::isAvailable('ref_kategori_rad')) {
            $legacy = LegacyRadiologyCategory::findOrFail($id);
            return new RadiologyCategoryResource($legacy);
        }

        if (! ($radiologyCategory instanceof RadiologyCategory)) {
            $radiologyCategory = RadiologyCategory::findOrFail($id);
        }

        $this->authorize('view', $radiologyCategory);

        return new RadiologyCategoryResource($radiologyCategory);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRadiologyCategoryRequest $request, $radiologyCategory)
    {
        $id = $radiologyCategory instanceof LegacyRadiologyCategory || $radiologyCategory instanceof RadiologyCategory
            ? $radiologyCategory->id
            : $radiologyCategory;

        if (LegacyAccess::isAvailable('ref_kategori_rad')) {
            $legacy = LegacyRadiologyCategory::findOrFail($id);
            $this->authorize('update', $legacy);

            $data = $request->validated();
            $updateData = [
                'nama' => $data['name'],
                'deskripsi' => $data['description'] ?? null,
                'loinc_code' => $data['loinc_code'] ?? null,
                'loinc_url' => $data['loinc_url'] ?? null,
                'snomed_code' => $data['snomed_code'] ?? null,
                'snomed_url' => $data['snomed_url'] ?? null,
            ];
            if (array_key_exists('is_active', $data)) {
                $updateData['status'] = $data['is_active'] ? '1' : '0';
            }
            $legacy->update($updateData);

            return new RadiologyCategoryResource($legacy);
        }

        if (! ($radiologyCategory instanceof RadiologyCategory)) {
            $radiologyCategory = RadiologyCategory::findOrFail($id);
        }

        $this->authorize('update', $radiologyCategory);

        $radiologyCategory->update($request->validated());

        return new RadiologyCategoryResource($radiologyCategory);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($radiologyCategory)
    {
        $id = $radiologyCategory instanceof LegacyRadiologyCategory || $radiologyCategory instanceof RadiologyCategory
            ? $radiologyCategory->id
            : $radiologyCategory;

        if (LegacyAccess::isAvailable('ref_kategori_rad')) {
            $legacy = LegacyRadiologyCategory::findOrFail($id);
            $this->authorize('delete', $legacy);

            $legacy->delete();

            return response()->noContent();
        }

        if (! ($radiologyCategory instanceof RadiologyCategory)) {
            $radiologyCategory = RadiologyCategory::findOrFail($id);
        }

        $this->authorize('delete', $radiologyCategory);

        $radiologyCategory->delete();

        return response()->noContent();
    }
}
