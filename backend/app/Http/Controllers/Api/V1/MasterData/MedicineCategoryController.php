<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreMedicineCategoryRequest;
use App\Http\Requests\MasterData\UpdateMedicineCategoryRequest;
use App\Http\Resources\MasterData\MedicineCategoryResource;
use App\Models\Legacy\LegacyMedicineCategory;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MedicineCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', LegacyMedicineCategory::class);

        if (! LegacyAccess::isAvailable('ref_kategori_obat') && ! app()->environment('testing')) {
            return response()->json([
                'message' => 'Koneksi ke database operasional SIMRS (simrs_legacy) gagal atau tabel ref_kategori_obat tidak tersedia.',
            ], 503);
        }

        $perPage = $request->get('per_page', 10);
        $query = LegacyMedicineCategory::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->has('is_active') && $request->is_active !== null && $request->is_active !== '') {
            $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
            $query->where('status', $isActive ? '1' : '0');
        }

        $sortBy = $request->get('sort', $request->get('sortBy', 'name'));
        if ($request->has('direction')) {
            $sortDesc = strtolower($request->get('direction')) === 'desc';
        } else {
            $sortDesc = filter_var($request->get('sortDesc', false), FILTER_VALIDATE_BOOLEAN);
        }

        $columnMap = [
            'id' => 'id',
            'name' => 'nama',
            'description' => 'deskripsi',
            'is_active' => 'status',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
        $sortCol = $columnMap[$sortBy] ?? 'nama';
        $query->orderBy($sortCol, $sortDesc ? 'desc' : 'asc');

        if ($perPage === '-1' || $perPage === 'all') {
            return MedicineCategoryResource::collection($query->get());
        }

        return MedicineCategoryResource::collection($query->paginate((int) $perPage));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMedicineCategoryRequest $request)
    {
        Gate::authorize('create', LegacyMedicineCategory::class);

        if (! LegacyAccess::isAvailable('ref_kategori_obat') && ! app()->environment('testing')) {
            return response()->json([
                'message' => 'Koneksi ke database operasional SIMRS (simrs_legacy) gagal atau tabel ref_kategori_obat tidak tersedia.',
            ], 503);
        }

        $data = $request->validated();
        $user = $request->user();
        $legacyUserId = $user?->getLegacyUserId();

        if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
            return response()->json([
                'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
            ], 403);
        }

        if (LegacyMedicineCategory::where('nama', $data['name'])->exists()) {
            throw ValidationException::withMessages([
                'name' => ['Nama kategori obat sudah terdaftar.'],
            ]);
        }

        return DB::connection('legacy')->transaction(function () use ($data, $legacyUserId) {
            $category = new LegacyMedicineCategory();
            $category->name = $data['name'];
            if (array_key_exists('description', $data)) {
                $category->description = $data['description'];
            }
            if (isset($data['is_active'])) {
                $category->is_active = $data['is_active'];
            }
            if ($legacyUserId) {
                $category->id_user = $legacyUserId;
            }
            $category->save();

            return (new MedicineCategoryResource($category))->response()->setStatusCode(201);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show($medicineCategory)
    {
        $id = $medicineCategory instanceof \Illuminate\Database\Eloquent\Model ? $medicineCategory->id : $medicineCategory;
        $legacy = LegacyMedicineCategory::findOrFail($id);
        Gate::authorize('view', $legacy);

        return new MedicineCategoryResource($legacy);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMedicineCategoryRequest $request, $medicineCategory)
    {
        $id = $medicineCategory instanceof \Illuminate\Database\Eloquent\Model ? $medicineCategory->id : $medicineCategory;
        $legacy = LegacyMedicineCategory::findOrFail($id);
        Gate::authorize('update', $legacy);

        $user = $request->user();
        $legacyUserId = $user?->getLegacyUserId();

        if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
            return response()->json([
                'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
            ], 403);
        }

        $data = $request->validated();

        if ($data['name'] !== $legacy->name) {
            if (LegacyMedicineCategory::where('nama', $data['name'])->where('id', '!=', $id)->exists()) {
                throw ValidationException::withMessages([
                    'name' => ['Nama kategori obat sudah terdaftar.'],
                ]);
            }
        }

        return DB::connection('legacy')->transaction(function () use ($legacy, $data, $legacyUserId) {
            $legacy->name = $data['name'];
            if (array_key_exists('description', $data)) {
                $legacy->description = $data['description'];
            }
            if (isset($data['is_active'])) {
                $legacy->is_active = $data['is_active'];
            }
            if ($legacyUserId) {
                $legacy->id_user = $legacyUserId;
            }
            $legacy->save();

            return new MedicineCategoryResource($legacy);
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($medicineCategory)
    {
        $id = $medicineCategory instanceof \Illuminate\Database\Eloquent\Model ? $medicineCategory->id : $medicineCategory;
        $legacy = LegacyMedicineCategory::findOrFail($id);
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
                'message' => 'Kategori obat tidak dapat dihapus karena masih digunakan pada data obat.',
            ], 409);
        }

        DB::connection('legacy')->transaction(function () use ($legacy, $legacyUserId) {
            $legacy->performSoftDelete($legacyUserId);
        });

        return response()->noContent();
    }

    /**
     * Update the active status of the specified resource.
     */
    public function updateStatus(Request $request, $medicineCategory)
    {
        $id = $medicineCategory instanceof \Illuminate\Database\Eloquent\Model ? $medicineCategory->id : $medicineCategory;
        $legacy = LegacyMedicineCategory::findOrFail($id);
        Gate::authorize('update', $legacy);

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

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

        return new MedicineCategoryResource($legacy);
    }
}
