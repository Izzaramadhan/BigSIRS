<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreVillageRequest;
use App\Http\Requests\MasterData\UpdateVillageRequest;
use App\Http\Resources\MasterData\VillageResource;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\Village;
use Illuminate\Http\Request;

class VillageController extends Controller
{
    public function index(Request $request)
    {
        $query = Village::with(['district.regency'])->orderBy('name', 'asc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('district_id')) {
            $query->where('district_id', $request->district_id);
        }

        if ($request->filled('regency_id')) {
            $regencyId = $request->regency_id;
            $query->whereHas('district', function ($q) use ($regencyId) {
                $q->where('regency_id', $regencyId);
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = $request->input('per_page', 10);
        if ($perPage > 100) {
            $perPage = 100;
        }

        return VillageResource::collection($query->paginate($perPage));
    }

    public function store(StoreVillageRequest $request)
    {
        // Check for duplicate name in the same district
        if (Village::where('district_id', $request->district_id)->where('name', $request->name)->exists()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'name' => ['Nama kelurahan sudah ada di kecamatan ini.'],
                ],
            ], 422);
        }

        $village = Village::create($request->validated());
        $village->load(['district.regency']);

        return new VillageResource($village);
    }

    public function show(Village $village)
    {
        $village->load(['district.regency']);

        return new VillageResource($village);
    }

    public function update(UpdateVillageRequest $request, Village $village)
    {
        // Check for duplicate name in the same district, excluding self
        if (Village::where('district_id', $request->district_id)
            ->where('name', $request->name)
            ->where('id', '!=', $village->id)
            ->exists()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'name' => ['Nama kelurahan sudah ada di kecamatan ini.'],
                ],
            ], 422);
        }

        $village->update($request->validated());
        $village->load(['district.regency']);

        return new VillageResource($village);
    }

    public function destroy(Village $village)
    {
        // Audit usage before deletion
        if (class_exists(Employee::class) && Employee::where('village_id', $village->id)->exists()) {
            return response()->json([
                'message' => 'Tidak dapat menghapus kelurahan karena digunakan oleh data pegawai.',
            ], 409);
        }

        if (class_exists(Patient::class) && Patient::where('village_id', $village->id)->exists()) {
            return response()->json([
                'message' => 'Tidak dapat menghapus kelurahan karena digunakan oleh data pasien.',
            ], 409);
        }

        $village->delete();

        return response()->noContent();
    }
}
