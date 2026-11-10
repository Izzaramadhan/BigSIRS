<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTariffTypeRequest;
use App\Http\Requests\UpdateTariffTypeRequest;
use App\Http\Requests\UpdateTariffTypeStatusRequest;
use App\Http\Resources\TariffTypeResource;
use App\Models\Legacy\LegacyTariffType;
use App\Models\Legacy\LegacyTariffTypeComponent;
use App\Models\TariffType;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TariffTypeController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->input('sort_by', 'name');
        $sortDir = $request->input('sort_dir', 'asc');

        if (LegacyAccess::isAvailable('ref_jenis_tarif')) {
            $query = LegacyTariffType::with('components.component');

            if ($request->filled('search')) {
                $query->search($request->input('search'));
            }

            if ($request->filled('is_active')) {
                $isActive = $request->is_active === 'true' || $request->is_active === '1';
                $query->where('status', $isActive ? 1 : 0);
            }

            $mappedSort = match ($sortBy) {
                'name' => 'nama',
                'code' => 'kode',
                default => 'nama',
            };
            $query->orderBy($mappedSort, $sortDir === 'desc' ? 'desc' : 'asc');

            if ($request->boolean('all')) {
                return TariffTypeResource::collection($query->get());
            }

            $perPage = (int) $request->input('per_page', 10);
            return TariffTypeResource::collection($query->paginate($perPage));
        }

        $query = TariffType::with('components.component');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === 'true' || $request->is_active === '1');
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedSorts = ['id', 'name', 'code', 'created_at', 'updated_at', 'is_active'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        if ($request->boolean('all')) {
            $tariffTypes = $query->get();
            return TariffTypeResource::collection($tariffTypes);
        }

        $perPage = $request->input('per_page', 10);
        $tariffTypes = $query->paginate($perPage);

        return TariffTypeResource::collection($tariffTypes);
    }

    public function store(StoreTariffTypeRequest $request)
    {
        if (LegacyAccess::isAvailable('ref_jenis_tarif')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId() ?? 1;
            $data = $request->validated();

            $tariffType = DB::connection('legacy')->transaction(function () use ($data, $legacyUserId, $request) {
                $type = LegacyTariffType::create([
                    'nama' => $data['name'],
                    'kode' => $data['code'],
                    'deskripsi' => $data['description'] ?? '',
                    'status' => isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1,
                    'id_user' => $legacyUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (LegacyAccess::isAvailable('map_jenis_tarif_komponen') && ! empty($request->input('components'))) {
                    foreach ($request->input('components') as $comp) {
                        LegacyTariffTypeComponent::create([
                            'id_jenis_tarif' => $type->id,
                            'id_komponen' => $comp['tariff_component_id'],
                            'persen' => $comp['percentage'],
                            'status' => '1',
                            'id_user' => $legacyUserId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                return $type->load('components.component');
            });

            return (new TariffTypeResource($tariffType))->response()->setStatusCode(201);
        }

        DB::beginTransaction();
        try {
            $tariffType = TariffType::create($request->validated());

            $componentsData = $request->input('components', []);
            foreach ($componentsData as $comp) {
                $tariffType->components()->create([
                    'tariff_component_id' => $comp['tariff_component_id'],
                    'percentage' => $comp['percentage'],
                ]);
            }

            $totalPercentage = collect($componentsData)->sum('percentage');
            if ($totalPercentage != 100) {
                $tariffType->update(['needs_review' => true]);
            }

            DB::commit();

            return (new TariffTypeResource($tariffType->load('components.component')))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Failed to create Tariff Type: '.$e->getMessage()], 500);
        }
    }

    public function show($tariffType)
    {
        $id = $tariffType instanceof TariffType ? $tariffType->id : $tariffType;
        if (LegacyAccess::isAvailable('ref_jenis_tarif')) {
            $legacy = LegacyTariffType::with('components.component')->findOrFail($id);
            return new TariffTypeResource($legacy);
        }

        if (! ($tariffType instanceof TariffType)) {
            $tariffType = TariffType::findOrFail($id);
        }

        return new TariffTypeResource($tariffType->load('components.component'));
    }

    public function update(UpdateTariffTypeRequest $request, $tariffType)
    {
        $id = $tariffType instanceof TariffType ? $tariffType->id : $tariffType;

        if (LegacyAccess::isAvailable('ref_jenis_tarif')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId() ?? 1;

            $updated = DB::connection('legacy')->transaction(function () use ($request, $id, $legacyUserId) {
                $legacy = LegacyTariffType::findOrFail($id);
                $data = $request->validated();

                $legacy->update([
                    'nama' => $data['name'] ?? $legacy->nama,
                    'kode' => $data['code'] ?? $legacy->kode,
                    'deskripsi' => array_key_exists('description', $data) ? ($data['description'] ?? '') : $legacy->deskripsi,
                    'status' => isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : $legacy->status,
                    'updated_at' => now(),
                ]);

                if (LegacyAccess::isAvailable('map_jenis_tarif_komponen') && $request->has('components')) {
                    $componentsData = collect($request->input('components', []));
                    $submittedPivotIds = $componentsData->pluck('id')->filter()->toArray();

                    LegacyTariffTypeComponent::where('id_jenis_tarif', $legacy->id)
                        ->whereNotIn('id', $submittedPivotIds)
                        ->delete();

                    foreach ($componentsData as $comp) {
                        if (! empty($comp['id'])) {
                            LegacyTariffTypeComponent::where('id', $comp['id'])->update([
                                'id_komponen' => $comp['tariff_component_id'],
                                'persen' => $comp['percentage'],
                                'updated_at' => now(),
                            ]);
                        } else {
                            LegacyTariffTypeComponent::create([
                                'id_jenis_tarif' => $legacy->id,
                                'id_komponen' => $comp['tariff_component_id'],
                                'persen' => $comp['percentage'],
                                'status' => '1',
                                'id_user' => $legacyUserId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                return $legacy->load('components.component');
            });

            return new TariffTypeResource($updated);
        }

        if (! ($tariffType instanceof TariffType)) {
            $tariffType = TariffType::findOrFail($id);
        }

        DB::beginTransaction();
        try {
            $tariffType->update($request->validated());

            $componentsData = collect($request->input('components', []));

            $submittedPivotIds = $componentsData->pluck('id')->filter()->toArray();
            $tariffType->components()->whereNotIn('id', $submittedPivotIds)->delete();

            foreach ($componentsData as $comp) {
                if (! empty($comp['id'])) {
                    $tariffType->components()->where('id', $comp['id'])->update([
                        'tariff_component_id' => $comp['tariff_component_id'],
                        'percentage' => $comp['percentage'],
                    ]);
                } else {
                    $tariffType->components()->create([
                        'tariff_component_id' => $comp['tariff_component_id'],
                        'percentage' => $comp['percentage'],
                    ]);
                }
            }

            $totalPercentage = $tariffType->components()->sum('percentage');
            if ($totalPercentage != 100) {
                $tariffType->update(['needs_review' => true]);
            } else {
                if ($tariffType->code !== null) {
                    $duplicates = $tariffType->components()
                        ->select('tariff_component_id')
                        ->groupBy('tariff_component_id')
                        ->havingRaw('COUNT(*) > 1')
                        ->count();

                    if ($duplicates == 0) {
                        $tariffType->update(['needs_review' => false]);
                    }
                }
            }

            DB::commit();

            return new TariffTypeResource($tariffType->load('components.component'));
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Failed to update Tariff Type: '.$e->getMessage()], 500);
        }
    }

    public function updateStatus(UpdateTariffTypeStatusRequest $request, $tariffType)
    {
        $id = $tariffType instanceof TariffType ? $tariffType->id : $tariffType;

        if (LegacyAccess::isAvailable('ref_jenis_tarif')) {
            $legacy = LegacyTariffType::findOrFail($id);
            $legacy->update([
                'status' => $request->boolean('is_active') ? 1 : 0,
                'updated_at' => now(),
            ]);
            return new TariffTypeResource($legacy->load('components.component'));
        }

        if (! ($tariffType instanceof TariffType)) {
            $tariffType = TariffType::findOrFail($id);
        }

        $tariffType->update($request->validated());

        return new TariffTypeResource($tariffType);
    }

    public function destroy($tariffType)
    {
        $id = $tariffType instanceof TariffType ? $tariffType->id : $tariffType;

        if (LegacyAccess::isAvailable('ref_jenis_tarif')) {
            $legacy = LegacyTariffType::findOrFail($id);

            if (LegacyAccess::isAvailable('ref_tarif_tindakan')) {
                $isUsed = DB::connection('legacy')->table('ref_tarif_tindakan')
                    ->where('id_jenis_tarif', $legacy->id)
                    ->exists();

                if ($isUsed) {
                    return response()->json(['message' => 'Data sedang digunakan dan tidak dapat dihapus.'], 409);
                }
            }

            DB::connection('legacy')->transaction(function () use ($legacy) {
                if (LegacyAccess::isAvailable('map_jenis_tarif_komponen')) {
                    LegacyTariffTypeComponent::where('id_jenis_tarif', $legacy->id)->delete();
                }
                $legacy->delete();
            });

            return response()->noContent();
        }

        if (! ($tariffType instanceof TariffType)) {
            $tariffType = TariffType::findOrFail($id);
        }

        if ($tariffType->legacy_id) {
            try {
                $isUsed = DB::connection('legacy')->table('ref_tarif_tindakan')
                    ->where('id_jenis_tarif', $tariffType->legacy_id)
                    ->exists();

                if ($isUsed) {
                    return response()->json(['message' => 'Data sedang digunakan dan tidak dapat dihapus.'], 409);
                }
            } catch (\Exception $e) {
                // Ignore if connection fails
            }
        }

        $tariffType->delete();

        return response()->noContent();
    }
}
