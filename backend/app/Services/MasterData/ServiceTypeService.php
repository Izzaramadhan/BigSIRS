<?php

namespace App\Services\MasterData;

use App\Models\Legacy\LegacyServiceType;
use App\Models\ServiceType;
use App\Support\LegacyAccess;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServiceTypeService
{
    /**
     * Get paginated service types based on filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 10, string $sort = 'name', string $order = 'asc'): LengthAwarePaginator
    {
        $allowedSorts = ['id', 'name', 'is_active', 'created_at'];
        $sort = in_array($sort, $allowedSorts) ? $sort : 'name';
        $order = strtolower($order) === 'desc' ? 'desc' : 'asc';

        if (LegacyAccess::isAvailable('ref_jenis_layanan')) {
            $query = LegacyServiceType::query();

            if (! empty($filters['search'])) {
                $query->search($filters['search']);
            }

            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $isActive = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? '1' : '0');
            }

            $dbSort = match ($sort) {
                'name' => 'nama',
                'is_active' => 'status',
                default => 'id',
            };

            return $query->orderBy($dbSort, $order)->paginate($perPage);
        }

        $query = ServiceType::query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderBy($sort, $order)->paginate($perPage);
    }

    /**
     * Create a new service type.
     */
    public function create(array $data)
    {
        if (LegacyAccess::isAvailable('ref_jenis_layanan')) {
            return DB::connection('legacy')->transaction(function () use ($data) {
                return LegacyServiceType::create([
                    'nama' => $data['name'],
                    'status' => array_key_exists('is_active', $data) ? ($data['is_active'] ? '1' : '0') : '1',
                ]);
            });
        }

        return ServiceType::create($data);
    }

    /**
     * Update an existing service type.
     */
    public function update($serviceType, array $data)
    {
        if (LegacyAccess::isAvailable('ref_jenis_layanan')) {
            $id = $serviceType instanceof LegacyServiceType || $serviceType instanceof ServiceType ? $serviceType->id : $serviceType;
            $legacy = LegacyServiceType::findOrFail($id);

            return DB::connection('legacy')->transaction(function () use ($legacy, $data) {
                $updateData = ['nama' => $data['name']];
                if (array_key_exists('is_active', $data)) {
                    $updateData['status'] = $data['is_active'] ? '1' : '0';
                }
                $legacy->update($updateData);
                return $legacy->fresh();
            });
        }

        if (! ($serviceType instanceof ServiceType)) {
            $serviceType = ServiceType::findOrFail($serviceType);
        }

        $serviceType->update($data);

        return $serviceType;
    }

    /**
     * Delete a service type.
     */
    public function delete($serviceType): bool
    {
        if (LegacyAccess::isAvailable('ref_jenis_layanan')) {
            $id = $serviceType instanceof LegacyServiceType || $serviceType instanceof ServiceType ? $serviceType->id : $serviceType;
            $legacy = LegacyServiceType::findOrFail($id);

            $isUsed = false;
            if (DB::connection('legacy')->getSchemaBuilder()->hasTable('trx_visit')) {
                $isUsed = DB::connection('legacy')->table('trx_visit')
                    ->where('id_jenis_layanan', $id)
                    ->exists();
            }

            if ($isUsed) {
                throw ValidationException::withMessages([
                    'id' => 'Jenis layanan tidak dapat dihapus karena sudah digunakan pada transaksi kunjungan.',
                ]);
            }

            return DB::connection('legacy')->transaction(function () use ($legacy) {
                return (bool) $legacy->delete();
            });
        }

        if (! ($serviceType instanceof ServiceType)) {
            $serviceType = ServiceType::findOrFail($serviceType);
        }

        return (bool) $serviceType->delete();
    }
}
