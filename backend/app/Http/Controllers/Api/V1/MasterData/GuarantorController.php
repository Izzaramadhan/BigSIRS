<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Guarantor;
use App\Http\Resources\GuarantorResource;
use App\Http\Requests\StoreGuarantorRequest;
use App\Http\Requests\UpdateGuarantorRequest;
use App\Http\Requests\UpdateGuarantorStatusRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuarantorController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 15), 100);
        $sort = $request->query('sort', 'name');
        $direction = strtolower($request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        
        $allowedSorts = ['id', 'code', 'name', 'type', 'created_at', 'updated_at', 'is_active'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'name';
        }

        $query = Guarantor::query();

        if ($request->has('search') && $request->search !== null) {
            $query->search($request->search);
        }

        if ($request->has('type') && $request->type !== null) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active') && $request->is_active !== null) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $guarantors = $query->orderBy($sort, $direction)->paginate($perPage);

        return GuarantorResource::collection($guarantors);
    }

    public function store(StoreGuarantorRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $guarantor = Guarantor::create($request->validated());
            return new GuarantorResource($guarantor);
        });
    }

    public function show(Guarantor $guarantor)
    {
        return new GuarantorResource($guarantor);
    }

    public function update(UpdateGuarantorRequest $request, Guarantor $guarantor)
    {
        return DB::transaction(function () use ($request, $guarantor) {
            $guarantor->update($request->validated());
            return new GuarantorResource($guarantor);
        });
    }

    public function status(UpdateGuarantorStatusRequest $request, Guarantor $guarantor)
    {
        return DB::transaction(function () use ($request, $guarantor) {
            $guarantor->update($request->validated());
            return new GuarantorResource($guarantor);
        });
    }

    public function destroy(Guarantor $guarantor)
    {
        DB::transaction(function () use ($guarantor) {
            $guarantor->delete();
        });

        return response()->noContent();
    }
}
