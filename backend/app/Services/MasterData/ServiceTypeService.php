<?php

namespace App\Services\MasterData;

use App\Models\ServiceType;
use Illuminate\Pagination\LengthAwarePaginator;

class ServiceTypeService
{
    /**
     * Get paginated service types based on filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 10, string $sort = 'name', string $order = 'asc'): LengthAwarePaginator
    {
        $query = ServiceType::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        // Validate sort column to prevent SQL injection
        $allowedSorts = ['id', 'name', 'is_active', 'created_at'];
        $sort = in_array($sort, $allowedSorts) ? $sort : 'name';
        $order = strtolower($order) === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sort, $order)->paginate($perPage);
    }

    /**
     * Create a new service type.
     */
    public function create(array $data): ServiceType
    {
        return ServiceType::create($data);
    }

    /**
     * Update an existing service type.
     */
    public function update(ServiceType $serviceType, array $data): ServiceType
    {
        $serviceType->update($data);
        return $serviceType;
    }

    /**
     * Delete a service type.
     */
    public function delete(ServiceType $serviceType): bool
    {
        return $serviceType->delete();
    }
}
