<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Models\ProcedureCategory;
use App\Http\Resources\ProcedureCategoryResource;
use App\Http\Requests\StoreProcedureCategoryRequest;
use App\Http\Requests\UpdateProcedureCategoryRequest;
use App\Http\Requests\UpdateProcedureCategoryStatusRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcedureCategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage   = min((int) $request->query('per_page', 15), 100);
        $sort      = $request->query('sort', 'name');
        $direction = strtolower($request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'name', 'is_active', 'created_at', 'updated_at'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'name';
        }

        $query = ProcedureCategory::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        if ($request->has('is_active') && $request->is_active !== null) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $categories = $query->orderBy($sort, $direction)->paginate($perPage);

        return ProcedureCategoryResource::collection($categories);
    }

    public function store(StoreProcedureCategoryRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $category = ProcedureCategory::create($request->validated());
            return (new ProcedureCategoryResource($category))->response()->setStatusCode(201);
        });
    }

    public function show(ProcedureCategory $procedureCategory)
    {
        return new ProcedureCategoryResource($procedureCategory);
    }

    public function update(UpdateProcedureCategoryRequest $request, ProcedureCategory $procedureCategory)
    {
        return DB::transaction(function () use ($request, $procedureCategory) {
            $procedureCategory->update($request->validated());
            return new ProcedureCategoryResource($procedureCategory->fresh());
        });
    }

    public function status(UpdateProcedureCategoryStatusRequest $request, ProcedureCategory $procedureCategory)
    {
        return DB::transaction(function () use ($request, $procedureCategory) {
            $procedureCategory->update($request->validated());
            return new ProcedureCategoryResource($procedureCategory->fresh());
        });
    }

    public function destroy(ProcedureCategory $procedureCategory)
    {
        // TODO: Placeholder check against future procedures model.
        // if ($procedureCategory->procedures()->exists()) {
        //     return response()->json([
        //         'message' => 'Tidak dapat menghapus kategori tindakan karena masih digunakan oleh data tindakan.'
        //     ], 409);
        // }

        DB::transaction(function () use ($procedureCategory) {
            $procedureCategory->delete();
        });

        return response()->noContent();
    }
}
