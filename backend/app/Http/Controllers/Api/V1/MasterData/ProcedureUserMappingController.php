<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Legacy\LegacyMedicalProcedure;
use App\Models\Legacy\LegacyProcedureUserMapping;
use App\Models\MasterData\MedicalProcedure;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcedureUserMappingController extends Controller
{
    public function index(Request $request)
    {
        if (LegacyAccess::isAvailable('map_ref_tindakan_user')) {
            $query = LegacyMedicalProcedure::whereHas('employees')->with(['employees.position']);

            if ($search = $request->get('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('kode', 'like', "%{$search}%")
                        ->orWhereHas('employees', function ($q2) use ($search) {
                            $q2->whereHas('person', function ($p) use ($search) {
                                $p->where('nama', 'like', "%{$search}%");
                            })->orWhere('nip', 'like', "%{$search}%");
                        });
                });
            }

            $sortField = $request->get('sort_by', 'id');
            $sortDir = $request->get('sort_dir', 'desc');
            $allowedSorts = ['id', 'kode', 'nama', 'created_at'];

            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortDir);
            }

            $perPage = (int) $request->get('per_page', 10);
            $data = $query->paginate($perPage);

            return response()->json([
                'data' => $data->map(function ($proc) {
                    return [
                        'id' => $proc->id,
                        'procedure' => [
                            'id' => $proc->id,
                            'code' => $proc->code,
                            'name' => $proc->name,
                        ],
                        'employees' => $proc->employees->map(function ($emp) {
                            return [
                                'id' => $emp->id,
                                'code' => $emp->code,
                                'name' => $emp->name,
                                'profession' => $emp->profession,
                                'is_active' => $emp->is_active,
                            ];
                        }),
                    ];
                }),
                'meta' => [
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                ],
            ]);
        }

        $query = MedicalProcedure::whereHas('employees')->with(['employees']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('employees', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        $sortField = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $allowedSorts = ['id', 'code', 'name', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir);
        }

        $perPage = (int) $request->get('per_page', 10);
        $data = $query->paginate($perPage);

        return response()->json([
            'data' => $data->map(function ($proc) {
                return [
                    'id' => $proc->id,
                    'procedure' => [
                        'id' => $proc->id,
                        'code' => $proc->code,
                        'name' => $proc->name,
                    ],
                    'employees' => $proc->employees->map(function ($emp) {
                        return [
                            'id' => $emp->id,
                            'code' => $emp->code,
                            'name' => $emp->name,
                            'profession' => $emp->profession,
                            'is_active' => $emp->is_active,
                        ];
                    }),
                ];
            }),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $isLegacy = LegacyAccess::isAvailable('map_ref_tindakan_user');
        $procedureTable = $isLegacy ? 'legacy.ref_tarif_tindakan' : 'medical_procedures';
        $employeeTable = $isLegacy ? 'legacy.ref_pegawai' : 'employees';

        $request->validate([
            'procedure_id' => "required|exists:{$procedureTable},id",
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => "required|exists:{$employeeTable},id",
        ], [
            'procedure_id.required' => 'Tindakan wajib dipilih.',
            'employee_ids.required' => 'Minimal satu pegawai wajib dipilih.',
            'employee_ids.min' => 'Minimal satu pegawai wajib dipilih.',
        ]);

        $procedureId = $request->procedure_id;

        if ($isLegacy) {
            $exists = LegacyProcedureUserMapping::where('id_ref_tindakan', $procedureId)->exists();
            if ($exists) {
                return response()->json(['message' => 'Tindakan ini sudah mempunyai mapping.'], 422);
            }

            $employeeIds = array_unique($request->employee_ids);
            $legacyUserId = $request->user()?->legacy_user_id ?? 1;

            DB::connection('legacy')->transaction(function () use ($procedureId, $employeeIds, $legacyUserId) {
                foreach ($employeeIds as $empId) {
                    LegacyProcedureUserMapping::create([
                        'id_ref_tindakan' => $procedureId,
                        'id_aktor' => $empId,
                        'status' => 1,
                        'id_user' => $legacyUserId,
                        'created_at' => now(),
                    ]);
                }
            });

            return response()->json(['message' => 'Mapping berhasil dibuat']);
        }

        $exists = DB::table('procedure_employee')->where('procedure_id', $procedureId)->exists();
        if ($exists) {
            return response()->json(['message' => 'Tindakan ini sudah mempunyai mapping.'], 422);
        }

        $employeeIds = array_unique($request->employee_ids);

        DB::transaction(function () use ($procedureId, $employeeIds) {
            $procedure = MedicalProcedure::findOrFail($procedureId);
            $procedure->employees()->sync($employeeIds);
        });

        return response()->json(['message' => 'Mapping berhasil dibuat']);
    }

    public function show(string $id)
    {
        if (LegacyAccess::isAvailable('map_ref_tindakan_user')) {
            $proc = LegacyMedicalProcedure::with('employees')->findOrFail($id);

            return response()->json([
                'data' => [
                    'id' => $proc->id,
                    'procedure' => [
                        'id' => $proc->id,
                        'code' => $proc->code,
                        'name' => $proc->name,
                    ],
                    'employees' => $proc->employees->map(function ($emp) {
                        return [
                            'id' => $emp->id,
                            'code' => $emp->code,
                            'name' => $emp->name,
                            'profession' => $emp->profession,
                            'is_active' => $emp->is_active,
                        ];
                    }),
                ],
            ]);
        }

        $proc = MedicalProcedure::with('employees')->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $proc->id,
                'procedure' => [
                    'id' => $proc->id,
                    'code' => $proc->code,
                    'name' => $proc->name,
                ],
                'employees' => $proc->employees->map(function ($emp) {
                    return [
                        'id' => $emp->id,
                        'code' => $emp->code,
                        'name' => $emp->name,
                        'profession' => $emp->profession,
                        'is_active' => $emp->is_active,
                    ];
                }),
            ],
        ]);
    }

    public function update(Request $request, string $id)
    {
        $isLegacy = LegacyAccess::isAvailable('map_ref_tindakan_user');
        $procedureTable = $isLegacy ? 'legacy.ref_tarif_tindakan' : 'medical_procedures';
        $employeeTable = $isLegacy ? 'legacy.ref_pegawai' : 'employees';

        $request->validate([
            'procedure_id' => "required|exists:{$procedureTable},id",
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => "required|exists:{$employeeTable},id",
        ], [
            'procedure_id.required' => 'Tindakan wajib dipilih.',
            'employee_ids.required' => 'Minimal satu pegawai wajib dipilih.',
            'employee_ids.min' => 'Minimal satu pegawai wajib dipilih.',
        ]);

        if ($request->procedure_id != $id) {
            return response()->json(['message' => 'ID tindakan tidak cocok'], 422);
        }

        $employeeIds = array_unique($request->employee_ids);

        if ($isLegacy) {
            $legacyUserId = $request->user()?->legacy_user_id ?? 1;

            DB::connection('legacy')->transaction(function () use ($id, $employeeIds, $legacyUserId) {
                // Remove mappings not in submitted
                LegacyProcedureUserMapping::where('id_ref_tindakan', $id)
                    ->whereNotIn('id_aktor', $employeeIds)
                    ->delete();

                // Add missing ones
                $existingActors = LegacyProcedureUserMapping::where('id_ref_tindakan', $id)
                    ->pluck('id_aktor')
                    ->toArray();

                foreach ($employeeIds as $empId) {
                    if (! in_array($empId, $existingActors)) {
                        LegacyProcedureUserMapping::create([
                            'id_ref_tindakan' => $id,
                            'id_aktor' => $empId,
                            'status' => 1,
                            'id_user' => $legacyUserId,
                            'created_at' => now(),
                        ]);
                    }
                }
            });

            return response()->json(['message' => 'Mapping berhasil diperbarui']);
        }

        DB::transaction(function () use ($id, $employeeIds) {
            $procedure = MedicalProcedure::findOrFail($id);
            $procedure->employees()->sync($employeeIds);
        });

        return response()->json(['message' => 'Mapping berhasil diperbarui']);
    }

    public function destroy(string $id)
    {
        if (LegacyAccess::isAvailable('map_ref_tindakan_user')) {
            DB::connection('legacy')->transaction(function () use ($id) {
                LegacyProcedureUserMapping::where('id_ref_tindakan', $id)->delete();
            });

            return response()->json(['message' => 'Mapping berhasil dihapus']);
        }

        $procedure = MedicalProcedure::findOrFail($id);
        $procedure->employees()->detach();

        return response()->json(['message' => 'Mapping berhasil dihapus']);
    }
}

