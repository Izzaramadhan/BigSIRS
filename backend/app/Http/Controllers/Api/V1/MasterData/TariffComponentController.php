<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTariffComponentRequest;
use App\Http\Requests\UpdateTariffComponentRequest;
use App\Http\Requests\UpdateTariffComponentStatusRequest;
use App\Http\Resources\TariffComponentResource;
use App\Models\TariffComponent;
use Illuminate\Http\Request;

class TariffComponentController extends Controller
{
    public function index(Request $request)
    {
        $query = TariffComponent::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $sortField = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_dir', 'desc');

        $allowedSorts = ['name', 'is_active', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        $perPage = $request->input('per_page', 10);
        $components = $query->paginate($perPage);

        return TariffComponentResource::collection($components);
    }

    public function store(StoreTariffComponentRequest $request)
    {
        $component = TariffComponent::create($request->validated());
        return new TariffComponentResource($component);
    }

    public function show(TariffComponent $tariffComponent)
    {
        return new TariffComponentResource($tariffComponent);
    }

    public function update(UpdateTariffComponentRequest $request, TariffComponent $tariffComponent)
    {
        $tariffComponent->update($request->validated());
        return new TariffComponentResource($tariffComponent);
    }

    public function updateStatus(UpdateTariffComponentStatusRequest $request, TariffComponent $tariffComponent)
    {
        $tariffComponent->update($request->validated());
        return new TariffComponentResource($tariffComponent);
    }

    public function destroy(TariffComponent $tariffComponent)
    {
        // TODO: Check if still referenced by existing tariffs, if yes return 409
        $tariffComponent->delete();
        return response()->noContent();
    }
}
