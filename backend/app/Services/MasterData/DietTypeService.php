<?php

namespace App\Services\MasterData;

use App\Models\DietType;
use App\Models\Legacy\LegacyDietType;
use App\Support\LegacyAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DietTypeService
{
    /**
     * Get paginated diet types with filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        if (LegacyAccess::isAvailable('ref_jenis_diet')) {
            $query = LegacyDietType::query();

            if (! empty($filters['search'])) {
                $query->search($filters['search']);
            }

            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $isActive = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? '1' : '0');
            }

            return $query->orderBy('nama', 'asc')->paginate($perPage);
        }

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
    public function create(array $data)
    {
        if (LegacyAccess::isAvailable('ref_jenis_diet')) {
            return DB::connection('legacy')->transaction(function () use ($data) {
                return LegacyDietType::create([
                    'nama' => $data['name'],
                    'deskripsi' => $data['description'] ?? null,
                    'status' => array_key_exists('is_active', $data) ? ($data['is_active'] ? '1' : '0') : '1',
                ]);
            });
        }

        $data['is_active'] = $data['is_active'] ?? true;

        return DietType::create($data);
    }

    /**
     * Update an existing diet type.
     */
    public function update($dietType, array $data)
    {
        if (LegacyAccess::isAvailable('ref_jenis_diet')) {
            $id = $dietType instanceof LegacyDietType || $dietType instanceof DietType ? $dietType->id : $dietType;
            $legacy = LegacyDietType::findOrFail($id);

            return DB::connection('legacy')->transaction(function () use ($legacy, $data) {
                $updateData = ['nama' => $data['name']];
                if (array_key_exists('description', $data)) {
                    $updateData['deskripsi'] = $data['description'];
                }
                if (array_key_exists('is_active', $data)) {
                    $updateData['status'] = $data['is_active'] ? '1' : '0';
                }
                $legacy->update($updateData);
                return $legacy->fresh();
            });
        }

        if (! ($dietType instanceof DietType)) {
            $dietType = DietType::findOrFail($dietType);
        }

        if (isset($data['is_active'])) {
            $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        $dietType->update($data);

        return $dietType->fresh();
    }

    /**
     * Update status of diet type.
     */
    public function updateStatus($dietType, bool $isActive)
    {
        if (LegacyAccess::isAvailable('ref_jenis_diet')) {
            $id = $dietType instanceof LegacyDietType || $dietType instanceof DietType ? $dietType->id : $dietType;
            $legacy = LegacyDietType::findOrFail($id);

            return DB::connection('legacy')->transaction(function () use ($legacy, $isActive) {
                $legacy->update(['status' => $isActive ? '1' : '0']);
                return $legacy->fresh();
            });
        }

        if (! ($dietType instanceof DietType)) {
            $dietType = DietType::findOrFail($dietType);
        }

        $dietType->update(['is_active' => $isActive]);

        return $dietType->fresh();
    }

    /**
     * Soft delete diet type.
     */
    public function delete($dietType): void
    {
        if (LegacyAccess::isAvailable('ref_jenis_diet')) {
            $id = $dietType instanceof LegacyDietType || $dietType instanceof DietType ? $dietType->id : $dietType;
            $legacy = LegacyDietType::findOrFail($id);

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->delete();
            });
            return;
        }

        if (! ($dietType instanceof DietType)) {
            $dietType = DietType::findOrFail($dietType);
        }

        $dietType->delete();
    }
}
