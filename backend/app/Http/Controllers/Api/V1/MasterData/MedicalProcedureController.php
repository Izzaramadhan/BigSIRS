<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMedicalProcedureRequest;
use App\Http\Requests\UpdateMedicalProcedureRequest;
use App\Http\Requests\UpdateMedicalProcedureStatusRequest;
use App\Http\Resources\MedicalProcedureResource;
use App\Models\Legacy\LegacyMedicalProcedure;
use App\Models\MasterData\MedicalProcedure;
use App\Models\TariffType;
use App\Support\LegacyAccess;
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
        $perPage = (int) $request->input('per_page', 10);

        if (LegacyAccess::isAvailable('ref_tarif_tindakan')) {
            $query = LegacyMedicalProcedure::with('procedureCategory');

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            $categoryId = $request->input('category_id') ?? $request->input('procedure_category_id');
            if ($categoryId) {
                $query->where('id_kategori', $categoryId);
            }

            if ($request->has('is_visible') && $request->is_visible !== '') {
                $isVisible = filter_var($request->is_visible, FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isVisible ? 1 : 0);
            }

            return MedicalProcedureResource::collection($query->paginate($perPage));
        }

        $query = $this->buildFilteredQuery($request);
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
                '<b><style bgcolor="#E2E8F0"><top>Unit Layanan</b>',
                '<b><style bgcolor="#E2E8F0"><top><center>Status</b>',
            ],
        ];

        $rowNo = 1;
        foreach ($procedures as $procedure) {
            $categoryName = $procedure->procedureCategory?->name;
            $codeVal = $procedure->code;
            $icdVal = $procedure->icd9Cm?->code;
            $nameVal = $procedure->name;
            $polyclinicsStr = $procedure->polyclinics->pluck('name')->join(', ');
            $statusStr = $procedure->is_visible ? 'Aktif' : 'Nonaktif';

            $tariffs = $procedure->tariffs;
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
        if (LegacyAccess::isAvailable('ref_tarif_tindakan')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId() ?? 1;

            $procedure = DB::connection('legacy')->transaction(function () use ($request, $legacyUserId) {
                $tariffsData = $request->input('tariffs', []);
                $firstTariff = $tariffsData[0] ?? null;
                $tariffTypeId = $firstTariff['tariff_type_id'] ?? null;
                $totalAmount = $firstTariff ? collect($firstTariff['components'] ?? [])->sum('amount') : 0;

                $procedure = LegacyMedicalProcedure::create([
                    'kode' => $request->input('code') ?? ('TND-' . time()),
                    'nama' => $request->input('name'),
                    'id_kategori' => $request->input('procedure_category_id'),
                    'id_jenis_tarif' => $tariffTypeId,
                    'harga' => $totalAmount,
                    'status' => $request->boolean('is_visible', true) ? '1' : '0',
                    'id_user' => $legacyUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Polyclinic mappings
                if (LegacyAccess::isAvailable('map_tarif_tindakan_poliklinik') && $request->has('polyclinics')) {
                    foreach ($request->input('polyclinics', []) as $poliId) {
                        DB::connection('legacy')->table('map_tarif_tindakan_poliklinik')->insert([
                            'id_ref_tarif_tindakan' => $procedure->id,
                            'id_poliklinik' => $poliId,
                            'status' => '1',
                            'id_user' => $legacyUserId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Report Group mappings
                if (LegacyAccess::isAvailable('map_tindakan_kelompok_laporan') && $request->has('report_groups')) {
                    foreach ($request->input('report_groups', []) as $rgId) {
                        DB::connection('legacy')->table('map_tindakan_kelompok_laporan')->insert([
                            'id_ref_tarif_tindakan' => $procedure->id,
                            'id_kelompok_laporan' => $rgId,
                            'status' => '1',
                            'id_user' => $legacyUserId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                return $procedure->load('procedureCategory');
            });

            return (new MedicalProcedureResource($procedure))->response()->setStatusCode(201);
        }

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

            return (new MedicalProcedureResource($this->loadRelations($procedure)))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Failed to create Medical Procedure: '.$e->getMessage()], 500);
        }
    }

    public function show($procedure)
    {
        $id = $procedure instanceof MedicalProcedure ? $procedure->id : $procedure;
        if (LegacyAccess::isAvailable('ref_tarif_tindakan')) {
            $legacy = LegacyMedicalProcedure::with('procedureCategory')->findOrFail($id);
            return new MedicalProcedureResource($legacy);
        }

        if (! ($procedure instanceof MedicalProcedure)) {
            $procedure = MedicalProcedure::findOrFail($id);
        }

        return new MedicalProcedureResource($this->loadRelations($procedure));
    }

    public function update(UpdateMedicalProcedureRequest $request, $procedure)
    {
        $id = $procedure instanceof MedicalProcedure ? $procedure->id : $procedure;

        if (LegacyAccess::isAvailable('ref_tarif_tindakan')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId() ?? 1;

            $updated = DB::connection('legacy')->transaction(function () use ($request, $id, $legacyUserId) {
                $legacy = LegacyMedicalProcedure::findOrFail($id);
                $tariffsData = $request->input('tariffs', []);
                $firstTariff = $tariffsData[0] ?? null;
                $tariffTypeId = $firstTariff ? ($firstTariff['tariff_type_id'] ?? $legacy->id_jenis_tarif) : $legacy->id_jenis_tarif;
                $totalAmount = $firstTariff ? collect($firstTariff['components'] ?? [])->sum('amount') : $legacy->harga;

                $legacy->update([
                    'kode' => $request->input('code') ?? $legacy->kode,
                    'nama' => $request->input('name') ?? $legacy->nama,
                    'id_kategori' => $request->input('procedure_category_id') ?? $legacy->id_kategori,
                    'id_jenis_tarif' => $tariffTypeId,
                    'harga' => $totalAmount,
                    'status' => $request->has('is_visible') ? ($request->boolean('is_visible') ? '1' : '0') : $legacy->status,
                    'updated_at' => now(),
                ]);

                // Sync polyclinics
                if (LegacyAccess::isAvailable('map_tarif_tindakan_poliklinik') && $request->has('polyclinics')) {
                    DB::connection('legacy')->table('map_tarif_tindakan_poliklinik')
                        ->where('id_ref_tarif_tindakan', $legacy->id)
                        ->delete();

                    foreach ($request->input('polyclinics', []) as $poliId) {
                        DB::connection('legacy')->table('map_tarif_tindakan_poliklinik')->insert([
                            'id_ref_tarif_tindakan' => $legacy->id,
                            'id_poliklinik' => $poliId,
                            'status' => '1',
                            'id_user' => $legacyUserId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Sync report groups
                if (LegacyAccess::isAvailable('map_tindakan_kelompok_laporan') && $request->has('report_groups')) {
                    DB::connection('legacy')->table('map_tindakan_kelompok_laporan')
                        ->where('id_ref_tarif_tindakan', $legacy->id)
                        ->delete();

                    foreach ($request->input('report_groups', []) as $rgId) {
                        DB::connection('legacy')->table('map_tindakan_kelompok_laporan')->insert([
                            'id_ref_tarif_tindakan' => $legacy->id,
                            'id_kelompok_laporan' => $rgId,
                            'status' => '1',
                            'id_user' => $legacyUserId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                return $legacy->load('procedureCategory');
            });

            return new MedicalProcedureResource($updated);
        }

        if (! ($procedure instanceof MedicalProcedure)) {
            $procedure = MedicalProcedure::findOrFail($id);
        }

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

    public function updateVisibility(UpdateMedicalProcedureStatusRequest $request, $procedure)
    {
        $id = $procedure instanceof MedicalProcedure ? $procedure->id : $procedure;

        if (LegacyAccess::isAvailable('ref_tarif_tindakan')) {
            $legacy = LegacyMedicalProcedure::findOrFail($id);
            $legacy->update([
                'status' => $request->boolean('is_visible') ? '1' : '0',
                'updated_at' => now(),
            ]);
            return new MedicalProcedureResource($legacy->load('procedureCategory'));
        }

        if (! ($procedure instanceof MedicalProcedure)) {
            $procedure = MedicalProcedure::findOrFail($id);
        }

        $procedure->update(['is_visible' => $request->is_visible]);

        return new MedicalProcedureResource($this->loadRelations($procedure));
    }

    public function destroy($procedure)
    {
        $id = $procedure instanceof MedicalProcedure ? $procedure->id : $procedure;

        if (LegacyAccess::isAvailable('ref_tarif_tindakan')) {
            $legacy = LegacyMedicalProcedure::findOrFail($id);

            DB::connection('legacy')->transaction(function () use ($legacy) {
                if (LegacyAccess::isAvailable('map_tarif_tindakan_poliklinik')) {
                    DB::connection('legacy')->table('map_tarif_tindakan_poliklinik')
                        ->where('id_ref_tarif_tindakan', $legacy->id)
                        ->delete();
                }
                if (LegacyAccess::isAvailable('map_tindakan_kelompok_laporan')) {
                    DB::connection('legacy')->table('map_tindakan_kelompok_laporan')
                        ->where('id_ref_tarif_tindakan', $legacy->id)
                        ->delete();
                }
                $legacy->delete();
            });

            return response()->noContent();
        }

        if (! ($procedure instanceof MedicalProcedure)) {
            $procedure = MedicalProcedure::findOrFail($id);
        }

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
