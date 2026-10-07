<?php

namespace App\Services\MasterData;

use App\Models\ActivityType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ActivityTypeService
{
    /**
     * Get paginated diet types with filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
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
     * Create a new diet type.
     */
    public function create(array $data): ActivityType
    {
        $data['is_active'] = $data['is_active'] ?? true;

        return ActivityType::create($data);
    }

    /**
     * Update an existing diet type.
     */
    public function update(ActivityType $activityType, array $data): ActivityType
    {
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
     * Update status of diet type.
     */
    public function updateStatus(ActivityType $activityType, bool $isActive): ActivityType
    {
        $activityType->update(['is_active' => $isActive]);

        return $activityType;
    }

    /**
     * Soft delete diet type.
     */
    public function delete(ActivityType $activityType): void
    {
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
}
