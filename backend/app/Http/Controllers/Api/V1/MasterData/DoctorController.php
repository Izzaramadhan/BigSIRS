<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreDoctorRequest;
use App\Http\Requests\MasterData\UpdateDoctorRequest;
use App\Http\Resources\MasterData\DoctorLookupResource;
use App\Http\Resources\MasterData\DoctorResource;
use App\Models\Employee;
use App\Models\Legacy\LegacyDoctor;
use App\Models\Legacy\LegacyEmployee;
use App\Models\Legacy\LegacyPerson;
use App\Models\MasterData\Doctor;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);

        if (LegacyAccess::isAvailable('ref_dokter')) {
            $withRelations = ['person'];
            if (LegacyAccess::hasTable('ref_pegawai')) {
                $withRelations[] = 'employee.person';
            }
            if (LegacyAccess::hasTable('ref_spesialisasi')) {
                $withRelations[] = 'specialization';
            }
            $query = LegacyDoctor::with($withRelations);

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->filled('specialization_id')) {
                $query->where('id_spesialisasi', $request->specialization_id);
            }

            if ($request->has('status')) {
                $isActive = $request->boolean('status');
                $query->whereIn('status', $isActive ? [1, '1'] : [0, '0']);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDesc = $request->boolean('sort_desc', true);
            $direction = $sortDesc ? 'desc' : 'asc';

            $legacySortMap = [
                'id' => 'id',
                'created_at' => 'created_at',
                'is_active' => 'status',
                'sip_number' => 'sip',
                'str_number' => 'no_str',
            ];

            if (isset($legacySortMap[$sortBy])) {
                $query->orderBy($legacySortMap[$sortBy], $direction);
            }

            return DoctorResource::collection($query->paginate($perPage));
        }

        $query = Doctor::with(['employee', 'specialization']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sip_number', 'like', "%{$search}%")
                    ->orWhere('legacy_id', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('national_id', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('specialization_id')) {
            $query->where('specialization_id', $request->specialization_id);
        }

        if ($request->has('status')) {
            $query->where('is_active', $request->boolean('status'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDesc = $request->boolean('sort_desc', true);
        $direction = $sortDesc ? 'desc' : 'asc';

        // Allowed sort columns whitelist
        $allowedSorts = ['id', 'created_at', 'is_active', 'sip_number', 'str_number'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $direction);
        }

        $doctors = $query->paginate($perPage);

        return DoctorResource::collection($doctors);
    }

    public function store(StoreDoctorRequest $request)
    {
        if (LegacyAccess::isAvailable('ref_dokter')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId() ?? 1;

            return DB::connection('legacy')->transaction(function () use ($request, $legacyUserId) {
                $data = $request->validated();
                $personData = $data['person'];
                $professionalData = $data['professional'];
                $employeeId = $request->input('employee_id');

                if ($employeeId) {
                    $employee = LegacyEmployee::with('person')->findOrFail($employeeId);
                    $mpiId = $employee->id_mpi;
                    if ($employee->person) {
                        $employee->person->update([
                            'no_ktp' => $personData['national_id'] ?? $employee->person->no_ktp,
                            'nama' => $personData['name'] ?? $employee->person->nama,
                            'gender' => $personData['gender'] ?? $employee->person->gender,
                            'tgl_lahir' => $personData['birth_date'] ?? $employee->person->tgl_lahir,
                            'tempat_lahir' => $personData['birth_place'] ?? $employee->person->tempat_lahir,
                            'alamat' => $personData['address'] ?? $employee->person->alamat,
                            'no_hp' => $personData['phone'] ?? $employee->person->no_hp,
                            'agama' => $personData['religion'] ?? $employee->person->agama,
                            'id_pendidikan' => $personData['education_id'] ?? $employee->person->id_pendidikan,
                            'id_pekerjaan' => $personData['occupation_id'] ?? $employee->person->id_pekerjaan,
                            'updated_at' => now(),
                        ]);
                    }
                } else {
                    $person = LegacyPerson::create([
                        'no_ktp' => $personData['national_id'] ?? null,
                        'nama' => $personData['name'],
                        'gender' => $personData['gender'] ?? 'L',
                        'tgl_lahir' => $personData['birth_date'] ?? null,
                        'tempat_lahir' => $personData['birth_place'] ?? null,
                        'alamat' => $personData['address'] ?? null,
                        'no_hp' => $personData['phone'] ?? null,
                        'agama' => $personData['religion'] ?? null,
                        'id_pendidikan' => $personData['education_id'] ?? null,
                        'id_pekerjaan' => $personData['occupation_id'] ?? null,
                        'jenis_person' => 'dokter',
                        'status' => 1,
                        'id_user' => $legacyUserId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $mpiId = $person->id;

                    $employee = LegacyEmployee::create([
                        'id_mpi' => $mpiId,
                        'nip' => 'DOC-' . $mpiId,
                        'status' => 1,
                        'id_user' => $legacyUserId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $signaturePath = null;
                if ($request->hasFile('signature')) {
                    $signaturePath = $request->file('signature')->store('signatures', 'public');
                }

                $doctor = LegacyDoctor::create([
                    'id_mpi' => $mpiId,
                    'id_spesialisasi' => $professionalData['specialization_id'],
                    'no_str' => $professionalData['str_number'] ?? null,
                    'sip' => $professionalData['sip_number'] ?? null,
                    'masa_berlaku' => $professionalData['sip_valid_until'] ?? null,
                    'kode_dpjp' => $professionalData['bpjs_dpjp_code'] ?? null,
                    'status' => isset($professionalData['is_active']) ? ($professionalData['is_active'] ? 1 : 0) : 1,
                    'ttd' => $signaturePath,
                    'id_user' => $legacyUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return (new DoctorResource($doctor->load(['employee.person', 'person', 'specialization'])))
                    ->response()
                    ->setStatusCode(201);
            });
        }

        return DB::transaction(function () use ($request) {
            $data = $request->validated();

            $personData = $data['person'];
            $professionalData = $data['professional'];
            $employeeId = $request->input('employee_id');

            if ($employeeId) {
                $employee = Employee::findOrFail($employeeId);
                $employee->update($personData);
            } else {
                $personData['profession'] = 'Dokter';
                $personData['is_active'] = true;
                $employee = Employee::create($personData);
            }

            // Handle signature upload
            if ($request->hasFile('signature')) {
                $path = $request->file('signature')->store('signatures', 'public');
                $professionalData['signature_path'] = $path;
            }

            $professionalData['employee_id'] = $employee->id;
            $doctor = Doctor::create($professionalData);

            return new DoctorResource($doctor->load(['employee', 'specialization']));
        });
    }

    public function show($doctor)
    {
        $id = $doctor instanceof Doctor ? $doctor->id : $doctor;
        if (LegacyAccess::isAvailable('ref_dokter')) {
            $legacy = LegacyDoctor::with(['employee.person', 'person', 'specialization'])->findOrFail($id);
            return new DoctorResource($legacy);
        }

        if (! ($doctor instanceof Doctor)) {
            $doctor = Doctor::findOrFail($id);
        }

        return new DoctorResource($doctor->load(['employee', 'specialization']));
    }

    public function update(UpdateDoctorRequest $request, $doctor)
    {
        $id = $doctor instanceof Doctor ? $doctor->id : $doctor;

        if (LegacyAccess::isAvailable('ref_dokter')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId() ?? 1;

            return DB::connection('legacy')->transaction(function () use ($request, $id, $legacyUserId) {
                $legacyDoctor = LegacyDoctor::with(['employee.person', 'person'])->findOrFail($id);
                $data = $request->validated();
                $personData = $data['person'];
                $professionalData = $data['professional'];

                // Update person
                $person = $legacyDoctor->person ?? $legacyDoctor->employee?->person;
                if ($person) {
                    $person->update([
                        'no_ktp' => $personData['national_id'] ?? $person->no_ktp,
                        'nama' => $personData['name'] ?? $person->nama,
                        'gender' => $personData['gender'] ?? $person->gender,
                        'tgl_lahir' => $personData['birth_date'] ?? $person->tgl_lahir,
                        'tempat_lahir' => $personData['birth_place'] ?? $person->tempat_lahir,
                        'alamat' => $personData['address'] ?? $person->alamat,
                        'no_hp' => $personData['phone'] ?? $person->no_hp,
                        'agama' => $personData['religion'] ?? $person->agama,
                        'id_pendidikan' => $personData['education_id'] ?? $person->id_pendidikan,
                        'id_pekerjaan' => $personData['occupation_id'] ?? $person->id_pekerjaan,
                        'updated_at' => now(),
                    ]);
                }

                $signaturePath = $legacyDoctor->ttd;
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

                $legacyDoctor->update([
                    'id_spesialisasi' => $professionalData['specialization_id'] ?? $legacyDoctor->id_spesialisasi,
                    'no_str' => $professionalData['str_number'] ?? $legacyDoctor->no_str,
                    'sip' => $professionalData['sip_number'] ?? $legacyDoctor->sip,
                    'masa_berlaku' => $professionalData['sip_valid_until'] ?? $legacyDoctor->masa_berlaku,
                    'kode_dpjp' => $professionalData['bpjs_dpjp_code'] ?? $legacyDoctor->kode_dpjp,
                    'status' => isset($professionalData['is_active']) ? ($professionalData['is_active'] ? 1 : 0) : $legacyDoctor->status,
                    'ttd' => $signaturePath,
                    'id_user' => $legacyUserId,
                    'updated_at' => now(),
                ]);

                return new DoctorResource($legacyDoctor->load(['employee.person', 'person', 'specialization']));
            });
        }

        if (! ($doctor instanceof Doctor)) {
            $doctor = Doctor::findOrFail($id);
        }

        return DB::transaction(function () use ($request, $doctor) {
            $data = $request->validated();

            $personData = $data['person'];
            $professionalData = $data['professional'];

            $employee = $doctor->employee;
            $employee->update($personData);

            if ($request->hasFile('signature')) {
                // Delete old signature
                if ($doctor->signature_path && Storage::disk('public')->exists($doctor->signature_path)) {
                    Storage::disk('public')->delete($doctor->signature_path);
                }
                $path = $request->file('signature')->store('signatures', 'public');
                $professionalData['signature_path'] = $path;
            } elseif ($request->boolean('remove_signature')) {
                if ($doctor->signature_path && Storage::disk('public')->exists($doctor->signature_path)) {
                    Storage::disk('public')->delete($doctor->signature_path);
                }
                $professionalData['signature_path'] = null;
            }

            $doctor->update($professionalData);

            return new DoctorResource($doctor->load(['employee', 'specialization']));
        });
    }

    public function destroy($doctor)
    {
        $id = $doctor instanceof Doctor ? $doctor->id : $doctor;

        if (LegacyAccess::isAvailable('ref_dokter')) {
            $legacyDoctor = LegacyDoctor::findOrFail($id);
            $legacyDoctor->delete();
            return response()->noContent();
        }

        if (! ($doctor instanceof Doctor)) {
            $doctor = Doctor::findOrFail($id);
        }
        $doctor->delete();

        return response()->noContent();
    }

    public function updateStatus(Request $request, $doctor)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $id = $doctor instanceof Doctor ? $doctor->id : $doctor;

        if (LegacyAccess::isAvailable('ref_dokter')) {
            $legacyDoctor = LegacyDoctor::findOrFail($id);
            $legacyDoctor->update([
                'status' => $validated['is_active'] ? 1 : 0,
                'updated_at' => now(),
            ]);
            return new DoctorResource($legacyDoctor->load(['employee.person', 'person', 'specialization']));
        }

        if (! ($doctor instanceof Doctor)) {
            $doctor = Doctor::findOrFail($id);
        }

        $doctor->update(['is_active' => $validated['is_active']]);

        return new DoctorResource($doctor->load(['employee', 'specialization']));
    }

    public function lookup(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_dokter')) {
            $withRelations = ['person'];
            if (LegacyAccess::hasTable('ref_pegawai')) {
                $withRelations[] = 'employee.person';
            }
            if (LegacyAccess::hasTable('ref_spesialisasi')) {
                $withRelations[] = 'specialization';
            }
            $query = LegacyDoctor::with($withRelations);

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->has('is_active')) {
                $query->whereIn('status', $request->boolean('is_active') ? [1, '1'] : [0, '0']);
            } elseif (! $request->has('include_inactive')) {
                $query->whereIn('status', [1, '1']);
            }

            if ($request->has('ids')) {
                $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
                $query->orWhereIn('id', $ids);
            }

            $metaInfo = [];

            if ($request->filled('polyclinic_id') || $request->filled('id_poliklinik')) {
                $polyclinicId = $request->input('polyclinic_id') ?? $request->input('id_poliklinik');
                $date = $request->input('date') ?? $request->input('admission_date');

                // Check total active doctors assigned to this polyclinic (regardless of day/schedule)
                $baseScheduleQuery = DB::connection('legacy')->table('trx_jadwal_dokter')
                    ->where('id_poliklinik', $polyclinicId)
                    ->where('status', '1')
                    ->where('libur', 0);

                $totalAssignedDoctors = (clone $baseScheduleQuery)->distinct('id_dokter')->count('id_dokter');

                $metaInfo['polyclinic_id'] = (int) $polyclinicId;
                $metaInfo['total_assigned_doctors'] = $totalAssignedDoctors;
                $metaInfo['has_assigned_doctors'] = $totalAssignedDoctors > 0;

                if ($totalAssignedDoctors === 0) {
                    // Condition A: No doctors assigned to this polyclinic
                    $query->whereRaw('1 = 0');
                    $metaInfo['available_on_schedule'] = 0;
                } else {
                    $scheduleQuery = clone $baseScheduleQuery;

                    if (!empty($date)) {
                        $tz = new \DateTimeZone('Asia/Jakarta');
                        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})(?:\s+(\d{2}):(\d{2})(?::(\d{2}))?)?$/', trim((string) $date), $m)) {
                            $y = $m[3];
                            $mo = $m[2];
                            $d = $m[1];
                            $timePart = isset($m[4]) ? " {$m[4]}:{$m[5]}" . (isset($m[6]) ? ":{$m[6]}" : ':00') : '';
                            $dt = new \DateTime("{$y}-{$mo}-{$d}{$timePart}", $tz);
                        } else {
                            try {
                                $dt = new \DateTime((string) $date, $tz);
                            } catch (\Exception $e) {
                                $dt = new \DateTime('now', $tz);
                            }
                        }

                        $formattedDate = $dt->format('Y-m-d');
                        $dayIndex = (int) $dt->format('N');
                        $dayNames = [
                            1 => 'Senin',
                            2 => 'Selasa',
                            3 => 'Rabu',
                            4 => 'Kamis',
                            5 => 'Jumat',
                            6 => 'Sabtu',
                            7 => 'Minggu',
                        ];
                        $dayName = $dayNames[$dayIndex] ?? null;

                        $metaInfo['query_date'] = $formattedDate;
                        $metaInfo['query_day'] = $dayName;

                        // Check holiday dates
                        $scheduleQuery->where(function ($q) use ($formattedDate) {
                            $q->whereNull('tanggal_mulai_libur')
                                ->orWhere('tanggal_mulai_libur', '>', $formattedDate)
                                ->orWhere('tanggal_selesai_libur', '<', $formattedDate);
                        });

                        // Filter strictly by day of practice
                        $scheduleQuery->where(function ($q) use ($dayName, $dayIndex) {
                            if ($dayName) {
                                $q->where('hari_praktik', $dayName);
                            }
                            if ($dayIndex) {
                                $q->orWhere('index_hari', $dayIndex);
                            }
                        });
                    }

                    $doctorIds = $scheduleQuery->pluck('id_dokter')->unique()->toArray();
                    $metaInfo['available_on_schedule'] = count($doctorIds);

                    if (empty($doctorIds)) {
                        // Condition B: Doctors are assigned to polyclinic, but none available on this schedule
                        $query->whereRaw('1 = 0');
                    } else {
                        $query->whereIn('ref_dokter.id', $doctorIds);
                    }
                }
            }

            $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
            $doctors = $query->paginate($perPage);

            $resourceCollection = DoctorLookupResource::collection($doctors);
            if (!empty($metaInfo)) {
                $resourceCollection->additional(['meta' => $metaInfo]);
            }

            return $resourceCollection;
        }

        $query = Doctor::with(['employee', 'specialization']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        } elseif (! $request->has('include_inactive')) {
            $query->where('is_active', true);
        }

        // Always include specifically requested IDs even if inactive
        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->orWhereIn('id', $ids);
        }

        $metaInfo = [];
        if ($request->filled('polyclinic_id') || $request->filled('id_poliklinik')) {
            $polyclinicId = $request->input('polyclinic_id') ?? $request->input('id_poliklinik');
            $date = $request->input('date') ?? $request->input('admission_date');

            if (\Illuminate\Support\Facades\Schema::hasTable('doctor_schedules')) {
                $baseScheduleQuery = \App\Models\MasterData\DoctorSchedule::where('polyclinic_id', $polyclinicId)
                    ->where('is_holiday', false);

                $totalAssignedDoctors = (clone $baseScheduleQuery)->distinct('doctor_id')->count('doctor_id');
                $metaInfo['polyclinic_id'] = (int) $polyclinicId;
                $metaInfo['total_assigned_doctors'] = $totalAssignedDoctors;
                $metaInfo['has_assigned_doctors'] = $totalAssignedDoctors > 0;

                if ($totalAssignedDoctors === 0) {
                    $query->whereRaw('1 = 0');
                    $metaInfo['available_on_schedule'] = 0;
                } else {
                    $scheduleQuery = clone $baseScheduleQuery;
                    if (!empty($date)) {
                        $tz = new \DateTimeZone('Asia/Jakarta');
                        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})(?:\s+(\d{2}):(\d{2})(?::(\d{2}))?)?$/', trim((string) $date), $m)) {
                            $dt = new \DateTime("{$m[3]}-{$m[2]}-{$m[1]}", $tz);
                        } else {
                            try {
                                $dt = new \DateTime((string) $date, $tz);
                            } catch (\Exception $e) {
                                $dt = new \DateTime('now', $tz);
                            }
                        }
                        $dayIndex = (int) $dt->format('N');
                        $scheduleQuery->where('day_of_week', $dayIndex);
                    }

                    $doctorIds = $scheduleQuery->pluck('doctor_id')->unique()->toArray();
                    $metaInfo['available_on_schedule'] = count($doctorIds);

                    if (empty($doctorIds)) {
                        $query->whereRaw('1 = 0');
                    } else {
                        $query->whereIn('id', $doctorIds);
                    }
                }
            }
        }

        $doctors = $query->paginate(20);
        $resourceCollection = DoctorLookupResource::collection($doctors);
        if (!empty($metaInfo)) {
            $resourceCollection->additional(['meta' => $metaInfo]);
        }

        return $resourceCollection;
    }
}
