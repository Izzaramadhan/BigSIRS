<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Employee::class);
        $query = Employee::with(['education', 'occupation', 'position']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('position_id') && ! is_null($request->get('position_id'))) {
            $query->where('position_id', $request->get('position_id'));
        }

        if ($request->has('is_active') && ! is_null($request->get('is_active'))) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json($query->orderBy('name')->paginate($request->get('per_page', 15)));
    }

    public function store(StoreEmployeeRequest $request)
    {
        $this->authorize('create', Employee::class);
        $employee = DB::transaction(function () use ($request) {
            $data = $request->validated();

            if ($request->hasFile('signature')) {
                $path = $request->file('signature')->store('signatures', 'public');
                $data['signature_path'] = $path;
            }

            return Employee::create($data);
        });

        return response()->json([
            'message' => 'Data Pegawai berhasil ditambahkan.',
            'data' => $employee->load(['education', 'occupation', 'position']),
        ], 201);
    }

    public function show(Employee $employee)
    {
        $this->authorize('view', $employee);

        return response()->json($employee->load(['education', 'occupation', 'position']));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $this->authorize('update', $employee);
        $employee = DB::transaction(function () use ($request, $employee) {
            $data = $request->validated();

            if ($request->hasFile('signature')) {
                // Delete old signature if exists
                if ($employee->signature_path && Storage::disk('public')->exists($employee->signature_path)) {
                    Storage::disk('public')->delete($employee->signature_path);
                }
                $path = $request->file('signature')->store('signatures', 'public');
                $data['signature_path'] = $path;
            } elseif ($request->boolean('remove_signature')) {
                if ($employee->signature_path && Storage::disk('public')->exists($employee->signature_path)) {
                    Storage::disk('public')->delete($employee->signature_path);
                }
                $data['signature_path'] = null;
            }

            $employee->update($data);

            return $employee;
        });

        return response()->json([
            'message' => 'Data Pegawai berhasil diperbarui.',
            'data' => $employee->load(['education', 'occupation', 'position']),
        ]);
    }

    public function updateStatus(Request $request, Employee $employee)
    {
        $this->authorize('update', $employee);

        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $employee->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return response()->json([
            'message' => 'Status Pegawai berhasil diperbarui.',
            'data' => $employee,
        ]);
    }

    public function destroy(Employee $employee)
    {
        $this->authorize('delete', $employee);
        $employee->delete();

        return response()->json([
            'message' => 'Data Pegawai berhasil dihapus.',
        ]);
    }
}
