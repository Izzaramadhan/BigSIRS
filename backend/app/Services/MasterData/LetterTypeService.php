<?php

namespace App\Services\MasterData;

use App\Models\LetterType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LetterTypeService
{
    public function getPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
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

    public function create(array $data): LetterType
    {
        return DB::transaction(function () use ($data) {
            return LetterType::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    public function update(LetterType $letterType, array $data): LetterType
    {
        return DB::transaction(function () use ($letterType, $data) {
            $updateData = [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ];

            if (isset($data['is_active'])) {
                $updateData['is_active'] = $data['is_active'];
            }

            $letterType->update($updateData);

            return $letterType;
        });
    }

    public function updateStatus(LetterType $letterType, bool $isActive): LetterType
    {
        $letterType->update(['is_active' => $isActive]);

        return $letterType;
    }

    public function delete(LetterType $letterType): bool
    {
        return DB::transaction(function () use ($letterType) {
            return $letterType->delete();
        });
    }
}
