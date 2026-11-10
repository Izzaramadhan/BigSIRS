<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreVillageRequest;
use App\Http\Requests\MasterData\UpdateVillageRequest;
use App\Http\Resources\MasterData\VillageResource;
use App\Models\Employee;
use App\Models\Legacy\LegacyVillage;
use App\Models\Patient;
use App\Models\Village;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VillageController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 10), 100);

        if (LegacyAccess::isAvailable('ref_kelurahan')) {
            $query = LegacyVillage::with(['district.regency'])->orderBy('nama', 'asc');

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->filled('district_id')) {
                $query->where('id_kecamatan', $request->district_id);
            }

            if ($request->filled('regency_id')) {
                $regencyId = $request->regency_id;
                $query->whereHas('district', function ($q) use ($regencyId) {
                    $q->where('id_kabupaten', $regencyId);
                });
            }

            if ($request->filled('is_active')) {
                $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? 1 : 0);
            }

            return VillageResource::collection($query->paginate($perPage));
        }

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

        return VillageResource::collection($query->paginate($perPage));
    }

    public function store(StoreVillageRequest $request)
    {
        $data = $request->validated();

        if (LegacyAccess::isAvailable('ref_kelurahan')) {
            $exists = LegacyVillage::where('id_kecamatan', $data['district_id'])
                ->where('nama', $data['name'])
                ->where('status', 1)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'name' => ['Nama kelurahan sudah ada di kecamatan ini.'],
                    ],
                ], 422);
            }

            $village = DB::connection('legacy')->transaction(function () use ($data) {
                return LegacyVillage::create([
                    'id_kecamatan' => (string) $data['district_id'],
                    'nama' => $data['name'],
                    'kode_kelurahan' => $data['code'] ?? null,
                    'status' => array_key_exists('is_active', $data) ? ($data['is_active'] ? 1 : 0) : 1,
                ]);
            });

            $village->load(['district.regency']);

            return (new VillageResource($village))->response()->setStatusCode(201);
        }

        // Check for duplicate name in the same district
        if (Village::where('district_id', $request->district_id)->where('name', $request->name)->exists()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'name' => ['Nama kelurahan sudah ada di kecamatan ini.'],
                ],
            ], 422);
        }

        $village = Village::create($data);
        $village->load(['district.regency']);

        return (new VillageResource($village))->response()->setStatusCode(201);
    }

    public function show($village)
    {
        $id = $village instanceof Village || $village instanceof LegacyVillage ? $village->id : $village;
        if (LegacyAccess::isAvailable('ref_kelurahan')) {
            $legacy = LegacyVillage::with(['district.regency'])->findOrFail($id);
            return new VillageResource($legacy);
        }

        if (! ($village instanceof Village)) {
            $village = Village::with(['district.regency'])->findOrFail($id);
        } else {
            $village->load(['district.regency']);
        }

        return new VillageResource($village);
    }

    public function update(UpdateVillageRequest $request, $village)
    {
        $id = $village instanceof Village || $village instanceof LegacyVillage ? $village->id : $village;
        $data = $request->validated();

        if (LegacyAccess::isAvailable('ref_kelurahan')) {
            $legacy = LegacyVillage::findOrFail($id);

            $exists = LegacyVillage::where('id_kecamatan', $data['district_id'])
                ->where('nama', $data['name'])
                ->where('id', '!=', $id)
                ->where('status', 1)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'name' => ['Nama kelurahan sudah ada di kecamatan ini.'],
                    ],
                ], 422);
            }

            DB::connection('legacy')->transaction(function () use ($legacy, $data) {
                $updateData = ['nama' => $data['name']];
                if (array_key_exists('district_id', $data)) {
                    $updateData['id_kecamatan'] = (string) $data['district_id'];
                }
                if (array_key_exists('code', $data)) {
                    $updateData['kode_kelurahan'] = $data['code'];
                }
                if (array_key_exists('is_active', $data)) {
                    $updateData['status'] = $data['is_active'] ? 1 : 0;
                }
                $legacy->update($updateData);
            });

            $legacy->load(['district.regency']);

            return new VillageResource($legacy->fresh());
        }

        if (! ($village instanceof Village)) {
            $village = Village::findOrFail($id);
        }

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

        $village->update($data);
        $village->load(['district.regency']);

        return new VillageResource($village);
    }

    public function destroy($village)
    {
        $id = $village instanceof Village || $village instanceof LegacyVillage ? $village->id : $village;

        if (LegacyAccess::isAvailable('ref_kelurahan')) {
            $legacy = LegacyVillage::findOrFail($id);

            $hasMpi = false;
            if (DB::connection('legacy')->getSchemaBuilder()->hasTable('master_person_index')) {
                $hasMpi = DB::connection('legacy')->table('master_person_index')
                    ->where('id_kelurahan', $id)
                    ->exists();
            }

            if ($hasMpi) {
                return response()->json([
                    'message' => 'Tidak dapat menghapus kelurahan karena digunakan oleh data pasien/pegawai.',
                ], 409);
            }

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->delete();
            });

            return response()->noContent();
        }

        if (! ($village instanceof Village)) {
            $village = Village::findOrFail($id);
        }

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
