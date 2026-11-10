<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProcedureCategoryRequest;
use App\Http\Requests\UpdateProcedureCategoryRequest;
use App\Http\Requests\UpdateProcedureCategoryStatusRequest;
use App\Http\Resources\ProcedureCategoryResource;
use App\Models\Legacy\LegacyProcedureCategory;
use App\Models\ProcedureCategory;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcedureCategoryController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->query('sort', 'name');
        $direction = strtolower($request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (LegacyAccess::isAvailable('ref_kategori_tindakan')) {
            $query = LegacyProcedureCategory::query();

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->has('is_active') && $request->is_active !== null) {
                $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? 1 : 0);
            }

            $mappedSort = match ($sort) {
                'name' => 'nama',
                'description' => 'deskripsi',
                default => 'nama',
            };

            if ($request->boolean('all')) {
                return ProcedureCategoryResource::collection($query->orderBy($mappedSort, $direction)->get());
            }

            $perPage = min((int) $request->query('per_page', 15), 500);
            return ProcedureCategoryResource::collection($query->orderBy($mappedSort, $direction)->paginate($perPage));
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $sort = $request->query('sort', 'name');
        $direction = strtolower($request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'name', 'is_active', 'created_at', 'updated_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'name';
        }

        $query = ProcedureCategory::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('description', 'like', '%'.$request->search.'%');
        }

        if ($request->has('is_active') && $request->is_active !== null) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->boolean('all')) {
            $categories = $query->orderBy($sort, $direction)->get();
            return ProcedureCategoryResource::collection($categories);
        }

        $perPage = min((int) $request->query('per_page', 15), 500);
        $categories = $query->orderBy($sort, $direction)->paginate($perPage);

        return ProcedureCategoryResource::collection($categories);
    }

    public function store(StoreProcedureCategoryRequest $request)
    {
        if (LegacyAccess::isAvailable('ref_kategori_tindakan')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId() ?? 1;

            return DB::connection('legacy')->transaction(function () use ($request, $legacyUserId) {
                $data = $request->validated();
                $category = LegacyProcedureCategory::create([
                    'nama' => $data['name'],
                    'deskripsi' => $data['description'] ?? '',
                    'status' => isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1,
                    'id_user' => $legacyUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return (new ProcedureCategoryResource($category))->response()->setStatusCode(201);
            });
        }

        return DB::transaction(function () use ($request) {
            $category = ProcedureCategory::create($request->validated());

            return (new ProcedureCategoryResource($category))->response()->setStatusCode(201);
        });
    }

    public function show($procedureCategory)
    {
        $id = $procedureCategory instanceof ProcedureCategory ? $procedureCategory->id : $procedureCategory;
        if (LegacyAccess::isAvailable('ref_kategori_tindakan')) {
            $legacy = LegacyProcedureCategory::findOrFail($id);
            return new ProcedureCategoryResource($legacy);
        }

        if (! ($procedureCategory instanceof ProcedureCategory)) {
            $procedureCategory = ProcedureCategory::findOrFail($id);
        }

        return new ProcedureCategoryResource($procedureCategory);
    }

    public function update(UpdateProcedureCategoryRequest $request, $procedureCategory)
    {
        $id = $procedureCategory instanceof ProcedureCategory ? $procedureCategory->id : $procedureCategory;

        if (LegacyAccess::isAvailable('ref_kategori_tindakan')) {
            return DB::connection('legacy')->transaction(function () use ($request, $id) {
                $legacy = LegacyProcedureCategory::findOrFail($id);
                $data = $request->validated();
                $legacy->update([
                    'nama' => $data['name'] ?? $legacy->nama,
                    'deskripsi' => array_key_exists('description', $data) ? ($data['description'] ?? '') : $legacy->deskripsi,
                    'status' => isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : $legacy->status,
                    'updated_at' => now(),
                ]);

                return new ProcedureCategoryResource($legacy);
            });
        }

        if (! ($procedureCategory instanceof ProcedureCategory)) {
            $procedureCategory = ProcedureCategory::findOrFail($id);
        }

        return DB::transaction(function () use ($request, $procedureCategory) {
            $procedureCategory->update($request->validated());

            return new ProcedureCategoryResource($procedureCategory->fresh());
        });
    }

    public function status(UpdateProcedureCategoryStatusRequest $request, $procedureCategory)
    {
        $id = $procedureCategory instanceof ProcedureCategory ? $procedureCategory->id : $procedureCategory;

        if (LegacyAccess::isAvailable('ref_kategori_tindakan')) {
            return DB::connection('legacy')->transaction(function () use ($request, $id) {
                $legacy = LegacyProcedureCategory::findOrFail($id);
                $legacy->update([
                    'status' => $request->boolean('is_active') ? 1 : 0,
                    'updated_at' => now(),
                ]);

                return new ProcedureCategoryResource($legacy);
            });
        }

        if (! ($procedureCategory instanceof ProcedureCategory)) {
            $procedureCategory = ProcedureCategory::findOrFail($id);
        }

        return DB::transaction(function () use ($request, $procedureCategory) {
            $procedureCategory->update($request->validated());

            return new ProcedureCategoryResource($procedureCategory->fresh());
        });
    }

    public function destroy($procedureCategory)
    {
        $id = $procedureCategory instanceof ProcedureCategory ? $procedureCategory->id : $procedureCategory;

        if (LegacyAccess::isAvailable('ref_kategori_tindakan')) {
            $legacy = LegacyProcedureCategory::findOrFail($id);

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->delete();
            });

            return response()->noContent();
        }

        if (! ($procedureCategory instanceof ProcedureCategory)) {
            $procedureCategory = ProcedureCategory::findOrFail($id);
        }

        DB::transaction(function () use ($procedureCategory) {
            $procedureCategory->delete();
        });

        return response()->noContent();
    }
}
