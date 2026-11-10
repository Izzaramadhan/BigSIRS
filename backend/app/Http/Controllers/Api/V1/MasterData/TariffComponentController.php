<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTariffComponentRequest;
use App\Http\Requests\UpdateTariffComponentRequest;
use App\Http\Requests\UpdateTariffComponentStatusRequest;
use App\Http\Resources\TariffComponentResource;
use App\Models\Legacy\LegacyTariffComponent;
use App\Models\TariffComponent;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TariffComponentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        if (LegacyAccess::isAvailable('ref_komponen')) {
            $query = LegacyTariffComponent::query()->orderBy('nama', 'asc');

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->has('is_active')) {
                $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? 1 : 0);
            }

            return TariffComponentResource::collection($query->paginate($perPage));
        }

        $query = TariffComponent::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $sortField = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_dir', 'desc');

        $allowedSorts = ['name', 'is_active', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        $perPage = $request->input('per_page', 10);
        $components = $query->paginate($perPage);

        return TariffComponentResource::collection($components);
    }

    public function store(StoreTariffComponentRequest $request)
    {
        if (LegacyAccess::isAvailable('ref_komponen')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId() ?? 1;
            $data = $request->validated();

            $component = DB::connection('legacy')->transaction(function () use ($data, $legacyUserId) {
                return LegacyTariffComponent::create([
                    'nama' => $data['name'],
                    'deskripsi' => $data['description'] ?? null,
                    'status' => isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1,
                    'id_user' => $legacyUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

            return (new TariffComponentResource($component))->response()->setStatusCode(201);
        }

        $component = TariffComponent::create($request->validated());

        return (new TariffComponentResource($component))->response()->setStatusCode(201);
    }

    public function show($tariffComponent)
    {
        $id = $tariffComponent instanceof TariffComponent ? $tariffComponent->id : $tariffComponent;
        if (LegacyAccess::isAvailable('ref_komponen')) {
            $legacy = LegacyTariffComponent::findOrFail($id);
            return new TariffComponentResource($legacy);
        }

        if (! ($tariffComponent instanceof TariffComponent)) {
            $tariffComponent = TariffComponent::findOrFail($id);
        }

        return new TariffComponentResource($tariffComponent);
    }

    public function update(UpdateTariffComponentRequest $request, $tariffComponent)
    {
        $id = $tariffComponent instanceof TariffComponent ? $tariffComponent->id : $tariffComponent;

        if (LegacyAccess::isAvailable('ref_komponen')) {
            return DB::connection('legacy')->transaction(function () use ($request, $id) {
                $legacy = LegacyTariffComponent::findOrFail($id);
                $data = $request->validated();
                $legacy->update([
                    'nama' => $data['name'] ?? $legacy->nama,
                    'deskripsi' => array_key_exists('description', $data) ? $data['description'] : $legacy->deskripsi,
                    'status' => isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : $legacy->status,
                    'updated_at' => now(),
                ]);

                return new TariffComponentResource($legacy);
            });
        }

        if (! ($tariffComponent instanceof TariffComponent)) {
            $tariffComponent = TariffComponent::findOrFail($id);
        }

        $tariffComponent->update($request->validated());

        return new TariffComponentResource($tariffComponent);
    }

    public function updateStatus(UpdateTariffComponentStatusRequest $request, $tariffComponent)
    {
        $id = $tariffComponent instanceof TariffComponent ? $tariffComponent->id : $tariffComponent;

        if (LegacyAccess::isAvailable('ref_komponen')) {
            $legacy = LegacyTariffComponent::findOrFail($id);
            $legacy->update([
                'status' => $request->boolean('is_active') ? 1 : 0,
                'updated_at' => now(),
            ]);
            return new TariffComponentResource($legacy);
        }

        if (! ($tariffComponent instanceof TariffComponent)) {
            $tariffComponent = TariffComponent::findOrFail($id);
        }

        $tariffComponent->update($request->validated());

        return new TariffComponentResource($tariffComponent);
    }

    public function destroy($tariffComponent)
    {
        $id = $tariffComponent instanceof TariffComponent ? $tariffComponent->id : $tariffComponent;

        if (LegacyAccess::isAvailable('ref_komponen')) {
            $legacy = LegacyTariffComponent::findOrFail($id);

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->delete();
            });

            return response()->noContent();
        }

        if (! ($tariffComponent instanceof TariffComponent)) {
            $tariffComponent = TariffComponent::findOrFail($id);
        }

        $tariffComponent->delete();

        return response()->noContent();
    }
}
