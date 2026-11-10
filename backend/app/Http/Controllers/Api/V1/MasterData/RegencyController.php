<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreRegencyRequest;
use App\Http\Requests\MasterData\UpdateRegencyRequest;
use App\Http\Resources\MasterData\RegencyResource;
use App\Models\District;
use App\Models\Employee;
use App\Models\Legacy\LegacyRegency;
use App\Models\Regency;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegencyController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 15), 100);

        if (LegacyAccess::isAvailable('ref_kabupaten')) {
            $query = LegacyRegency::query()->with('province');

            if ($search = $request->get('search')) {
                $query->search($search);
            }

            if ($request->has('province_id')) {
                $query->where('id_provinsi', $request->province_id);
            }

            if ($request->has('is_active')) {
                $query->where('status', $request->boolean('is_active') ? 1 : 0);
            }

            $sort = $request->get('sort', 'name');
            $order = strtolower($request->get('order', 'asc')) === 'desc' ? 'desc' : 'asc';
            $dbSort = match ($sort) {
                'name' => 'nama',
                'code' => 'kode_kabupaten',
                'province_id' => 'id_provinsi',
                'is_active' => 'status',
                default => 'nama',
            };

            return RegencyResource::collection($query->orderBy($dbSort, $order)->paginate($perPage));
        }

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

        return RegencyResource::collection($query->paginate($perPage));
    }

    public function store(StoreRegencyRequest $request)
    {
        $data = $request->validated();

        if (LegacyAccess::isAvailable('ref_kabupaten')) {
            $regencyId = $data['code'] ?? null;
            if (! $regencyId) {
                $count = DB::connection('legacy')->table('ref_kabupaten')->where('id_provinsi', $data['province_id'])->count() + 1;
                $regencyId = $data['province_id'] . str_pad((string) $count, 2, '0', STR_PAD_LEFT);
            }

            $regency = DB::connection('legacy')->transaction(function () use ($data, $regencyId) {
                return LegacyRegency::create([
                    'id' => (string) $regencyId,
                    'id_provinsi' => (string) $data['province_id'],
                    'nama' => $data['name'],
                    'kode_kabupaten' => $data['code'] ?? (string) $regencyId,
                    'status' => array_key_exists('is_active', $data) ? ($data['is_active'] ? 1 : 0) : 1,
                ]);
            });

            $regency->load('province');

            return (new RegencyResource($regency))->response()->setStatusCode(201);
        }

        $regency = Regency::create($data);
        $regency->load('province');

        return (new RegencyResource($regency))->response()->setStatusCode(201);
    }

    public function show($regency)
    {
        $id = $regency instanceof Regency || $regency instanceof LegacyRegency ? $regency->id : $regency;
        if (LegacyAccess::isAvailable('ref_kabupaten')) {
            $legacy = LegacyRegency::with('province')->findOrFail($id);
            return new RegencyResource($legacy);
        }

        if (! ($regency instanceof Regency)) {
            $regency = Regency::with('province')->findOrFail($id);
        } else {
            $regency->load('province');
        }

        return new RegencyResource($regency);
    }

    public function update(UpdateRegencyRequest $request, $regency)
    {
        $id = $regency instanceof Regency || $regency instanceof LegacyRegency ? $regency->id : $regency;
        $data = $request->validated();

        if (LegacyAccess::isAvailable('ref_kabupaten')) {
            $legacy = LegacyRegency::findOrFail($id);

            DB::connection('legacy')->transaction(function () use ($legacy, $data) {
                $updateData = ['nama' => $data['name']];
                if (array_key_exists('province_id', $data)) {
                    $updateData['id_provinsi'] = (string) $data['province_id'];
                }
                if (array_key_exists('code', $data)) {
                    $updateData['kode_kabupaten'] = $data['code'];
                }
                if (array_key_exists('is_active', $data)) {
                    $updateData['status'] = $data['is_active'] ? 1 : 0;
                }
                $legacy->update($updateData);
            });

            $legacy->load('province');

            return new RegencyResource($legacy->fresh());
        }

        if (! ($regency instanceof Regency)) {
            $regency = Regency::findOrFail($id);
        }

        $regency->update($data);
        $regency->load('province');

        return new RegencyResource($regency);
    }

    public function destroy($regency)
    {
        $id = $regency instanceof Regency || $regency instanceof LegacyRegency ? $regency->id : $regency;

        if (LegacyAccess::isAvailable('ref_kabupaten')) {
            $legacy = LegacyRegency::findOrFail($id);

            $hasDistricts = false;
            if (DB::connection('legacy')->getSchemaBuilder()->hasTable('ref_kecamatan')) {
                $hasDistricts = DB::connection('legacy')->table('ref_kecamatan')
                    ->where('id_kabupaten', $id)
                    ->exists();
            }

            $hasMpi = false;
            if (DB::connection('legacy')->getSchemaBuilder()->hasTable('master_person_index')) {
                $hasMpi = DB::connection('legacy')->table('master_person_index')
                    ->where('id_kabupaten', $id)
                    ->exists();
            }

            if ($hasDistricts || $hasMpi) {
                return response()->json([
                    'message' => 'Kabupaten masih digunakan dan tidak dapat dihapus.',
                ], 409);
            }

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->delete();
            });

            return response()->noContent();
        }

        if (! ($regency instanceof Regency)) {
            $regency = Regency::findOrFail($id);
        }

        // Audit relation: District, Employee (via regency_id in addresses, if any)
        $hasDistricts = District::where('regency_id', $regency->id)->exists();
        $hasEmployees = Employee::where('regency_id', $regency->id)->exists();

        if ($hasDistricts || $hasEmployees) {
            return response()->json([
                'message' => 'Kabupaten masih digunakan dan tidak dapat dihapus.',
            ], 409);
        }

        $regency->delete();

        return response()->noContent();
    }
}
