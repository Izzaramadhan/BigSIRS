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
use Shuchkin\SimpleXLSXGen;

class MedicalProcedureController extends Controller
{
    protected function buildFilteredQuery(Request $request)
    {
        $query = MedicalProcedure::with([
            'procedureCategory',
            'icd9Cm',
            'polyclinics',
            'tariffs.tariffType',
        ]);

        if ($request->filled('search')) {
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

        $categoryId = $request->input('category_id') ?? $request->input('procedure_category_id');
        if ($categoryId !== null && $categoryId !== '') {
            if (is_numeric($categoryId) && (int) $categoryId > 0) {
                $query->where('procedure_category_id', (int) $categoryId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('tariff_type_id')) {
            $tariffTypeId = $request->input('tariff_type_id');
            if (is_numeric($tariffTypeId) && (int) $tariffTypeId > 0) {
                $query->whereHas('tariffs', function ($q) use ($tariffTypeId) {
                    $q->where('tariff_type_id', (int) $tariffTypeId);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('polyclinic_id')) {
            $query->whereHas('polyclinics', function ($q) use ($request) {
                $q->where('polyclinics.id', $request->polyclinic_id);
            });
        }

        if ($request->has('is_visible') && $request->is_visible !== null && $request->is_visible !== '') {
            $query->where('is_visible', $request->is_visible === 'true' || $request->is_visible === '1');
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedSorts = ['id', 'name', 'code', 'created_at', 'is_visible'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->buildFilteredQuery($request);
        $perPage = $request->input('per_page', 10);
        $procedures = $query->paginate($perPage);

        return MedicalProcedureResource::collection($procedures);
    }

    public function export(Request $request)
    {
        if (! class_exists(SimpleXLSXGen::class)) {
            $fallbackPath = base_path('vendor/shuchkin/simplexlsxgen/src/SimpleXLSXGen.php');
            if (file_exists($fallbackPath)) {
                require_once $fallbackPath;
            }
        }

        $query = $this->buildFilteredQuery($request);
        $procedures = $query->get();

        if ($procedures->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada data tindakan yang dapat diunduh.',
                'data' => [],
            ], 200);
        }

        $rows = [
            [
                '<b><style bgcolor="#E2E8F0"><top><center>No</b>',
                '<b><style bgcolor="#E2E8F0"><top>Kategori</b>',
                '<b><style bgcolor="#E2E8F0"><top><center>Kode</b>',
                '<b><style bgcolor="#E2E8F0"><top><center>ICD-9-CM</b>',
                '<b><style bgcolor="#E2E8F0"><top>Tindakan</b>',
                '<b><style bgcolor="#E2E8F0"><top>Jenis Tarif</b>',
                '<b><style bgcolor="#E2E8F0"><top><right>Harga</b>',
                '<b><style bgcolor="#E2E8F0"><top>Poliklinik</b>',
                '<b><style bgcolor="#E2E8F0"><top><center>Status</b>',
            ],
        ];

        $tariffTypeId = $request->input('tariff_type_id');
        $hasTariffTypeFilter = is_numeric($tariffTypeId) && (int) $tariffTypeId > 0;

        $rowNo = 1;
        foreach ($procedures as $procedure) {
            $categoryName = $procedure->procedureCategory?->name;
            $codeVal = $procedure->name;
            $icdVal = $procedure->icd9Cm?->code;
            $nameVal = $procedure->code;
            $polyclinics = $procedure->polyclinics->pluck('name')->filter()->values();
            $polyclinicsStr = $polyclinics->isNotEmpty() ? $polyclinics->implode(', ') : '-';
            $statusStr = $procedure->is_visible ? 'Aktif' : 'Nonaktif';

            $tariffs = $procedure->tariffs;
            if ($hasTariffTypeFilter) {
                $tariffs = $tariffs->filter(fn ($t) => (int) $t->tariff_type_id === (int) $tariffTypeId);
            }

            if ($tariffs->isEmpty()) {
                $rows[] = [
                    $rowNo++,
                    '<top><raw>' . htmlspecialchars($this->sanitizeForExcel($categoryName)) . '</raw>',
                    "\0" . $this->sanitizeForExcel($codeVal),
                    "\0" . $this->sanitizeForExcel($icdVal),
                    '<top><wraptext><raw>' . htmlspecialchars($this->sanitizeForExcel($nameVal)) . '</raw>',
                    '<top>-',
                    '<top><right>-',
                    '<top><wraptext><raw>' . htmlspecialchars($this->sanitizeForExcel($polyclinicsStr)) . '</raw>',
                    '<top><center>' . $statusStr,
                ];
            } else {
                foreach ($tariffs as $tariff) {
                    $tariffTypeName = $tariff->tariffType?->name;
                    $amount = (float) $tariff->total_amount;

                    $rows[] = [
                        $rowNo++,
                        '<top><raw>' . htmlspecialchars($this->sanitizeForExcel($categoryName)) . '</raw>',
                        "\0" . $this->sanitizeForExcel($codeVal),
                        "\0" . $this->sanitizeForExcel($icdVal),
                        '<top><wraptext><raw>' . htmlspecialchars($this->sanitizeForExcel($nameVal)) . '</raw>',
                        '<top><raw>' . htmlspecialchars($this->sanitizeForExcel($tariffTypeName)) . '</raw>',
                        '<style nf="Rp #,##0"><top><right>' . $amount,
                        '<top><wraptext><raw>' . htmlspecialchars($this->sanitizeForExcel($polyclinicsStr)) . '</raw>',
                        '<top><center>' . $statusStr,
                    ];
                }
            }
        }

        $xlsx = SimpleXLSXGen::fromArray($rows, 'Data Tindakan');
        $xlsx->freezePanes('A2');
        $xlsx->autoFilter('A1:I1');
        $xlsx->setColWidth(1, 6)
            ->setColWidth(2, 20)
            ->setColWidth(3, 16)
            ->setColWidth(4, 16)
            ->setColWidth(5, 36)
            ->setColWidth(6, 20)
            ->setColWidth(7, 18)
            ->setColWidth(8, 30)
            ->setColWidth(9, 14);

        $filename = 'data-tindakan-' . now()->format('Y-m-d-His') . '.xlsx';
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0, must-revalidate',
            'Pragma' => 'public',
        ];

        return response()->streamDownload(function () use ($xlsx) {
            echo (string) $xlsx;
        }, $filename, $headers);
    }

    protected function sanitizeForExcel(?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return '-';
        }

        if ($trimmed === '-') {
            return '-';
        }

        $firstChar = $trimmed[0];
        if (in_array($firstChar, ['=', '+', '-', '@'], true)) {
            return "'" . $trimmed;
        }

        return $trimmed;
    }

    public function store(StoreMedicalProcedureRequest $request)
    {
        $this->validateTariffs($request->input('tariffs'));

        DB::beginTransaction();
        try {
            $procedure = MedicalProcedure::create($request->only([
                'code', 'name', 'procedure_category_id', 'icd9_cm_id', 'is_visible',
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

            return response()->json(['message' => 'Failed to create Medical Procedure: '.$e->getMessage()], 500);
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
                'code', 'name', 'procedure_category_id', 'icd9_cm_id', 'is_visible',
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

                if (! empty($tariffData['id'])) {
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
                    if (! empty($compData['id'])) {
                        $existingComp = $tariff->components()->find($compData['id']);
                        if ($existingComp) {
                            $snapshot = $existingComp->percentage_snapshot;
                        }
                    } else {
                        $snapshot = $tariffTypeComponents->has($compData['tariff_component_id'])
                            ? $tariffTypeComponents[$compData['tariff_component_id']]->percentage
                            : 0;
                    }

                    if (! empty($compData['id'])) {
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

            return response()->json(['message' => 'Failed to update Medical Procedure: '.$e->getMessage()], 500);
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
            if (! $tariffTypeId) {
                continue;
            }

            if (in_array($tariffTypeId, $tariffTypeIds)) {
                $errors["tariffs.{$i}.tariff_type_id"] = ['Duplicate Tariff Type selected.'];
            }
            $tariffTypeIds[] = $tariffTypeId;

            $tariffType = TariffType::with('components')->find($tariffTypeId);
            if (! $tariffType) {
                continue;
            }

            $allowedComponentIds = $tariffType->components->pluck('tariff_component_id')->toArray();
            $providedComponentIds = [];

            if (empty($tariffData['components'])) {
                continue;
            }

            foreach ($tariffData['components'] as $j => $comp) {
                $compId = $comp['tariff_component_id'] ?? null;
                if (! $compId) {
                    continue;
                }

                if (! in_array($compId, $allowedComponentIds)) {
                    $errors["tariffs.{$i}.components.{$j}.tariff_component_id"] = ['Komponen tidak termasuk dalam jenis tarif yang dipilih.'];
                }

                if (in_array($compId, $providedComponentIds)) {
                    $errors["tariffs.{$i}.components.{$j}.tariff_component_id"] = ['Komponen duplikat dalam Jenis Tarif.'];
                }
                $providedComponentIds[] = $compId;
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
