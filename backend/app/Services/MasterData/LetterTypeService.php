<?php

namespace App\Services\MasterData;

use App\Models\Legacy\LegacyLetterType;
use App\Models\LetterType;
use App\Support\LegacyAccess;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LetterTypeService
{
    public function getPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        if (LegacyAccess::isAvailable('ref_jenis_surat')) {
            $query = LegacyLetterType::query();

            if (isset($filters['search']) && ! empty($filters['search'])) {
                $query->search($filters['search']);
            }

            if (isset($filters['is_active'])) {
                $isActive = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? 1 : 0);
            }

            $sortField = $filters['sort_by'] ?? 'name';
            $sortDirection = $filters['sort_dir'] ?? 'asc';
            $mappedSort = match ($sortField) {
                'name' => 'nama',
                'description' => 'deskripsi',
                default => 'nama',
            };

            return $query->orderBy($mappedSort, $sortDirection === 'desc' ? 'desc' : 'asc')->paginate($perPage);
        }

        $query = LetterType::query();

        if (isset($filters['search']) && ! empty($filters['search'])) {
            $searchTerm = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('description', 'like', $searchTerm)
                    ->orWhere('legacy_resource', 'like', $searchTerm);
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $sortField = $filters['sort_by'] ?? 'name';
        $sortDirection = $filters['sort_dir'] ?? 'asc';

        $allowedSortFields = ['name', 'description', 'legacy_resource', 'is_active'];

        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        return $query->paginate($perPage);
    }

    public function create(array $data)
    {
        if (LegacyAccess::isAvailable('ref_jenis_surat')) {
            return DB::connection('legacy')->transaction(function () use ($data) {
                return LegacyLetterType::create([
                    'nama' => $data['name'],
                    'deskripsi' => $data['description'] ?? null,
                    'resource' => $data['legacy_resource'] ?? null,
                    'status' => array_key_exists('is_active', $data) ? ($data['is_active'] ? '1' : '0') : '1',
                ]);
            });
        }

        return DB::transaction(function () use ($data) {
            return LetterType::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'legacy_resource' => $data['legacy_resource'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    public function update($letterType, array $data)
    {
        if (LegacyAccess::isAvailable('ref_jenis_surat')) {
            $id = $letterType instanceof LegacyLetterType || $letterType instanceof LetterType ? $letterType->id : $letterType;
            $legacy = LegacyLetterType::findOrFail($id);

            return DB::connection('legacy')->transaction(function () use ($legacy, $data) {
                $updateData = ['nama' => $data['name']];
                if (array_key_exists('description', $data)) {
                    $updateData['deskripsi'] = $data['description'];
                }
                if (array_key_exists('legacy_resource', $data)) {
                    $updateData['resource'] = $data['legacy_resource'];
                }
                if (array_key_exists('is_active', $data)) {
                    $updateData['status'] = $data['is_active'] ? '1' : '0';
                }
                $legacy->update($updateData);
                return $legacy->fresh();
            });
        }

        if (! ($letterType instanceof LetterType)) {
            $letterType = LetterType::findOrFail($letterType);
        }

        return DB::transaction(function () use ($letterType, $data) {
            $updateData = [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ];

            if (array_key_exists('legacy_resource', $data)) {
                $updateData['legacy_resource'] = $data['legacy_resource'];
            }

            if (isset($data['is_active'])) {
                $updateData['is_active'] = $data['is_active'];
            }

            $letterType->update($updateData);

            return $letterType->fresh();
        });
    }

    public function updateStatus($letterType, bool $isActive)
    {
        if (LegacyAccess::isAvailable('ref_jenis_surat')) {
            $id = $letterType instanceof LegacyLetterType || $letterType instanceof LetterType ? $letterType->id : $letterType;
            $legacy = LegacyLetterType::findOrFail($id);

            return DB::connection('legacy')->transaction(function () use ($legacy, $isActive) {
                $legacy->update(['status' => $isActive ? '1' : '0']);
                return $legacy->fresh();
            });
        }

        if (! ($letterType instanceof LetterType)) {
            $letterType = LetterType::findOrFail($letterType);
        }

        $letterType->update(['is_active' => $isActive]);

        return $letterType->fresh();
    }

    public function delete($letterType): bool
    {
        if (LegacyAccess::isAvailable('ref_jenis_surat')) {
            $id = $letterType instanceof LegacyLetterType || $letterType instanceof LetterType ? $letterType->id : $letterType;
            $legacy = LegacyLetterType::findOrFail($id);

            return DB::connection('legacy')->transaction(function () use ($legacy) {
                return (bool) $legacy->delete();
            });
        }

        if (! ($letterType instanceof LetterType)) {
            $letterType = LetterType::findOrFail($letterType);
        }

        return DB::transaction(function () use ($letterType) {
            return (bool) $letterType->delete();
        });
    }
}
