<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRadiologyGroupRequest;
use App\Http\Requests\UpdateRadiologyGroupRequest;
use App\Http\Resources\RadiologyGroupResource;
use App\Models\Legacy\LegacyRadiologyGroup;
use App\Models\MasterData\RadiologyGroup;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RadiologyGroupController extends Controller
{
    public function index(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_grup_rad')) {
            $query = LegacyRadiologyGroup::with(['category', 'type', 'activityType', 'itemGroups'])
                ->withCount('itemGroups');

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->filled('category_id')) {
                $query->where('id_kategori_rad', $request->category_id);
            }

            if ($request->filled('type_id')) {
                $query->where('id_tipe_rad', $request->type_id);
            }

            $perPage = (int) ($request->per_page ?: 10);
            return RadiologyGroupResource::collection($query->paginate($perPage));
        }

        $query = RadiologyGroup::with(['category', 'type', 'activityType', 'itemGroups'])
            ->withCount('itemGroups')
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('loinc_code', 'like', "%{$search}%");
            })
            ->when($request->category_id, function ($q, $categoryId) {
                $q->where('radiology_category_id', $categoryId);
            })
            ->when($request->type_id, function ($q, $typeId) {
                $q->where('radiology_type_id', $typeId);
            });

        $sort = $request->sort ?: 'id';
        $direction = $request->direction === 'desc' ? 'desc' : 'asc';

        $groups = $query->orderBy($sort, $direction)
            ->paginate($request->per_page ?: 10);

        return RadiologyGroupResource::collection($groups);
    }

    public function store(StoreRadiologyGroupRequest $request)
    {
        if (LegacyAccess::isAvailable('ref_grup_rad')) {
            $data = $request->validated();
            $group = DB::connection('legacy')->transaction(function () use ($data, $request) {
                $group = LegacyRadiologyGroup::create([
                    'nama' => $data['name'],
                    'id_kategori_rad' => $data['radiology_category_id'],
                    'id_tipe_rad' => $data['radiology_type_id'],
                    'id_ref_jenis_kegiatan' => $data['activity_type_id'],
                    'harga' => (int) $data['price'],
                    'harga_interpretasi' => (int) $data['interpretation_price'],
                    'deskripsi' => $data['description'] ?? null,
                    'status' => array_key_exists('is_active', $data) ? ($data['is_active'] ? 1 : 0) : 1,
                    'loinc_code' => $data['loinc_code'] ?? null,
                    'loinc_url' => $data['loinc_url'] ?? null,
                    'id_user' => auth()->id() ?? 1,
                ]);

                if ($request->has('radiology_item_group_ids')) {
                    $itemGroupIds = (array) $request->input('radiology_item_group_ids');
                    foreach ($itemGroupIds as $igId) {
                        DB::connection('legacy')->table('map_grup_kelompok_rad')->insert([
                            'id_grup_rad' => $group->id,
                            'id_kelompok_rad' => $igId,
                            'status' => 1,
                            'created_at' => now(),
                            'id_user' => auth()->id() ?? 1,
                        ]);
                    }
                }

                return $group;
            });

            return new RadiologyGroupResource($group->load(['category', 'type', 'activityType', 'itemGroups']));
        }

        $group = DB::transaction(function () use ($request) {
            $group = RadiologyGroup::create($request->validated());

            if ($request->has('radiology_item_group_ids')) {
                $group->itemGroups()->sync($request->radiology_item_group_ids);
            }

            return $group->load(['category', 'type', 'activityType', 'itemGroups']);
        });

        return new RadiologyGroupResource($group);
    }

    public function show($radiologyGroup)
    {
        $id = $radiologyGroup instanceof LegacyRadiologyGroup || $radiologyGroup instanceof RadiologyGroup
            ? $radiologyGroup->id
            : $radiologyGroup;

        if (LegacyAccess::isAvailable('ref_grup_rad')) {
            $legacy = LegacyRadiologyGroup::with(['category', 'type', 'activityType', 'itemGroups'])->findOrFail($id);
            return new RadiologyGroupResource($legacy);
        }

        if (! ($radiologyGroup instanceof RadiologyGroup)) {
            $radiologyGroup = RadiologyGroup::findOrFail($id);
        }

        return new RadiologyGroupResource($radiologyGroup->load(['category', 'type', 'activityType', 'itemGroups']));
    }

    public function update(UpdateRadiologyGroupRequest $request, $radiologyGroup)
    {
        $id = $radiologyGroup instanceof LegacyRadiologyGroup || $radiologyGroup instanceof RadiologyGroup
            ? $radiologyGroup->id
            : $radiologyGroup;

        if (LegacyAccess::isAvailable('ref_grup_rad')) {
            $legacy = LegacyRadiologyGroup::findOrFail($id);
            $data = $request->validated();

            DB::connection('legacy')->transaction(function () use ($data, $request, $legacy) {
                $updateData = [
                    'nama' => $data['name'],
                    'id_kategori_rad' => $data['radiology_category_id'],
                    'id_tipe_rad' => $data['radiology_type_id'],
                    'id_ref_jenis_kegiatan' => $data['activity_type_id'],
                    'harga' => (int) $data['price'],
                    'harga_interpretasi' => (int) $data['interpretation_price'],
                    'deskripsi' => $data['description'] ?? null,
                    'loinc_code' => $data['loinc_code'] ?? null,
                    'loinc_url' => $data['loinc_url'] ?? null,
                ];
                if (array_key_exists('is_active', $data)) {
                    $updateData['status'] = $data['is_active'] ? 1 : 0;
                }
                $legacy->update($updateData);

                if ($request->has('radiology_item_group_ids')) {
                    $itemGroupIds = array_map('intval', (array) $request->input('radiology_item_group_ids'));
                    DB::connection('legacy')->table('map_grup_kelompok_rad')
                        ->where('id_grup_rad', $legacy->id)
                        ->whereNotIn('id_kelompok_rad', $itemGroupIds)
                        ->delete();

                    $existing = DB::connection('legacy')->table('map_grup_kelompok_rad')
                        ->where('id_grup_rad', $legacy->id)
                        ->pluck('id_kelompok_rad')
                        ->map(fn ($val) => (int) $val)
                        ->toArray();

                    foreach ($itemGroupIds as $igId) {
                        if (! in_array($igId, $existing, true)) {
                            DB::connection('legacy')->table('map_grup_kelompok_rad')->insert([
                                'id_grup_rad' => $legacy->id,
                                'id_kelompok_rad' => $igId,
                                'status' => 1,
                                'created_at' => now(),
                                'id_user' => auth()->id() ?? 1,
                            ]);
                        }
                    }
                }
            });

            return new RadiologyGroupResource($legacy->load(['category', 'type', 'activityType', 'itemGroups']));
        }

        if (! ($radiologyGroup instanceof RadiologyGroup)) {
            $radiologyGroup = RadiologyGroup::findOrFail($id);
        }

        $group = DB::transaction(function () use ($request, $radiologyGroup) {
            $radiologyGroup->update($request->validated());

            if ($request->has('radiology_item_group_ids')) {
                $radiologyGroup->itemGroups()->sync($request->radiology_item_group_ids);
            }

            return $radiologyGroup->load(['category', 'type', 'activityType', 'itemGroups']);
        });

        return new RadiologyGroupResource($group);
    }

    public function destroy($radiologyGroup)
    {
        $id = $radiologyGroup instanceof LegacyRadiologyGroup || $radiologyGroup instanceof RadiologyGroup
            ? $radiologyGroup->id
            : $radiologyGroup;

        if (LegacyAccess::isAvailable('ref_grup_rad')) {
            $legacy = LegacyRadiologyGroup::findOrFail($id);
            DB::connection('legacy')->table('map_grup_kelompok_rad')
                ->where('id_grup_rad', $legacy->id)
                ->delete();
            $legacy->delete();

            return response()->noContent();
        }

        if (! ($radiologyGroup instanceof RadiologyGroup)) {
            $radiologyGroup = RadiologyGroup::findOrFail($id);
        }

        $radiologyGroup->delete();

        return response()->noContent();
    }

    public function updateStatus(Request $request, $radiologyGroup)
    {
        $request->validate(['is_active' => 'required|boolean']);

        $id = $radiologyGroup instanceof LegacyRadiologyGroup || $radiologyGroup instanceof RadiologyGroup
            ? $radiologyGroup->id
            : $radiologyGroup;

        if (LegacyAccess::isAvailable('ref_grup_rad')) {
            $legacy = LegacyRadiologyGroup::findOrFail($id);
            $legacy->update(['status' => $request->boolean('is_active') ? 1 : 0]);

            return new RadiologyGroupResource($legacy->load(['category', 'type', 'activityType', 'itemGroups']));
        }

        if (! ($radiologyGroup instanceof RadiologyGroup)) {
            $radiologyGroup = RadiologyGroup::findOrFail($id);
        }

        $radiologyGroup->update(['is_active' => $request->is_active]);

        return new RadiologyGroupResource($radiologyGroup);
    }
}
