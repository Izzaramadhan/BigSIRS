<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Enums\ServiceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePolyclinicRequest;
use App\Http\Requests\UpdatePolyclinicRequest;
use App\Http\Requests\UpdatePolyclinicStatusRequest;
use App\Http\Resources\PolyclinicResource;
use App\Models\Legacy\LegacyPolyclinic;
use App\Models\Polyclinic;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PolyclinicController extends Controller
{
    public function index(Request $request)
    {
        $authModel = LegacyAccess::isAvailable('ref_poliklinik') ? LegacyPolyclinic::class : Polyclinic::class;
        Gate::authorize('viewAny', $authModel);

        $perPage = min((int) $request->query('per_page', 15), 100);
        $sort = $request->query('sort', 'name');
        $direction = strtolower($request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'code', 'name', 'service_type', 'quota', 'jkn_quota', 'is_active', 'created_at', 'updated_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'name';
        }

        if (LegacyAccess::isAvailable('ref_poliklinik')) {
            $query = LegacyPolyclinic::with('warehouse');

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->has('is_active') && $request->is_active !== null) {
                $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? '1' : '0');
            }

            if ($request->filled('service_type')) {
                $query->where('jenis', $request->service_type);
            }

            $legacySortMap = [
                'name' => 'nama',
                'code' => 'kode',
                'service_type' => 'jenis',
                'is_active' => 'status',
                'quota' => 'kuota',
                'jkn_quota' => 'kuota_jkn',
            ];
            $dbSort = $legacySortMap[$sort] ?? $sort;

            $polyclinics = $query->orderBy($dbSort, $direction)->paginate($perPage);

            return PolyclinicResource::collection($polyclinics);
        }

        $query = Polyclinic::with('warehouse');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->has('is_active') && $request->is_active !== null) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        $polyclinics = $query->orderBy($sort, $direction)->paginate($perPage);

        return PolyclinicResource::collection($polyclinics);
    }

    public function store(StorePolyclinicRequest $request)
    {
        $authModel = LegacyAccess::isAvailable('ref_poliklinik') ? LegacyPolyclinic::class : Polyclinic::class;
        Gate::authorize('create', $authModel);

        $data = $request->validated();
        unset($data['parent_id']);

        if (LegacyAccess::isAvailable('ref_poliklinik')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], 403);
            }

            return DB::connection('legacy')->transaction(function () use ($data, $legacyUserId) {
                $polyclinic = new LegacyPolyclinic();
                $polyclinic->code = $data['code'];
                $polyclinic->name = $data['name'];
                $polyclinic->service_type = $data['service_type'];
                $polyclinic->description = $data['description'] ?? null;
                $polyclinic->is_visible = $data['is_visible'] ?? true;
                $polyclinic->is_online_visible = $data['is_online_visible'] ?? false;
                $polyclinic->quota = $data['quota'] ?? 0;
                $polyclinic->jkn_quota = $data['jkn_quota'] ?? 0;
                $polyclinic->bpjs_code = $data['bpjs_code'] ?? null;
                $polyclinic->warehouse_id = $data['warehouse_id'] ?? null;
                $polyclinic->is_active = $data['is_active'] ?? true;
                if ($legacyUserId) {
                    $polyclinic->id_user = $legacyUserId;
                }
                $polyclinic->save();

                $polyclinic->load('warehouse');

                return (new PolyclinicResource($polyclinic))->response()->setStatusCode(201);
            });
        }

        return DB::transaction(function () use ($data) {
            $polyclinic = Polyclinic::create($data);
            $polyclinic->load('warehouse');

            return (new PolyclinicResource($polyclinic))->response()->setStatusCode(201);
        });
    }

    public function show($polyclinic)
    {
        $id = $polyclinic instanceof \Illuminate\Database\Eloquent\Model ? $polyclinic->id : $polyclinic;

        if (LegacyAccess::isAvailable('ref_poliklinik')) {
            $legacy = LegacyPolyclinic::with('warehouse')->findOrFail($id);
            Gate::authorize('view', $legacy);

            return new PolyclinicResource($legacy);
        }

        if (! ($polyclinic instanceof Polyclinic)) {
            $polyclinic = Polyclinic::with('warehouse')->findOrFail($id);
        } else {
            $polyclinic->load('warehouse');
        }

        Gate::authorize('view', $polyclinic);

        return new PolyclinicResource($polyclinic);
    }

    public function update(UpdatePolyclinicRequest $request, $polyclinic)
    {
        $id = $polyclinic instanceof \Illuminate\Database\Eloquent\Model ? $polyclinic->id : $polyclinic;

        if (LegacyAccess::isAvailable('ref_poliklinik')) {
            $legacy = LegacyPolyclinic::findOrFail($id);
            Gate::authorize('update', $legacy);

            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], 403);
            }

            $data = $request->validated();
            unset($data['parent_id']);

            return DB::connection('legacy')->transaction(function () use ($legacy, $data, $legacyUserId) {
                $legacy->code = $data['code'];
                $legacy->name = $data['name'];
                $legacy->service_type = $data['service_type'];
                $legacy->description = $data['description'] ?? null;
                if (isset($data['is_visible'])) {
                    $legacy->is_visible = $data['is_visible'];
                }
                if (isset($data['is_online_visible'])) {
                    $legacy->is_online_visible = $data['is_online_visible'];
                }
                if (isset($data['quota'])) {
                    $legacy->quota = $data['quota'];
                }
                if (isset($data['jkn_quota'])) {
                    $legacy->jkn_quota = $data['jkn_quota'];
                }
                if (array_key_exists('bpjs_code', $data)) {
                    $legacy->bpjs_code = $data['bpjs_code'];
                }
                if (array_key_exists('warehouse_id', $data)) {
                    $legacy->warehouse_id = $data['warehouse_id'];
                }
                if (isset($data['is_active'])) {
                    $legacy->is_active = $data['is_active'];
                }
                if ($legacyUserId) {
                    $legacy->id_user = $legacyUserId;
                }
                $legacy->save();

                $legacy->load('warehouse');

                return new PolyclinicResource($legacy);
            });
        }

        if (! ($polyclinic instanceof Polyclinic)) {
            $polyclinic = Polyclinic::findOrFail($id);
        }
        Gate::authorize('update', $polyclinic);

        $data = $request->validated();
        unset($data['parent_id']);

        return DB::transaction(function () use ($data, $polyclinic) {
            $polyclinic->update($data);

            return new PolyclinicResource($polyclinic->fresh('warehouse'));
        });
    }

    public function status(UpdatePolyclinicStatusRequest $request, $polyclinic)
    {
        $id = $polyclinic instanceof \Illuminate\Database\Eloquent\Model ? $polyclinic->id : $polyclinic;

        if (LegacyAccess::isAvailable('ref_poliklinik')) {
            $legacy = LegacyPolyclinic::findOrFail($id);
            Gate::authorize('update', $legacy);

            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], 403);
            }

            return DB::connection('legacy')->transaction(function () use ($legacy, $request, $legacyUserId) {
                $legacy->is_active = $request->validated('is_active');
                if ($legacyUserId) {
                    $legacy->id_user = $legacyUserId;
                }
                $legacy->save();

                $legacy->load('warehouse');

                return new PolyclinicResource($legacy);
            });
        }

        if (! ($polyclinic instanceof Polyclinic)) {
            $polyclinic = Polyclinic::findOrFail($id);
        }
        Gate::authorize('update', $polyclinic);

        return DB::transaction(function () use ($request, $polyclinic) {
            $polyclinic->update($request->validated());

            return new PolyclinicResource($polyclinic->fresh('warehouse'));
        });
    }

    public function destroy($polyclinic)
    {
        $id = $polyclinic instanceof \Illuminate\Database\Eloquent\Model ? $polyclinic->id : $polyclinic;

        if (LegacyAccess::isAvailable('ref_poliklinik')) {
            $legacy = LegacyPolyclinic::findOrFail($id);
            Gate::authorize('delete', $legacy);

            $user = request()->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], 403);
            }

            if ($legacy->hasActiveChildren()) {
                return response()->json([
                    'message' => 'Tidak dapat menghapus poliklinik karena memiliki sub-poliklinik aktif.',
                ], 409);
            }

            if ($legacy->isReferencedInTransactions()) {
                return response()->json([
                    'message' => 'Poliklinik tidak dapat dihapus karena sudah memiliki riwayat kunjungan, jadwal dokter, atau tarif terkait. Gunakan fitur nonaktifkan status jika poliklinik tidak lagi digunakan.',
                ], 409);
            }

            DB::connection('legacy')->transaction(function () use ($legacy, $legacyUserId) {
                $legacy->performSoftDelete($legacyUserId);
            });

            return response()->noContent();
        }

        if (! ($polyclinic instanceof Polyclinic)) {
            $polyclinic = Polyclinic::findOrFail($id);
        }
        Gate::authorize('delete', $polyclinic);

        if ($polyclinic->children()->exists()) {
            return response()->json([
                'message' => 'Tidak dapat menghapus poliklinik karena memiliki sub-poliklinik aktif.',
            ], 409);
        }

        DB::transaction(function () use ($polyclinic) {
            $polyclinic->delete();
        });

        return response()->noContent();
    }

    /**
     * Return all ServiceType enum values for frontend dropdowns.
     */
    public function serviceTypes()
    {
        return response()->json([
            'data' => collect(ServiceType::cases())->map(fn ($e) => [
                'value' => $e->value,
                'label' => $e->label(),
            ]),
        ]);
    }
}
