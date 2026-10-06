<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Regency;
use App\Http\Requests\MasterData\StoreRegencyRequest;
use App\Http\Requests\MasterData\UpdateRegencyRequest;
use App\Http\Resources\MasterData\RegencyResource;
use Illuminate\Http\Request;

class RegencyController extends Controller
{
    public function index(Request $request)
    {
        $query = Regency::query()->with('province');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->has('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $sort = $request->get('sort', 'name');
        $order = $request->get('order', 'asc');
        
        $whitelist = ['code', 'name', 'province_id', 'is_active', 'created_at', 'updated_at'];
        if (in_array($sort, $whitelist) && in_array(strtolower($order), ['asc', 'desc'])) {
            $query->orderBy($sort, $order);
        }

        $perPage = min((int) $request->get('per_page', 15), 100);

        return RegencyResource::collection($query->paginate($perPage));
    }

    public function store(StoreRegencyRequest $request)
    {
        $regency = Regency::create($request->validated());
        $regency->load('province');

        return new RegencyResource($regency);
    }

    public function show(Regency $regency)
    {
        $regency->load('province');
        return new RegencyResource($regency);
    }

    public function update(UpdateRegencyRequest $request, Regency $regency)
    {
        $regency->update($request->validated());
        $regency->load('province');

        return new RegencyResource($regency);
    }

    public function destroy(Regency $regency)
    {
        // Audit relation: District, Employee (via regency_id in addresses, if any)
        $hasDistricts = \App\Models\District::where('regency_id', $regency->id)->exists();
        $hasEmployees = \App\Models\Employee::where('regency_id', $regency->id)->exists();

        if ($hasDistricts || $hasEmployees) {
            return response()->json([
                'message' => 'Kabupaten masih digunakan dan tidak dapat dihapus.'
            ], 409);
        }

        $regency->delete();

        return response()->noContent();
    }

    public function updateStatus(Request $request, Regency $regency)
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $regency->update(['is_active' => $request->is_active]);

        return new RegencyResource($regency);
    }
}
