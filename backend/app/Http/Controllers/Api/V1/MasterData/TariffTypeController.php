<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTariffTypeRequest;
use App\Http\Requests\UpdateTariffTypeRequest;
use App\Http\Requests\UpdateTariffTypeStatusRequest;
use App\Http\Resources\TariffTypeResource;
use App\Models\TariffType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TariffTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = TariffType::with('components.component');

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active === 'true' || $request->is_active === '1');
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        
        $allowedSorts = ['id', 'name', 'code', 'created_at', 'updated_at', 'is_active'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = $request->input('per_page', 10);
        $tariffTypes = $query->paginate($perPage);

        return TariffTypeResource::collection($tariffTypes);
    }

    public function store(StoreTariffTypeRequest $request)
    {
        DB::beginTransaction();
        try {
            $tariffType = TariffType::create($request->validated());

            $componentsData = $request->input('components', []);
            foreach ($componentsData as $comp) {
                $tariffType->components()->create([
                    'tariff_component_id' => $comp['tariff_component_id'],
                    'percentage' => $comp['percentage'],
                ]);
            }

            // Check total percentage for needs_review mark (if they didn't bypass rules somehow, it's fine)
            // The requirement says "Backend boleh menerima total yang tidak sama dengan 100" but we can flag it.
            $totalPercentage = collect($componentsData)->sum('percentage');
            if ($totalPercentage != 100) {
                $tariffType->update(['needs_review' => true]);
            }

            DB::commit();
            return new TariffTypeResource($tariffType->load('components.component'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create Tariff Type: ' . $e->getMessage()], 500);
        }
    }

    public function show(TariffType $tariffType)
    {
        return new TariffTypeResource($tariffType->load('components.component'));
    }

    public function update(UpdateTariffTypeRequest $request, TariffType $tariffType)
    {
        DB::beginTransaction();
        try {
            $tariffType->update($request->validated());

            $componentsData = collect($request->input('components', []));
            
            // Delete removed components (pivots)
            $submittedPivotIds = $componentsData->pluck('id')->filter()->toArray();
            $tariffType->components()->whereNotIn('id', $submittedPivotIds)->delete();

            // Update or create components
            foreach ($componentsData as $comp) {
                if (!empty($comp['id'])) {
                    // Update existing
                    $tariffType->components()->where('id', $comp['id'])->update([
                        'tariff_component_id' => $comp['tariff_component_id'],
                        'percentage' => $comp['percentage'],
                    ]);
                } else {
                    // Create new
                    $tariffType->components()->create([
                        'tariff_component_id' => $comp['tariff_component_id'],
                        'percentage' => $comp['percentage'],
                    ]);
                }
            }

            // Re-check total percentage for needs_review
            $totalPercentage = $tariffType->components()->sum('percentage');
            if ($totalPercentage != 100) {
                $tariffType->update(['needs_review' => true]);
            } else {
                // If they fixed it, we might clear needs_review, but let's just leave it or clear it if it's 100 and has code.
                if ($tariffType->code !== null) {
                    // Also check if any duplicate component exists
                    $duplicates = $tariffType->components()
                        ->select('tariff_component_id')
                        ->groupBy('tariff_component_id')
                        ->havingRaw('COUNT(*) > 1')
                        ->count();
                    
                    if ($duplicates == 0) {
                        $tariffType->update(['needs_review' => false]);
                    }
                }
            }

            DB::commit();
            return new TariffTypeResource($tariffType->load('components.component'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update Tariff Type: ' . $e->getMessage()], 500);
        }
    }

    public function updateStatus(UpdateTariffTypeStatusRequest $request, TariffType $tariffType)
    {
        $tariffType->update($request->validated());
        return new TariffTypeResource($tariffType);
    }

    public function destroy(TariffType $tariffType)
    {
        // TODO: Enforce HTTP 409 when target tables (Tindakan, Tarif, dll) are implemented.
        // For now, check if legacy connection has it used in ref_tarif_tindakan just in case.
        if ($tariffType->legacy_id) {
            try {
                $isUsed = DB::connection('legacy')->table('ref_tarif_tindakan')
                    ->where('id_jenis_tarif', $tariffType->legacy_id)
                    ->exists();
                
                if ($isUsed) {
                    return response()->json(['message' => 'Data sedang digunakan dan tidak dapat dihapus.'], 409);
                }
            } catch (\Exception $e) {
                // Ignore if connection fails
            }
        }

        $tariffType->delete();
        return response()->noContent();
    }
}
