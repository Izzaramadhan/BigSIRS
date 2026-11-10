<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreDistrictRequest;
use App\Http\Requests\MasterData\UpdateDistrictRequest;
use App\Http\Resources\MasterData\DistrictResource;
use App\Models\District;
use App\Models\Employee;
use App\Models\Legacy\LegacyDistrict;
use App\Models\Village;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistrictController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 10), 100);

        if (LegacyAccess::isAvailable('ref_kecamatan')) {
            $query = LegacyDistrict::with('regency')->orderBy('nama', 'asc');

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->filled('regency_id')) {
                $query->where('id_kabupaten', $request->regency_id);
            }

            if ($request->filled('is_active')) {
                $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? 1 : 0);
            }

            return DistrictResource::collection($query->paginate($perPage));
        }

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

        return DistrictResource::collection($query->paginate($perPage));
    }

    public function store(StoreDistrictRequest $request)
    {
        $data = $request->validated();

        if (LegacyAccess::isAvailable('ref_kecamatan')) {
            $districtId = $data['code'] ?? null;
            if (! $districtId) {
                $count = DB::connection('legacy')->table('ref_kecamatan')->where('id_kabupaten', $data['regency_id'])->count() + 1;
                $districtId = $data['regency_id'] . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
            }

            $district = DB::connection('legacy')->transaction(function () use ($data, $districtId) {
                return LegacyDistrict::create([
                    'id' => (string) $districtId,
                    'id_kabupaten' => (string) $data['regency_id'],
                    'nama' => $data['name'],
                    'kode_kecamatan' => $data['code'] ?? (string) $districtId,
                    'status' => array_key_exists('is_active', $data) ? ($data['is_active'] ? 1 : 0) : 1,
                ]);
            });

            $district->load('regency');

            return (new DistrictResource($district))->response()->setStatusCode(201);
        }

        $district = District::create($data);
        $district->load('regency');

        return (new DistrictResource($district))->response()->setStatusCode(201);
    }

    public function show($district)
    {
        $id = $district instanceof District || $district instanceof LegacyDistrict ? $district->id : $district;
        if (LegacyAccess::isAvailable('ref_kecamatan')) {
            $legacy = LegacyDistrict::with('regency')->findOrFail($id);
            return new DistrictResource($legacy);
        }

        if (! ($district instanceof District)) {
            $district = District::with('regency')->findOrFail($id);
        } else {
            $district->load('regency');
        }

        return new DistrictResource($district);
    }

    public function update(UpdateDistrictRequest $request, $district)
    {
        $id = $district instanceof District || $district instanceof LegacyDistrict ? $district->id : $district;
        $data = $request->validated();

        if (LegacyAccess::isAvailable('ref_kecamatan')) {
            $legacy = LegacyDistrict::findOrFail($id);

            DB::connection('legacy')->transaction(function () use ($legacy, $data) {
                $updateData = ['nama' => $data['name']];
                if (array_key_exists('regency_id', $data)) {
                    $updateData['id_kabupaten'] = (string) $data['regency_id'];
                }
                if (array_key_exists('code', $data)) {
                    $updateData['kode_kecamatan'] = $data['code'];
                }
                if (array_key_exists('is_active', $data)) {
                    $updateData['status'] = $data['is_active'] ? 1 : 0;
                }
                $legacy->update($updateData);
            });

            $legacy->load('regency');

            return new DistrictResource($legacy->fresh());
        }

        if (! ($district instanceof District)) {
            $district = District::findOrFail($id);
        }

        $district->update($data);
        $district->load('regency');

        return new DistrictResource($district);
    }

    public function destroy($district)
    {
        $id = $district instanceof District || $district instanceof LegacyDistrict ? $district->id : $district;

        if (LegacyAccess::isAvailable('ref_kecamatan')) {
            $legacy = LegacyDistrict::findOrFail($id);

            $hasVillages = false;
            if (DB::connection('legacy')->getSchemaBuilder()->hasTable('ref_kelurahan')) {
                $hasVillages = DB::connection('legacy')->table('ref_kelurahan')
                    ->where('id_kecamatan', $id)
                    ->where('status', 1)
                    ->exists();
            }

            $hasMpi = false;
            if (DB::connection('legacy')->getSchemaBuilder()->hasTable('master_person_index')) {
                $hasMpi = DB::connection('legacy')->table('master_person_index')
                    ->where('id_kecamatan', $id)
                    ->exists();
            }

            if ($hasVillages || $hasMpi) {
                return response()->json([
                    'message' => 'Tidak dapat menghapus kecamatan karena masih memiliki kelurahan atau digunakan oleh data pasien/pegawai.',
                ], 409);
            }

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->delete();
            });

            return response()->noContent();
        }

        if (! ($district instanceof District)) {
            $district = District::findOrFail($id);
        }

        // Audit usage before deletion
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
