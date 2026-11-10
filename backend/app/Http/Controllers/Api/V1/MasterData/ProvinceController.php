<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreProvinceRequest;
use App\Http\Requests\MasterData\UpdateProvinceRequest;
use App\Http\Resources\MasterData\ProvinceResource;
use App\Models\Legacy\LegacyProvince;
use App\Models\Province;
use App\Models\Regency;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProvinceController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 15), 100);

        if (LegacyAccess::isAvailable('ref_provinsi')) {
            $query = LegacyProvince::query();

            if ($search = $request->get('search')) {
                $query->search($search);
            }

            if ($request->has('is_active')) {
                $query->where('status', $request->boolean('is_active') ? 1 : 0);
            }

            $sort = $request->get('sort', 'name');
            $order = strtolower($request->get('order', 'asc')) === 'desc' ? 'desc' : 'asc';
            $dbSort = match ($sort) {
                'name' => 'nama',
                'code' => 'kode_provinsi',
                'is_active' => 'status',
                default => 'nama',
            };

            return ProvinceResource::collection($query->orderBy($dbSort, $order)->paginate($perPage));
        }

        $query = Province::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $sort = $request->get('sort', 'name');
        $order = strtolower($request->get('order', 'asc')) === 'desc' ? 'desc' : 'asc';

        return ProvinceResource::collection($query->orderBy($sort, $order)->paginate($perPage));
    }

    public function store(StoreProvinceRequest $request)
    {
        $data = $request->validated();

        if (LegacyAccess::isAvailable('ref_provinsi')) {
            $province = DB::connection('legacy')->transaction(function () use ($data) {
                return LegacyProvince::create([
                    'nama' => $data['name'],
                    'kode_provinsi' => $data['code'] ?? null,
                    'status' => array_key_exists('is_active', $data) ? ($data['is_active'] ? 1 : 0) : 1,
                ]);
            });

            return (new ProvinceResource($province))->response()->setStatusCode(201);
        }

        $province = Province::create($data);

        return (new ProvinceResource($province))->response()->setStatusCode(201);
    }

    public function show($province)
    {
        $id = $province instanceof Province || $province instanceof LegacyProvince ? $province->id : $province;
        if (LegacyAccess::isAvailable('ref_provinsi')) {
            $legacy = LegacyProvince::findOrFail($id);
            return new ProvinceResource($legacy);
        }

        if (! ($province instanceof Province)) {
            $province = Province::findOrFail($id);
        }

        return new ProvinceResource($province);
    }

    public function update(UpdateProvinceRequest $request, $province)
    {
        $id = $province instanceof Province || $province instanceof LegacyProvince ? $province->id : $province;
        $data = $request->validated();

        if (LegacyAccess::isAvailable('ref_provinsi')) {
            $legacy = LegacyProvince::findOrFail($id);

            DB::connection('legacy')->transaction(function () use ($legacy, $data) {
                $updateData = ['nama' => $data['name']];
                if (array_key_exists('code', $data)) {
                    $updateData['kode_provinsi'] = $data['code'];
                }
                if (array_key_exists('is_active', $data)) {
                    $updateData['status'] = $data['is_active'] ? 1 : 0;
                }
                $legacy->update($updateData);
            });

            return new ProvinceResource($legacy->fresh());
        }

        if (! ($province instanceof Province)) {
            $province = Province::findOrFail($id);
        }

        $province->update($data);

        return new ProvinceResource($province);
    }

    public function destroy($province)
    {
        $id = $province instanceof Province || $province instanceof LegacyProvince ? $province->id : $province;

        if (LegacyAccess::isAvailable('ref_provinsi')) {
            $legacy = LegacyProvince::findOrFail($id);

            $hasRegencies = false;
            if (DB::connection('legacy')->getSchemaBuilder()->hasTable('ref_kabupaten')) {
                $hasRegencies = DB::connection('legacy')->table('ref_kabupaten')
                    ->where('id_provinsi', $id)
                    ->exists();
            }

            if ($hasRegencies) {
                return response()->json([
                    'message' => 'Provinsi masih digunakan dan tidak dapat dihapus.',
                ], 409);
            }

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->delete();
            });

            return response()->noContent();
        }

        if (! ($province instanceof Province)) {
            $province = Province::findOrFail($id);
        }

        $hasRegencies = Regency::where('province_id', $province->id)->exists();
        if ($hasRegencies) {
            return response()->json([
                'message' => 'Provinsi masih digunakan dan tidak dapat dihapus.',
            ], 409);
        }

        $province->delete();

        return response()->noContent();
    }
}

