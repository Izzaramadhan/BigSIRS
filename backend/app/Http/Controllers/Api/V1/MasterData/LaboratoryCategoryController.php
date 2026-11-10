<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreLaboratoryCategoryRequest;
use App\Http\Requests\MasterData\UpdateLaboratoryCategoryRequest;
use App\Http\Resources\MasterData\LaboratoryCategoryResource;
use App\Models\Legacy\LegacyLaboratoryCategory;
use App\Models\MasterData\LaboratoryCategory;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;

class LaboratoryCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        if (LegacyAccess::isAvailable('ref_kategori_lab')) {
            $query = LegacyLaboratoryCategory::query()->orderBy('nama', 'asc');

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->has('is_active')) {
                $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? 1 : 0);
            }

            if ($perPage === '-1' || $perPage === 'all') {
                return LaboratoryCategoryResource::collection($query->get());
            }

            return LaboratoryCategoryResource::collection($query->paginate((int) $perPage));
        }

        $query = LaboratoryCategory::query();

        if ($request->has('search') && ! empty($request->search)) {
            $query->where('name', 'like', '%'.$request->search.'%');
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
        if (LegacyAccess::isAvailable('ref_kategori_lab')) {
            $data = $request->validated();
            $category = LegacyLaboratoryCategory::create([
                'nama' => $data['name'],
                'deskripsi' => $data['description'] ?? null,
                'tipe' => $data['type'] ?? null,
                'status' => array_key_exists('is_active', $data) ? ($data['is_active'] ? '1' : '0') : '1',
                'loinc_code' => $data['loinc_code'] ?? null,
                'loinc_url' => $data['loinc_url'] ?? null,
                'snomed_code' => $data['snomed_code'] ?? null,
                'snomed_url' => $data['snomed_url'] ?? null,
                'id_user' => auth()->id() ?? 1,
            ]);

            return (new LaboratoryCategoryResource($category))->response()->setStatusCode(201);
        }

        $category = LaboratoryCategory::create($request->validated());

        return new LaboratoryCategoryResource($category);
    }

    /**
     * Display the specified resource.
     */
    public function show($laboratoryCategory)
    {
        $id = $laboratoryCategory instanceof LegacyLaboratoryCategory || $laboratoryCategory instanceof LaboratoryCategory
            ? $laboratoryCategory->id
            : $laboratoryCategory;

        if (LegacyAccess::isAvailable('ref_kategori_lab')) {
            $legacy = LegacyLaboratoryCategory::findOrFail($id);
            return new LaboratoryCategoryResource($legacy);
        }

        if (! ($laboratoryCategory instanceof LaboratoryCategory)) {
            $laboratoryCategory = LaboratoryCategory::findOrFail($id);
        }

        return new LaboratoryCategoryResource($laboratoryCategory);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLaboratoryCategoryRequest $request, $laboratoryCategory)
    {
        $id = $laboratoryCategory instanceof LegacyLaboratoryCategory || $laboratoryCategory instanceof LaboratoryCategory
            ? $laboratoryCategory->id
            : $laboratoryCategory;

        if (LegacyAccess::isAvailable('ref_kategori_lab')) {
            $legacy = LegacyLaboratoryCategory::findOrFail($id);
            $data = $request->validated();
            $updateData = [
                'nama' => $data['name'],
                'deskripsi' => $data['description'] ?? null,
                'tipe' => $data['type'] ?? null,
                'loinc_code' => $data['loinc_code'] ?? null,
                'loinc_url' => $data['loinc_url'] ?? null,
                'snomed_code' => $data['snomed_code'] ?? null,
                'snomed_url' => $data['snomed_url'] ?? null,
            ];
            if (array_key_exists('is_active', $data)) {
                $updateData['status'] = $data['is_active'] ? '1' : '0';
            }
            $legacy->update($updateData);

            return new LaboratoryCategoryResource($legacy);
        }

        if (! ($laboratoryCategory instanceof LaboratoryCategory)) {
            $laboratoryCategory = LaboratoryCategory::findOrFail($id);
        }

        $laboratoryCategory->update($request->validated());

        return new LaboratoryCategoryResource($laboratoryCategory);
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(Request $request, $laboratoryCategory)
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $id = $laboratoryCategory instanceof LegacyLaboratoryCategory || $laboratoryCategory instanceof LaboratoryCategory
            ? $laboratoryCategory->id
            : $laboratoryCategory;

        if (LegacyAccess::isAvailable('ref_kategori_lab')) {
            $legacy = LegacyLaboratoryCategory::findOrFail($id);
            $legacy->update([
                'status' => $request->boolean('is_active') ? '1' : '0',
            ]);

            return new LaboratoryCategoryResource($legacy);
        }

        if (! ($laboratoryCategory instanceof LaboratoryCategory)) {
            $laboratoryCategory = LaboratoryCategory::findOrFail($id);
        }

        $laboratoryCategory->update([
            'is_active' => $request->is_active,
        ]);

        return new LaboratoryCategoryResource($laboratoryCategory);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($laboratoryCategory)
    {
        $id = $laboratoryCategory instanceof LegacyLaboratoryCategory || $laboratoryCategory instanceof LaboratoryCategory
            ? $laboratoryCategory->id
            : $laboratoryCategory;

        if (LegacyAccess::isAvailable('ref_kategori_lab')) {
            $legacy = LegacyLaboratoryCategory::findOrFail($id);
            $legacy->delete();

            return response()->noContent();
        }

        if (! ($laboratoryCategory instanceof LaboratoryCategory)) {
            $laboratoryCategory = LaboratoryCategory::findOrFail($id);
        }

        $laboratoryCategory->delete();

        return response()->noContent();
    }
}
