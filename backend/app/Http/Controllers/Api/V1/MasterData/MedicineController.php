<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreMedicineRequest;
use App\Http\Requests\MasterData\UpdateMedicineRequest;
use App\Http\Resources\MasterData\MedicineResource;
use App\Models\Legacy\LegacyMedicine;
use App\Models\MasterData\Medicine;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MedicineController extends Controller
{
    public function index(Request $request)
    {
        $authModel = LegacyAccess::isAvailable('ref_obat') ? LegacyMedicine::class : Medicine::class;
        Gate::authorize('viewAny', $authModel);

        $perPage = $request->input('per_page', 10);

        if (LegacyAccess::isAvailable('ref_obat')) {
            $query = LegacyMedicine::with(['unit', 'category', 'route']);

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->filled('category_id') || $request->filled('medicine_category_id')) {
                $catId = $request->input('category_id', $request->input('medicine_category_id'));
                $query->where('id_kategori_obat', $catId);
            }

            if ($request->filled('unit_id') || $request->filled('medicine_unit_id')) {
                $unitId = $request->input('unit_id', $request->input('medicine_unit_id'));
                $query->where('id_satuan_obat', $unitId);
            }

            if ($request->filled('classification_id') || $request->filled('medicine_classification_id')) {
                $classId = $request->input('classification_id', $request->input('medicine_classification_id'));
                $query->where('id_golongan', $classId);
            }

            if ($request->has('is_active') && $request->is_active !== null && $request->is_active !== '') {
                $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? '1' : '0');
            }

            $sortBy = $request->input('sort', $request->input('sortBy', 'name'));
            if ($request->has('direction')) {
                $sortDesc = strtolower($request->input('direction')) === 'desc';
            } else {
                $sortDesc = filter_var($request->input('sortDesc', false), FILTER_VALIDATE_BOOLEAN);
            }

            $columnMap = [
                'id' => 'id',
                'name' => 'nama',
                'code' => 'kode',
                'kfa_code' => 'kode_kfa',
                'selling_price_per_unit' => 'harga_jual',
                'price' => 'harga_jual',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
            ];
            $sortCol = $columnMap[$sortBy] ?? 'nama';
            $query->orderBy($sortCol, $sortDesc ? 'desc' : 'asc');

            if ($perPage === '-1' || $perPage === 'all') {
                return MedicineResource::collection($query->get());
            }

            return MedicineResource::collection($query->paginate((int) $perPage));
        }

        $query = Medicine::with(['unit', 'category', 'classification', 'route', 'generic']);

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('kfa_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('medicine_category_id', $request->category_id);
        }

        if ($request->filled('classification_id')) {
            $query->where('medicine_classification_id', $request->classification_id);
        }

        if ($request->has('is_active') && $request->is_active !== null && $request->is_active !== '') {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort', $request->input('sortBy', 'name'));
        if ($request->has('direction')) {
            $sortDesc = strtolower($request->input('direction')) === 'desc';
        } else {
            $sortDesc = filter_var($request->input('sortDesc', false), FILTER_VALIDATE_BOOLEAN);
        }

        $allowedSorts = ['id', 'code', 'name', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        if ($perPage === '-1' || $perPage === 'all') {
            return MedicineResource::collection($query->get());
        }

        return MedicineResource::collection($query->paginate($perPage));
    }

    public function store(StoreMedicineRequest $request)
    {
        $authModel = LegacyAccess::isAvailable('ref_obat') ? LegacyMedicine::class : Medicine::class;
        Gate::authorize('create', $authModel);

        $data = $request->validated();

        if (LegacyAccess::isAvailable('ref_obat')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], 403);
            }

            if (! empty($data['code']) && LegacyMedicine::where('kode', $data['code'])->exists()) {
                throw ValidationException::withMessages([
                    'code' => ['Kode obat sudah terdaftar.'],
                ]);
            }

            return DB::connection('legacy')->transaction(function () use ($data, $legacyUserId) {
                $medicine = new LegacyMedicine();
                $medicine->name = $data['name'];
                if (! empty($data['code'])) {
                    $medicine->code = $data['code'];
                }
                if (array_key_exists('description', $data)) {
                    $medicine->description = $data['description'];
                }
                if (array_key_exists('function', $data)) {
                    $medicine->function = $data['function'];
                }
                if (array_key_exists('kfa_code', $data)) {
                    $medicine->kfa_code = $data['kfa_code'];
                }
                if (array_key_exists('bpjs_code', $data)) {
                    $medicine->bpjs_code = $data['bpjs_code'];
                }
                if (array_key_exists('medicine_unit_id', $data)) {
                    $medicine->medicine_unit_id = $data['medicine_unit_id'];
                }
                if (array_key_exists('medicine_category_id', $data)) {
                    $medicine->medicine_category_id = $data['medicine_category_id'];
                }
                if (array_key_exists('medicine_classification_id', $data)) {
                    $medicine->medicine_classification_id = $data['medicine_classification_id'];
                }
                if (array_key_exists('medicine_route_id', $data)) {
                    $medicine->medicine_route_id = $data['medicine_route_id'];
                }
                if (array_key_exists('generic_medicine_id', $data)) {
                    $medicine->generic_medicine_id = $data['generic_medicine_id'];
                }
                if (array_key_exists('selling_price_per_unit', $data)) {
                    $medicine->selling_price_per_unit = $data['selling_price_per_unit'];
                } elseif (array_key_exists('price', $data)) {
                    $medicine->selling_price_per_unit = $data['price'];
                }
                if (array_key_exists('purchase_price', $data)) {
                    $medicine->purchase_price = $data['purchase_price'];
                }
                if (isset($data['is_active'])) {
                    $medicine->is_active = $data['is_active'];
                } else {
                    $medicine->is_active = true;
                }
                if ($legacyUserId) {
                    $medicine->id_user = $legacyUserId;
                }
                $medicine->save();
                $medicine->load(['unit', 'category', 'route']);

                return (new MedicineResource($medicine))->response()->setStatusCode(201);
            });
        }

        if (LegacyAccess::isEnabled() && ! app()->environment('testing')) {
            return response()->json([
                'message' => 'Koneksi ke database operasional SIMRS (simrs_legacy) gagal atau tabel ref_obat tidak tersedia.',
            ], 503);
        }

        if (! isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        $medicine = Medicine::create($data);
        $medicine->load(['unit', 'category', 'classification', 'route', 'generic']);

        return (new MedicineResource($medicine))->response()->setStatusCode(201);
    }

    public function show($medicine)
    {
        $id = $medicine instanceof \Illuminate\Database\Eloquent\Model ? $medicine->id : $medicine;

        if (LegacyAccess::isAvailable('ref_obat')) {
            $legacy = LegacyMedicine::with(['unit', 'category', 'route'])->findOrFail($id);
            Gate::authorize('view', $legacy);

            return new MedicineResource($legacy);
        }

        if (LegacyAccess::isEnabled() && ! app()->environment('testing')) {
            return response()->json([
                'message' => 'Koneksi ke database operasional SIMRS (simrs_legacy) gagal atau tabel ref_obat tidak tersedia.',
            ], 503);
        }

        if (! ($medicine instanceof Medicine)) {
            $medicine = Medicine::findOrFail($id);
        }

        Gate::authorize('view', $medicine);

        $medicine->load(['unit', 'category', 'classification', 'route', 'generic']);

        return new MedicineResource($medicine);
    }

    public function update(UpdateMedicineRequest $request, $medicine)
    {
        $id = $medicine instanceof \Illuminate\Database\Eloquent\Model ? $medicine->id : $medicine;

        if (LegacyAccess::isAvailable('ref_obat')) {
            $legacy = LegacyMedicine::findOrFail($id);
            Gate::authorize('update', $legacy);

            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], 403);
            }

            $data = $request->validated();

            if (! empty($data['code']) && $data['code'] !== $legacy->code) {
                if (LegacyMedicine::where('kode', $data['code'])->where('id', '!=', $id)->exists()) {
                    throw ValidationException::withMessages([
                        'code' => ['Kode obat sudah terdaftar.'],
                    ]);
                }
            }

            return DB::connection('legacy')->transaction(function () use ($legacy, $data, $legacyUserId) {
                $legacy->name = $data['name'];
                if (array_key_exists('code', $data)) {
                    $legacy->code = $data['code'];
                }
                if (array_key_exists('description', $data)) {
                    $legacy->description = $data['description'];
                }
                if (array_key_exists('function', $data)) {
                    $legacy->function = $data['function'];
                }
                if (array_key_exists('kfa_code', $data)) {
                    $legacy->kfa_code = $data['kfa_code'];
                }
                if (array_key_exists('bpjs_code', $data)) {
                    $legacy->bpjs_code = $data['bpjs_code'];
                }
                if (array_key_exists('medicine_unit_id', $data)) {
                    $legacy->medicine_unit_id = $data['medicine_unit_id'];
                }
                if (array_key_exists('medicine_category_id', $data)) {
                    $legacy->medicine_category_id = $data['medicine_category_id'];
                }
                if (array_key_exists('medicine_classification_id', $data)) {
                    $legacy->medicine_classification_id = $data['medicine_classification_id'];
                }
                if (array_key_exists('medicine_route_id', $data)) {
                    $legacy->medicine_route_id = $data['medicine_route_id'];
                }
                if (array_key_exists('generic_medicine_id', $data)) {
                    $legacy->generic_medicine_id = $data['generic_medicine_id'];
                }
                if (array_key_exists('selling_price_per_unit', $data)) {
                    $legacy->selling_price_per_unit = $data['selling_price_per_unit'];
                } elseif (array_key_exists('price', $data)) {
                    $legacy->selling_price_per_unit = $data['price'];
                }
                if (array_key_exists('purchase_price', $data)) {
                    $legacy->purchase_price = $data['purchase_price'];
                }
                if (isset($data['is_active'])) {
                    $legacy->is_active = $data['is_active'];
                }
                if ($legacyUserId) {
                    $legacy->id_user = $legacyUserId;
                }
                $legacy->save();
                $legacy->load(['unit', 'category', 'route']);

                return new MedicineResource($legacy);
            });
        }

        if (LegacyAccess::isEnabled() && ! app()->environment('testing')) {
            return response()->json([
                'message' => 'Koneksi ke database operasional SIMRS (simrs_legacy) gagal atau tabel ref_obat tidak tersedia.',
            ], 503);
        }

        if (! ($medicine instanceof Medicine)) {
            $medicine = Medicine::findOrFail($id);
        }

        Gate::authorize('update', $medicine);

        $medicine->update($request->validated());
        $medicine->load(['unit', 'category', 'classification', 'route', 'generic']);

        return new MedicineResource($medicine);
    }

    public function destroy($medicine)
    {
        $id = $medicine instanceof \Illuminate\Database\Eloquent\Model ? $medicine->id : $medicine;

        if (LegacyAccess::isAvailable('ref_obat')) {
            $legacy = LegacyMedicine::findOrFail($id);
            Gate::authorize('delete', $legacy);

            $user = request()->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], 403);
            }

            if ($legacy->isReferencedInTransactions()) {
                return response()->json([
                    'message' => 'Data obat tidak dapat dihapus karena masih digunakan pada transaksi atau data terkait.',
                ], 409);
            }

            DB::connection('legacy')->transaction(function () use ($legacy, $legacyUserId) {
                $legacy->performSoftDelete($legacyUserId);
            });

            return response()->noContent();
        }

        if (LegacyAccess::isEnabled() && ! app()->environment('testing')) {
            return response()->json([
                'message' => 'Koneksi ke database operasional SIMRS (simrs_legacy) gagal atau tabel ref_obat tidak tersedia.',
            ], 503);
        }

        if (! ($medicine instanceof Medicine)) {
            $medicine = Medicine::findOrFail($id);
        }

        Gate::authorize('delete', $medicine);

        $medicine->delete();

        return response()->noContent();
    }

    public function updateStatus(Request $request, $medicine)
    {
        $id = $medicine instanceof \Illuminate\Database\Eloquent\Model ? $medicine->id : $medicine;

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        if (LegacyAccess::isAvailable('ref_obat')) {
            $legacy = LegacyMedicine::findOrFail($id);
            Gate::authorize('update', $legacy);

            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], 403);
            }

            DB::connection('legacy')->transaction(function () use ($legacy, $validated, $legacyUserId) {
                $legacy->is_active = $validated['is_active'];
                if ($legacyUserId) {
                    $legacy->id_user = $legacyUserId;
                }
                $legacy->save();
            });

            $legacy->load(['unit', 'category', 'route']);

            return new MedicineResource($legacy);
        }

        if (LegacyAccess::isEnabled() && ! app()->environment('testing')) {
            return response()->json([
                'message' => 'Koneksi ke database operasional SIMRS (simrs_legacy) gagal atau tabel ref_obat tidak tersedia.',
            ], 503);
        }

        if (! ($medicine instanceof Medicine)) {
            $medicine = Medicine::findOrFail($id);
        }

        Gate::authorize('update', $medicine);

        $medicine->update($validated);

        return new MedicineResource($medicine);
    }
}
