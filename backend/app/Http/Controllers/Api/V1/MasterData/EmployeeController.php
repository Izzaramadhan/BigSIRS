<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\Legacy\LegacyEmployee;
use App\Models\Legacy\LegacyPerson;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $authModel = LegacyAccess::isAvailable('ref_pegawai') ? LegacyEmployee::class : Employee::class;
        $this->authorize('viewAny', $authModel);

        if (LegacyAccess::isAvailable('ref_pegawai')) {
            $query = LegacyEmployee::with(['person', 'position']);

            if ($search = $request->get('search')) {
                $query->search($search);
            }

            if ($request->has('position_id') && ! is_null($request->get('position_id'))) {
                $query->where('id_jabatan', $request->get('position_id'));
            }

            if ($request->has('is_active') && ! is_null($request->get('is_active'))) {
                $isActive = $request->boolean('is_active');
                $query->where('status', $isActive ? 1 : 0);
            }

            return response()->json($query->paginate($request->get('per_page', 15)));
        }

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
        $authModel = LegacyAccess::isAvailable('ref_pegawai') ? LegacyEmployee::class : Employee::class;
        $this->authorize('create', $authModel);

        if (LegacyAccess::isAvailable('ref_pegawai')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId() ?? 1;

            $employee = DB::connection('legacy')->transaction(function () use ($request, $legacyUserId) {
                $data = $request->validated();

                $person = LegacyPerson::create([
                    'no_ktp' => $data['national_id'] ?? null,
                    'nama' => $data['name'],
                    'gender' => $data['gender'] ?? 'L',
                    'tgl_lahir' => $data['birth_date'] ?? null,
                    'tempat_lahir' => $data['birth_place'] ?? null,
                    'alamat' => $data['address'] ?? null,
                    'no_hp' => $data['phone'] ?? null,
                    'agama' => $data['religion'] ?? null,
                    'id_pendidikan' => $data['education_id'] ?? null,
                    'id_pekerjaan' => $data['occupation_id'] ?? null,
                    'jenis_person' => 'karyawan',
                    'status' => 1,
                    'id_user' => $legacyUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $signaturePath = null;
                if ($request->hasFile('signature')) {
                    $signaturePath = $request->file('signature')->store('signatures', 'public');
                }

                $nip = $data['code'] ?? ('EMP-' . $person->id);
                $isActive = isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1;

                $employee = LegacyEmployee::create([
                    'id_mpi' => $person->id,
                    'nip' => $nip,
                    'id_jabatan' => $data['position_id'] ?? null,
                    'status' => $isActive,
                    'ttd' => $signaturePath,
                    'id_user' => $legacyUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $employee->load(['person', 'position']);
            });

            return response()->json([
                'message' => 'Data Pegawai berhasil ditambahkan.',
                'data' => $employee,
            ], 201);
        }

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

    public function show($employee)
    {
        $id = $employee instanceof Employee ? $employee->id : $employee;
        if (LegacyAccess::isAvailable('ref_pegawai')) {
            $legacy = LegacyEmployee::with(['person', 'position'])->findOrFail($id);
            $this->authorize('view', $legacy);
            return response()->json($legacy);
        }

        if (! ($employee instanceof Employee)) {
            $employee = Employee::findOrFail($id);
        }

        $this->authorize('view', $employee);

        return response()->json($employee->load(['education', 'occupation', 'position']));
    }

    public function update(UpdateEmployeeRequest $request, $employee)
    {
        $id = $employee instanceof Employee ? $employee->id : $employee;

        if (LegacyAccess::isAvailable('ref_pegawai')) {
            $legacy = LegacyEmployee::with('person')->findOrFail($id);
            $this->authorize('update', $legacy);

            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId() ?? 1;

            $updated = DB::connection('legacy')->transaction(function () use ($request, $legacy, $legacyUserId) {
                $data = $request->validated();

                if ($legacy->person) {
                    $legacy->person->update([
                        'no_ktp' => $data['national_id'] ?? $legacy->person->no_ktp,
                        'nama' => $data['name'] ?? $legacy->person->nama,
                        'gender' => $data['gender'] ?? $legacy->person->gender,
                        'tgl_lahir' => $data['birth_date'] ?? $legacy->person->tgl_lahir,
                        'tempat_lahir' => $data['birth_place'] ?? $legacy->person->tempat_lahir,
                        'alamat' => $data['address'] ?? $legacy->person->alamat,
                        'no_hp' => $data['phone'] ?? $legacy->person->no_hp,
                        'agama' => $data['religion'] ?? $legacy->person->agama,
                        'id_pendidikan' => $data['education_id'] ?? $legacy->person->id_pendidikan,
                        'id_pekerjaan' => $data['occupation_id'] ?? $legacy->person->id_pekerjaan,
                        'updated_at' => now(),
                    ]);
                }

                $signaturePath = $legacy->ttd;
                if ($request->hasFile('signature')) {
                    if ($signaturePath && Storage::disk('public')->exists($signaturePath)) {
                        Storage::disk('public')->delete($signaturePath);
                    }
                    $signaturePath = $request->file('signature')->store('signatures', 'public');
                } elseif ($request->boolean('remove_signature')) {
                    if ($signaturePath && Storage::disk('public')->exists($signaturePath)) {
                        Storage::disk('public')->delete($signaturePath);
                    }
                    $signaturePath = null;
                }

                $legacy->update([
                    'nip' => $data['code'] ?? $legacy->nip,
                    'id_jabatan' => $data['position_id'] ?? $legacy->id_jabatan,
                    'status' => isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : $legacy->status,
                    'ttd' => $signaturePath,
                    'id_user' => $legacyUserId,
                    'updated_at' => now(),
                ]);

                return $legacy->load(['person', 'position']);
            });

            return response()->json([
                'message' => 'Data Pegawai berhasil diperbarui.',
                'data' => $updated,
            ]);
        }

        if (! ($employee instanceof Employee)) {
            $employee = Employee::findOrFail($id);
        }

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

    public function updateStatus(Request $request, $employee)
    {
        $id = $employee instanceof Employee ? $employee->id : $employee;

        if (LegacyAccess::isAvailable('ref_pegawai')) {
            $legacy = LegacyEmployee::findOrFail($id);
            $this->authorize('update', $legacy);

            $request->validate([
                'is_active' => 'required|boolean',
            ]);

            $legacy->update([
                'status' => $request->boolean('is_active') ? 1 : 0,
                'updated_at' => now(),
            ]);

            return response()->json([
                'message' => 'Status Pegawai berhasil diperbarui.',
                'data' => $legacy->load(['person', 'position']),
            ]);
        }

        if (! ($employee instanceof Employee)) {
            $employee = Employee::findOrFail($id);
        }

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

    public function destroy($employee)
    {
        $id = $employee instanceof Employee ? $employee->id : $employee;

        if (LegacyAccess::isAvailable('ref_pegawai')) {
            $legacy = LegacyEmployee::with('person')->findOrFail($id);
            $this->authorize('delete', $legacy);

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->delete();
                if ($legacy->person) {
                    $legacy->person->delete();
                }
            });

            return response()->json([
                'message' => 'Data Pegawai berhasil dihapus.',
            ]);
        }

        if (! ($employee instanceof Employee)) {
            $employee = Employee::findOrFail($id);
        }

        $this->authorize('delete', $employee);
        $employee->delete();

        return response()->json([
            'message' => 'Data Pegawai berhasil dihapus.',
        ]);
    }
}
