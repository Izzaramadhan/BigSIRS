<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreDistrictRequest;
use App\Http\Requests\MasterData\UpdateDistrictRequest;
use App\Http\Resources\MasterData\DistrictResource;
use App\Models\District;
use App\Models\Employee;
use App\Models\Village;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    public function index(Request $request)
    {
        $query = District::with('regency')->orderBy('name', 'asc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('regency_id')) {
            $query->where('regency_id', $request->regency_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = $request->input('per_page', 10);
        if ($perPage > 100) {
            $perPage = 100;
        }

        return DistrictResource::collection($query->paginate($perPage));
    }

    public function store(StoreDistrictRequest $request)
    {
        $district = District::create($request->validated());
        $district->load('regency');

        return new DistrictResource($district);
    }

    public function show(District $district)
    {
        $district->load('regency');

        return new DistrictResource($district);
    }

    public function update(UpdateDistrictRequest $request, District $district)
    {
        $district->update($request->validated());
        $district->load('regency');

        return new DistrictResource($district);
    }

    public function updateStatus(Request $request, District $district)
    {
        $request->validate(['is_active' => 'required|boolean']);

        $district->update(['is_active' => $request->is_active]);
        $district->load('regency');

        return new DistrictResource($district);
    }

    public function destroy(District $district)
    {
        // Audit usage before deletion
        // Check if District is used by villages (Villages not implemented yet, but good practice to check if exists)
        if (class_exists(Village::class) && Village::where('district_id', $district->id)->exists()) {
            return response()->json([
                'message' => 'Tidak dapat menghapus kecamatan karena masih memiliki kelurahan.',
            ], 409);
        }

        if (class_exists(Employee::class) && Employee::where('district_id', $district->id)->exists()) {
            return response()->json([
                'message' => 'Tidak dapat menghapus kecamatan karena digunakan oleh data pegawai.',
            ], 409);
        }

        $district->delete();

        return response()->noContent();
    }
}
