<?php

namespace App\Services\MasterData;

use App\Models\DietType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class DietTypeService
{
    /**
     * Get paginated diet types with filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = DietType::query();

        if (! empty($filters['search'])) {
            $query->where(function (Builder $q) use ($filters) {
                $q->where('name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('description', 'like', '%'.$filters['search'].'%');
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderBy('name', 'asc')->paginate($perPage);
    }

    /**
     * Create a new diet type.
     */
    public function create(array $data): DietType
    {
        $data['is_active'] = $data['is_active'] ?? true;

        return DietType::create($data);
    }

    /**
     * Update an existing diet type.
     */
    public function update(DietType $dietType, array $data): DietType
    {
        if (isset($data['is_active'])) {
            $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        $dietType->update($data);

        return $dietType->fresh();
    }

    /**
     * Update status of diet type.
     */
    public function updateStatus(DietType $dietType, bool $isActive): DietType
    {
        $dietType->update(['is_active' => $isActive]);

        return $dietType;
    }

    /**
     * Soft delete diet type.
     */
    public function delete(DietType $dietType): void
    {
        // Add check if diet type is used in other tables, for now just delete
        $dietType->delete();
    }
}
