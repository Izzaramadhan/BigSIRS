<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Models\MasterData\MedicalProcedure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcedureUserMappingController extends Controller
{
    public function index(Request $request)
    {
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
        $request->validate([
            'procedure_id' => 'required|exists:medical_procedures,id',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'required|exists:employees,id',
        ], [
            'procedure_id.required' => 'Tindakan wajib dipilih.',
            'employee_ids.required' => 'Minimal satu pegawai wajib dipilih.',
            'employee_ids.min' => 'Minimal satu pegawai wajib dipilih.',
        ]);

        $procedureId = $request->procedure_id;

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
        $request->validate([
            'procedure_id' => 'required|exists:medical_procedures,id',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'required|exists:employees,id',
        ], [
            'procedure_id.required' => 'Tindakan wajib dipilih.',
            'employee_ids.required' => 'Minimal satu pegawai wajib dipilih.',
            'employee_ids.min' => 'Minimal satu pegawai wajib dipilih.',
        ]);

        if ($request->procedure_id != $id) {
            return response()->json(['message' => 'ID tindakan tidak cocok'], 422);
        }

        $employeeIds = array_unique($request->employee_ids);

        DB::transaction(function () use ($id, $employeeIds) {
            $procedure = MedicalProcedure::findOrFail($id);
            $procedure->employees()->sync($employeeIds);
        });

        return response()->json(['message' => 'Mapping berhasil diperbarui']);
    }

    public function destroy(string $id)
    {
        $procedure = MedicalProcedure::findOrFail($id);
        $procedure->employees()->detach();

        return response()->json(['message' => 'Mapping berhasil dihapus']);
    }
}
