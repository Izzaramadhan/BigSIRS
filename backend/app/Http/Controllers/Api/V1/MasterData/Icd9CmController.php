<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MasterData\StoreIcd9cmCodeRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateIcd9cmCodeRequest;
use App\Http\Resources\Api\V1\MasterData\Icd9CmResource;
use App\Models\Legacy\LegacyIcd9;
use App\Models\MasterData\Icd9Cm;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Icd9CmController extends Controller
{
    protected function isLegacy(): bool
    {
        return LegacyAccess::isAvailable('ref_icd_9') || LegacyAccess::isAvailable('ref_icd9');
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        if ($this->isLegacy()) {
            $query = LegacyIcd9::query();

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->has('is_active') && $request->is_active !== '') {
                $isActive = $request->boolean('is_active');
                $query->where('status', $isActive ? 1 : 0);
            }

            $sortField = $request->input('sort_by', $request->input('sort', 'code'));
            $sortDir = $request->input('sort_dir', $request->input('direction', 'asc'));
            $mappedSort = match ($sortField) {
                'name' => 'nama',
                'english_name' => 'nama',
                'code' => 'kode',
                default => 'id',
            };
            $query->orderBy($mappedSort, strtolower($sortDir) === 'asc' ? 'asc' : 'desc');

            return Icd9CmResource::collection($query->paginate($perPage));
        }

        $query = Icd9Cm::query();

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $sortField = $request->input('sort_by', $request->input('sort', 'created_at'));
        $sortDir = $request->input('sort_dir', $request->input('direction', 'desc'));

        $allowedSorts = ['code', 'name', 'english_name', 'inacbg_code', 'created_at', 'updated_at', 'is_active'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, strtolower($sortDir) === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = $request->input('per_page', 10);
        $codes = $query->paginate($perPage);

        return Icd9CmResource::collection($codes);
    }

    public function store(StoreIcd9cmCodeRequest $request)
    {
        if ($this->isLegacy()) {
            $validated = $request->validated();
            $code = trim(strtoupper($validated['code']));

            $exists = LegacyIcd9::where('kode', $code)->exists();
            if ($exists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'code' => ['Kode prosedur ICD-9 sudah digunakan.'],
                    ],
                ], 422);
            }

            $legacyUserId = $request->user()?->getLegacyUserId() ?? 0;

            $icd9 = DB::connection('legacy')->transaction(function () use ($validated, $code, $legacyUserId) {
                return LegacyIcd9::create([
                    'kode' => $code,
                    'nama' => $validated['name'],
                    'status' => array_key_exists('is_active', $validated) ? ($validated['is_active'] ? 1 : 0) : 1,
                    'kode_inacbg' => $validated['inacbg_code'] ?? null,
                    'deskripsi_inacbg' => $validated['inacbg_name'] ?? ($validated['description'] ?? null),
                    'tarif_kelas1' => $validated['class_1_tariff'] ?? null,
                    'tarif_kelas2' => $validated['class_2_tariff'] ?? null,
                    'tarif_kelas3' => $validated['class_3_tariff'] ?? null,
                    'id_user' => $legacyUserId,
                ]);
            });

            return (new Icd9CmResource($icd9))->response()->setStatusCode(201);
        }

        $code = Icd9Cm::create($request->validated());

        return new Icd9CmResource($code);
    }

    public function show($icd9Cm)
    {
        $id = $icd9Cm instanceof LegacyIcd9 || $icd9Cm instanceof Icd9Cm ? $icd9Cm->id : $icd9Cm;
        if ($this->isLegacy()) {
            $legacy = LegacyIcd9::findOrFail($id);
            return new Icd9CmResource($legacy);
        }

        if (! ($icd9Cm instanceof Icd9Cm)) {
            $icd9Cm = Icd9Cm::findOrFail($id);
        }

        return new Icd9CmResource($icd9Cm);
    }

    public function update(UpdateIcd9cmCodeRequest $request, $icd9Cm)
    {
        if ($this->isLegacy()) {
            $id = $icd9Cm instanceof LegacyIcd9 || $icd9Cm instanceof Icd9Cm ? $icd9Cm->id : $icd9Cm;
            $legacy = LegacyIcd9::findOrFail($id);
            $validated = $request->validated();
            $code = trim(strtoupper($validated['code']));

            $exists = LegacyIcd9::where('kode', $code)->where('id', '!=', $legacy->id)->exists();
            if ($exists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'code' => ['Kode prosedur ICD-9 sudah digunakan.'],
                    ],
                ], 422);
            }

            $legacyUserId = $request->user()?->getLegacyUserId();

            DB::connection('legacy')->transaction(function () use ($legacy, $validated, $code, $legacyUserId) {
                $updateData = [
                    'kode' => $code,
                    'nama' => $validated['name'],
                ];

                if (array_key_exists('is_active', $validated)) {
                    $updateData['status'] = $validated['is_active'] ? 1 : 0;
                }
                if (array_key_exists('inacbg_code', $validated)) {
                    $updateData['kode_inacbg'] = $validated['inacbg_code'];
                }
                if (array_key_exists('inacbg_name', $validated)) {
                    $updateData['deskripsi_inacbg'] = $validated['inacbg_name'];
                } elseif (array_key_exists('description', $validated)) {
                    $updateData['deskripsi_inacbg'] = $validated['description'];
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
                if ($legacyUserId !== null) {
                    $updateData['id_user'] = $legacyUserId;
                }

                $legacy->update($updateData);
            });

            return new Icd9CmResource($legacy->fresh());
        }

        if (! ($icd9Cm instanceof Icd9Cm)) {
            $icd9Cm = Icd9Cm::findOrFail($icd9Cm);
        }

        $icd9Cm->update($request->validated());

        return new Icd9CmResource($icd9Cm);
    }

    public function destroy($icd9Cm)
    {
        if ($this->isLegacy()) {
            $id = $icd9Cm instanceof LegacyIcd9 || $icd9Cm instanceof Icd9Cm ? $icd9Cm->id : $icd9Cm;
            $legacy = LegacyIcd9::findOrFail($id);

            if ($legacy->hasTransactionReferences()) {
                return response()->json([
                    'message' => 'Prosedur ICD-9 tidak dapat dihapus karena telah digunakan dalam rekam medis atau transaksi pasien.',
                ], 422);
            }

            $legacyUserId = request()->user()?->getLegacyUserId();
            $legacy->performSoftDelete($legacyUserId);

            return response()->noContent();
        }

        if (! ($icd9Cm instanceof Icd9Cm)) {
            $icd9Cm = Icd9Cm::findOrFail($icd9Cm);
        }

        $icd9Cm->delete();

        return response()->noContent();
    }

    public function updateStatus(Request $request, $icd9Cm)
    {
        $request->validate(['is_active' => 'required|boolean']);

        if ($this->isLegacy()) {
            $id = $icd9Cm instanceof LegacyIcd9 || $icd9Cm instanceof Icd9Cm ? $icd9Cm->id : $icd9Cm;
            $legacy = LegacyIcd9::findOrFail($id);

            $legacyUserId = $request->user()?->getLegacyUserId();
            $updateData = ['status' => $request->boolean('is_active') ? 1 : 0];
            if ($legacyUserId !== null) {
                $updateData['id_user'] = $legacyUserId;
            }
            $legacy->update($updateData);

            return new Icd9CmResource($legacy->fresh());
        }

        if (! ($icd9Cm instanceof Icd9Cm)) {
            $icd9Cm = Icd9Cm::findOrFail($icd9Cm);
        }

        $icd9Cm->update(['is_active' => $request->boolean('is_active')]);

        return new Icd9CmResource($icd9Cm);
    }

    public function lookup(Request $request)
    {
        if ($this->isLegacy()) {
            $query = LegacyIcd9::query()->active();

            if ($request->filled('search')) {
                $query->search($request->input('search'));
            }

            if ($request->has('selected_id')) {
                $query->orWhere('id', $request->selected_id);
            }

            $codes = $query->orderBy('nama', 'asc')
                ->limit(50)
                ->get();

            $mapped = $codes->map(function ($item) {
                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'is_active' => $item->is_active,
                ];
            });

            return response()->json([
                'data' => $mapped,
            ]);
        }

        $query = Icd9Cm::query()->active();

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->has('selected_id')) {
            $query->orWhere('id', $request->selected_id);
        }

        $codes = $query->orderBy('name', 'asc')
            ->limit(50)
            ->get(['id', 'code', 'name', 'is_active']);

        return response()->json([
            'data' => $codes,
        ]);
    }
}
