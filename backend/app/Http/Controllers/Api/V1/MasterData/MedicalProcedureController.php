<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMedicalProcedureRequest;
use App\Http\Requests\UpdateMedicalProcedureRequest;
use App\Http\Requests\UpdateMedicalProcedureStatusRequest;
use App\Http\Resources\MedicalProcedureResource;
use App\Models\MasterData\MedicalProcedure;
use App\Models\TariffType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MedicalProcedureController extends Controller
{
    public function index(Request $request)
    {
        $query = MedicalProcedure::with([
            'procedureCategory',
            'icd9Cm',
            'polyclinics',
            'tariffs.tariffType',
        ]);

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhereHas('icd9Cm', function ($q2) use ($search) {
                      $q2->where('code', 'like', "%{$search}%")
                         ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('procedure_category_id') && $request->procedure_category_id !== '') {
            $query->where('procedure_category_id', $request->procedure_category_id);
        }

        if ($request->has('tariff_type_id') && $request->tariff_type_id !== '') {
            $query->whereHas('tariffs', function ($q) use ($request) {
                $q->where('tariff_type_id', $request->tariff_type_id);
            });
        }

        if ($request->has('polyclinic_id') && $request->polyclinic_id !== '') {
            $query->whereHas('polyclinics', function ($q) use ($request) {
                $q->where('polyclinics.id', $request->polyclinic_id);
            });
        }

        if ($request->has('is_visible') && $request->is_visible !== '') {
            $query->where('is_visible', $request->is_visible === 'true' || $request->is_visible === '1');
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        
        $allowedSorts = ['id', 'name', 'code', 'created_at', 'is_visible'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = $request->input('per_page', 10);
        $procedures = $query->paginate($perPage);

        return MedicalProcedureResource::collection($procedures);
    }

    public function store(StoreMedicalProcedureRequest $request)
    {
        $this->validateTariffs($request->input('tariffs'));

        DB::beginTransaction();
        try {
            $procedure = MedicalProcedure::create($request->only([
                'code', 'name', 'procedure_category_id', 'icd9_cm_id', 'is_visible'
            ]));

            if ($request->has('polyclinics')) {
                $procedure->polyclinics()->sync($request->input('polyclinics'));
            }

            if ($request->has('report_groups')) {
                $procedure->reportGroups()->sync($request->input('report_groups'));
            }

            foreach ($request->input('tariffs') as $tariffData) {
                $tariff = $procedure->tariffs()->create([
                    'tariff_type_id' => $tariffData['tariff_type_id'],
                    'total_amount' => collect($tariffData['components'])->sum('amount'),
                ]);

                $tariffType = TariffType::with('components')->find($tariffData['tariff_type_id']);
                $tariffTypeComponents = $tariffType->components->keyBy('tariff_component_id');

                foreach ($tariffData['components'] as $compData) {
                    $snapshot = $tariffTypeComponents->has($compData['tariff_component_id']) 
                        ? $tariffTypeComponents[$compData['tariff_component_id']]->percentage 
                        : 0;

                    $tariff->components()->create([
                        'tariff_component_id' => $compData['tariff_component_id'],
                        'percentage_snapshot' => $snapshot,
                        'amount' => $compData['amount'],
                    ]);
                }
            }

            DB::commit();
            return new MedicalProcedureResource($this->loadRelations($procedure));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create Medical Procedure: ' . $e->getMessage()], 500);
        }
    }

    public function show(MedicalProcedure $procedure)
    {
        return new MedicalProcedureResource($this->loadRelations($procedure));
    }

    public function update(UpdateMedicalProcedureRequest $request, MedicalProcedure $procedure)
    {
        $this->validateTariffs($request->input('tariffs'));

        DB::beginTransaction();
        try {
            $procedure->update($request->only([
                'code', 'name', 'procedure_category_id', 'icd9_cm_id', 'is_visible'
            ]));

            if ($request->has('polyclinics')) {
                $procedure->polyclinics()->sync($request->input('polyclinics'));
            }

            if ($request->has('report_groups')) {
                $procedure->reportGroups()->sync($request->input('report_groups'));
            }

            $submittedTariffIds = collect($request->input('tariffs'))->pluck('id')->filter()->toArray();
            $procedure->tariffs()->whereNotIn('id', $submittedTariffIds)->delete();

            foreach ($request->input('tariffs') as $tariffData) {
                $totalAmount = collect($tariffData['components'])->sum('amount');
                
                if (!empty($tariffData['id'])) {
                    $tariff = $procedure->tariffs()->find($tariffData['id']);
                    $tariff->update([
                        'tariff_type_id' => $tariffData['tariff_type_id'],
                        'total_amount' => $totalAmount,
                    ]);
                } else {
                    $tariff = $procedure->tariffs()->create([
                        'tariff_type_id' => $tariffData['tariff_type_id'],
                        'total_amount' => $totalAmount,
                    ]);
                }

                $submittedComponentIds = collect($tariffData['components'])->pluck('id')->filter()->toArray();
                $tariff->components()->whereNotIn('id', $submittedComponentIds)->delete();

                $tariffType = TariffType::with('components')->find($tariffData['tariff_type_id']);
                $tariffTypeComponents = $tariffType->components->keyBy('tariff_component_id');

                foreach ($tariffData['components'] as $compData) {
                    // Retain existing snapshot if updating, otherwise fetch from tariffType
                    $snapshot = 0;
                    if (!empty($compData['id'])) {
                        $existingComp = $tariff->components()->find($compData['id']);
                        if ($existingComp) {
                            $snapshot = $existingComp->percentage_snapshot;
                        }
                    } else {
                        $snapshot = $tariffTypeComponents->has($compData['tariff_component_id']) 
                            ? $tariffTypeComponents[$compData['tariff_component_id']]->percentage 
                            : 0;
                    }

                    if (!empty($compData['id'])) {
                        $tariff->components()->where('id', $compData['id'])->update([
                            'tariff_component_id' => $compData['tariff_component_id'],
                            'amount' => $compData['amount'],
                        ]);
                    } else {
                        $tariff->components()->create([
                            'tariff_component_id' => $compData['tariff_component_id'],
                            'percentage_snapshot' => $snapshot,
                            'amount' => $compData['amount'],
                        ]);
                    }
                }
            }

            DB::commit();
            return new MedicalProcedureResource($this->loadRelations($procedure));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update Medical Procedure: ' . $e->getMessage()], 500);
        }
    }

    public function updateVisibility(UpdateMedicalProcedureStatusRequest $request, MedicalProcedure $procedure)
    {
        $procedure->update(['is_visible' => $request->is_visible]);
        return new MedicalProcedureResource($this->loadRelations($procedure));
    }

    public function destroy(MedicalProcedure $procedure)
    {
        $procedure->delete();
        return response()->noContent();
    }

    private function loadRelations(MedicalProcedure $procedure)
    {
        return $procedure->load([
            'procedureCategory',
            'icd9Cm',
            'polyclinics',
            'reportGroups',
            'tariffs.tariffType',
            'tariffs.components.tariffComponent',
        ]);
    }

    private function validateTariffs($tariffsData)
    {
        $tariffTypeIds = [];
        $errors = [];

        foreach ($tariffsData as $i => $tariffData) {
            $tariffTypeId = $tariffData['tariff_type_id'] ?? null;
            if (!$tariffTypeId) continue;

            if (in_array($tariffTypeId, $tariffTypeIds)) {
                $errors["tariffs.{$i}.tariff_type_id"] = ["Duplicate Tariff Type selected."];
            }
            $tariffTypeIds[] = $tariffTypeId;

            $tariffType = TariffType::with('components')->find($tariffTypeId);
            if (!$tariffType) continue;

            $allowedComponentIds = $tariffType->components->pluck('tariff_component_id')->toArray();
            $providedComponentIds = [];

            if (empty($tariffData['components'])) continue;

            foreach ($tariffData['components'] as $j => $comp) {
                $compId = $comp['tariff_component_id'] ?? null;
                if (!$compId) continue;

                if (!in_array($compId, $allowedComponentIds)) {
                    $errors["tariffs.{$i}.components.{$j}.tariff_component_id"] = ["Component is not part of the selected Tariff Type."];
                }

                if (in_array($compId, $providedComponentIds)) {
                    $errors["tariffs.{$i}.components.{$j}.tariff_component_id"] = ["Duplicate Component in Tariff Type."];
                }
                $providedComponentIds[] = $compId;
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
