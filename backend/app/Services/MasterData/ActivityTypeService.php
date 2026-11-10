<?php

namespace App\Services\MasterData;

use App\Models\ActivityType;
use App\Models\Legacy\LegacyActivityType;
use App\Support\LegacyAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivityTypeService
{
    /**
     * Get paginated activity types with filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        if (LegacyAccess::isAvailable('ref_jenis_kegiatan')) {
            $query = LegacyActivityType::query()->with('parent');

            if (! empty($filters['search'])) {
                $query->search($filters['search']);
            }

            if (isset($filters['parent_id'])) {
                $query->where('parent_id', $filters['parent_id']);
            }

            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $isActive = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? 1 : 0);
            }

            return $query->orderBy('nama', 'asc')->paginate($perPage);
        }

        $query = ActivityType::query()->with('parent:id,name');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhereHas('parent', function ($p) use ($search) {
                        $p->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        return $query->orderBy('name', 'asc')->paginate($perPage);
    }

    /**
     * Create a new activity type.
     */
    public function create(array $data)
    {
        if (LegacyAccess::isAvailable('ref_jenis_kegiatan')) {
            return DB::connection('legacy')->transaction(function () use ($data) {
                return LegacyActivityType::create([
                    'nama' => $data['name'],
                    'parent_id' => $data['parent_id'] ?? null,
                    'status' => array_key_exists('is_active', $data) ? ($data['is_active'] ? 1 : 0) : 1,
                ]);
            });
        }

        $data['is_active'] = $data['is_active'] ?? true;

        return ActivityType::create($data);
    }

    /**
     * Update an existing activity type.
     */
    public function update($activityType, array $data)
    {
        if (LegacyAccess::isAvailable('ref_jenis_kegiatan')) {
            $id = $activityType instanceof LegacyActivityType || $activityType instanceof ActivityType ? $activityType->id : $activityType;
            $legacy = LegacyActivityType::findOrFail($id);

            if (isset($data['parent_id']) && $data['parent_id']) {
                if ($this->isDescendantLegacy($legacy->id, $data['parent_id'])) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'Jenis kegiatan ini tidak boleh menjadi turunan dari bawahannya sendiri.',
                    ]);
                }
            }

            return DB::connection('legacy')->transaction(function () use ($legacy, $data) {
                $updateData = ['nama' => $data['name']];
                if (array_key_exists('parent_id', $data)) {
                    $updateData['parent_id'] = $data['parent_id'];
                }
                if (array_key_exists('is_active', $data)) {
                    $updateData['status'] = $data['is_active'] ? 1 : 0;
                }
                $legacy->update($updateData);
                return $legacy->fresh();
            });
        }

        if (! ($activityType instanceof ActivityType)) {
            $activityType = ActivityType::findOrFail($activityType);
        }

        if (isset($data['is_active'])) {
            $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($data['parent_id']) && $data['parent_id']) {
            if ($this->isDescendant($activityType->id, $data['parent_id'])) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Jenis kegiatan ini tidak boleh menjadi turunan dari bawahannya sendiri.',
                ]);
            }
        }

        $activityType->update($data);

        return $activityType->fresh();
    }

    /**
     * Update status of activity type.
     */
    public function updateStatus($activityType, bool $isActive)
    {
        if (LegacyAccess::isAvailable('ref_jenis_kegiatan')) {
            $id = $activityType instanceof LegacyActivityType || $activityType instanceof ActivityType ? $activityType->id : $activityType;
            $legacy = LegacyActivityType::findOrFail($id);

            return DB::connection('legacy')->transaction(function () use ($legacy, $isActive) {
                $legacy->update(['status' => $isActive ? 1 : 0]);
                return $legacy->fresh();
            });
        }

        if (! ($activityType instanceof ActivityType)) {
            $activityType = ActivityType::findOrFail($activityType);
        }

        $activityType->update(['is_active' => $isActive]);

        return $activityType;
    }

    /**
     * Soft delete activity type.
     */
    public function delete($activityType): void
    {
        if (LegacyAccess::isAvailable('ref_jenis_kegiatan')) {
            $id = $activityType instanceof LegacyActivityType || $activityType instanceof ActivityType ? $activityType->id : $activityType;
            $legacy = LegacyActivityType::findOrFail($id);

            if ($legacy->children()->exists()) {
                throw ValidationException::withMessages([
                    'id' => 'Jenis kegiatan ini masih digunakan sebagai induk. Pindahkan atau hapus data turunannya terlebih dahulu.',
                ]);
            }

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->delete();
            });
            return;
        }

        if (! ($activityType instanceof ActivityType)) {
            $activityType = ActivityType::findOrFail($activityType);
        }

        if ($activityType->children()->exists()) {
            throw ValidationException::withMessages([
                'id' => 'Jenis kegiatan ini masih digunakan sebagai induk. Pindahkan atau hapus data turunannya terlebih dahulu.',
            ]);
        }
        $activityType->delete();
    }

    private function isDescendant($parentId, $childId): bool
    {
        $child = ActivityType::find($childId);
        if (! $child) {
            return false;
        }

        $currentParent = $child->parent_id;
        while ($currentParent) {
            if ($currentParent == $parentId) {
                return true;
            }
            $parent = ActivityType::find($currentParent);
            if (! $parent) {
                break;
            }
            $currentParent = $parent->parent_id;
        }

        return false;
    }

    private function isDescendantLegacy($parentId, $childId): bool
    {
        $child = LegacyActivityType::find($childId);
        if (! $child) {
            return false;
        }

        $currentParent = $child->parent_id;
        while ($currentParent) {
            if ($currentParent == $parentId) {
                return true;
            }
            $parent = LegacyActivityType::find($currentParent);
            if (! $parent) {
                break;
            }
            $currentParent = $parent->parent_id;
        }

        return false;
    }
}
