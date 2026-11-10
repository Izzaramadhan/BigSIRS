<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MasterData\Icd10CodeResource;
use App\Models\Legacy\LegacyIcd10;
use App\Models\MasterData\Icd10Code;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class Icd10CodeController extends Controller
{
    public function index(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_diagnosa')) {
            $query = LegacyIcd10::query();

            if ($search = $request->get('search')) {
                $query->search($search);
            }

            if ($request->has('is_active')) {
                $isActive = $request->boolean('is_active');
                $query->where('status', $isActive ? '1' : '0');
            }

            $sortField = $request->get('sort_by', $request->get('sort', 'code'));
            $sortDir = $request->get('sort_dir', $request->get('direction', 'asc'));
            $mappedSort = match ($sortField) {
                'name' => 'nama',
                'english_name' => 'nama_en',
                'code' => 'code',
                default => 'id',
            };
            $query->orderBy($mappedSort, strtolower($sortDir) === 'desc' ? 'desc' : 'asc');

            $perPage = (int) $request->get('per_page', 10);
            return Icd10CodeResource::collection($query->paginate($perPage));
        }

        $query = Icd10Code::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('english_name', 'like', "%{$search}%")
                    ->orWhere('inacbg_code', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $sortField = $request->get('sort_by', $request->get('sort', 'code'));
        $sortDir = $request->get('sort_dir', $request->get('direction', 'asc'));
        $allowedSorts = ['id', 'code', 'name', 'english_name', 'inacbg_code', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, strtolower($sortDir) === 'desc' ? 'desc' : 'asc');
        }

        $perPage = (int) $request->get('per_page', 10);
        $data = $query->paginate($perPage);

        return Icd10CodeResource::collection($data);
    }

    public function store(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_diagnosa')) {
            $validated = $request->validate([
                'code' => ['required', 'string', 'max:50'],
                'name' => 'required|string|max:255',
                'english_name' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'is_medical_history' => 'boolean',
                'is_active' => 'boolean',
                'inacbg_code' => 'nullable|string|max:100',
                'inacbg_name' => 'nullable|string',
                'class_1_tariff' => 'nullable|numeric|min:0',
                'class_2_tariff' => 'nullable|numeric|min:0',
                'class_3_tariff' => 'nullable|numeric|min:0',
            ]);

            $code = trim(strtoupper($validated['code']));

            $exists = LegacyIcd10::where('code', $code)->exists();
            if ($exists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'code' => ['Kode diagnosis sudah digunakan.'],
                    ],
                ], 422);
            }

            $legacyUserId = $request->user()?->getLegacyUserId();
            $codeRoot = explode('.', $code)[0] ?? $code;

            $icd10 = DB::connection('legacy')->transaction(function () use ($validated, $code, $codeRoot, $legacyUserId) {
                return LegacyIcd10::create([
                    'code' => $code,
                    'code_root' => $codeRoot,
                    'nama' => $validated['name'],
                    'nama_en' => $validated['english_name'] ?? null,
                    'deskripsi' => $validated['description'] ?? null,
                    'riwayat_penyakit' => ! empty($validated['is_medical_history']) ? '1' : '0',
                    'status' => array_key_exists('is_active', $validated) ? ($validated['is_active'] ? '1' : '0') : '1',
                    'kode_inacbg' => $validated['inacbg_code'] ?? null,
                    'deskripsi_inacbg' => $validated['inacbg_name'] ?? null,
                    'tarif_kelas1' => $validated['class_1_tariff'] ?? null,
                    'tarif_kelas2' => $validated['class_2_tariff'] ?? null,
                    'tarif_kelas3' => $validated['class_3_tariff'] ?? null,
                    'id_user' => $legacyUserId,
                ]);
            });

            return (new Icd10CodeResource($icd10))->response()->setStatusCode(201);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:icd10_codes,code'],
            'name' => 'required|string|max:255',
            'english_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_medical_history' => 'boolean',
            'is_active' => 'boolean',
            'inacbg_code' => 'nullable|string|max:100',
            'inacbg_name' => 'nullable|string',
            'class_1_tariff' => 'nullable|numeric|min:0',
            'class_2_tariff' => 'nullable|numeric|min:0',
            'class_3_tariff' => 'nullable|numeric|min:0',
        ]);

        $validated['code'] = trim(strtoupper($validated['code']));

        $icd10 = Icd10Code::create($validated);

        return new Icd10CodeResource($icd10);
    }

    public function show(string $id)
    {
        if (LegacyAccess::isAvailable('ref_diagnosa')) {
            $legacy = LegacyIcd10::findOrFail($id);
            return new Icd10CodeResource($legacy);
        }

        $icd10 = Icd10Code::findOrFail($id);

        return new Icd10CodeResource($icd10);
    }

    public function update(Request $request, string $id)
    {
        if (LegacyAccess::isAvailable('ref_diagnosa')) {
            $icd10 = LegacyIcd10::findOrFail($id);

            $validated = $request->validate([
                'code' => ['required', 'string', 'max:50'],
                'name' => 'required|string|max:255',
                'english_name' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'is_medical_history' => 'boolean',
                'is_active' => 'boolean',
                'inacbg_code' => 'nullable|string|max:100',
                'inacbg_name' => 'nullable|string',
                'class_1_tariff' => 'nullable|numeric|min:0',
                'class_2_tariff' => 'nullable|numeric|min:0',
                'class_3_tariff' => 'nullable|numeric|min:0',
            ]);

            $code = trim(strtoupper($validated['code']));

            $exists = LegacyIcd10::where('code', $code)->where('id', '!=', $icd10->id)->exists();
            if ($exists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'code' => ['Kode diagnosis sudah digunakan.'],
                    ],
                ], 422);
            }

            $legacyUserId = $request->user()?->getLegacyUserId();
            $codeRoot = explode('.', $code)[0] ?? $code;

            DB::connection('legacy')->transaction(function () use ($icd10, $validated, $code, $codeRoot, $legacyUserId) {
                $updateData = [
                    'code' => $code,
                    'code_root' => $codeRoot,
                    'nama' => $validated['name'],
                    'nama_en' => $validated['english_name'] ?? null,
                    'deskripsi' => $validated['description'] ?? null,
                    'riwayat_penyakit' => ! empty($validated['is_medical_history']) ? '1' : '0',
                ];

                if (array_key_exists('is_active', $validated)) {
                    $updateData['status'] = $validated['is_active'] ? '1' : '0';
                }
                if (array_key_exists('inacbg_code', $validated)) {
                    $updateData['kode_inacbg'] = $validated['inacbg_code'];
                }
                if (array_key_exists('inacbg_name', $validated)) {
                    $updateData['deskripsi_inacbg'] = $validated['inacbg_name'];
                }
                if (array_key_exists('class_1_tariff', $validated)) {
                    $updateData['tarif_kelas1'] = $validated['class_1_tariff'];
                }
                if (array_key_exists('class_2_tariff', $validated)) {
                    $updateData['tarif_kelas2'] = $validated['class_2_tariff'];
                }
                if (array_key_exists('class_3_tariff', $validated)) {
                    $updateData['tarif_kelas3'] = $validated['class_3_tariff'];
                }
                if ($legacyUserId) {
                    $updateData['id_user'] = $legacyUserId;
                }

                $icd10->update($updateData);
            });

            return new Icd10CodeResource($icd10->fresh());
        }

        $icd10 = Icd10Code::findOrFail($id);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('icd10_codes')->ignore($icd10->id)],
            'name' => 'required|string|max:255',
            'english_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_medical_history' => 'boolean',
            'is_active' => 'boolean',
            'inacbg_code' => 'nullable|string|max:100',
            'inacbg_name' => 'nullable|string',
            'class_1_tariff' => 'nullable|numeric|min:0',
            'class_2_tariff' => 'nullable|numeric|min:0',
            'class_3_tariff' => 'nullable|numeric|min:0',
        ]);

        $validated['code'] = trim(strtoupper($validated['code']));

        $icd10->update($validated);

        return new Icd10CodeResource($icd10);
    }

    public function destroy(string $id)
    {
        if (LegacyAccess::isAvailable('ref_diagnosa')) {
            $icd10 = LegacyIcd10::findOrFail($id);

            if ($icd10->hasTransactionReferences()) {
                return response()->json([
                    'message' => 'Kode diagnosis tidak dapat dihapus karena telah digunakan dalam rekam medis atau transaksi pasien.',
                ], 422);
            }

            $legacyUserId = request()->user()?->getLegacyUserId();
            $icd10->performSoftDelete($legacyUserId);

            return response()->json(['message' => 'ICD-10 berhasil dihapus']);
        }

        $icd10 = Icd10Code::findOrFail($id);
        $icd10->delete();

        return response()->json(['message' => 'ICD-10 berhasil dihapus']);
    }

    public function updateStatus(Request $request, string $id)
    {
        if (LegacyAccess::isAvailable('ref_diagnosa')) {
            $icd10 = LegacyIcd10::findOrFail($id);
            $validated = $request->validate([
                'is_active' => 'required|boolean',
            ]);

            $legacyUserId = $request->user()?->getLegacyUserId();
            $updateData = ['status' => $validated['is_active'] ? '1' : '0'];
            if ($legacyUserId) {
                $updateData['id_user'] = $legacyUserId;
            }
            $icd10->update($updateData);

            return new Icd10CodeResource($icd10->fresh());
        }

        $icd10 = Icd10Code::findOrFail($id);
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $icd10->update($validated);

        return new Icd10CodeResource($icd10);
    }
}
