<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreDoctorScheduleRequest;
use App\Http\Requests\MasterData\UpdateDoctorScheduleRequest;
use App\Http\Resources\MasterData\DoctorScheduleResource;
use App\Models\Legacy\LegacyDoctorSchedule;
use App\Models\MasterData\DoctorSchedule;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DoctorScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (LegacyAccess::isAvailable('trx_jadwal_dokter')) {
            $query = LegacyDoctorSchedule::with(['doctor.employee.person', 'doctor.specialization', 'polyclinic']);

            if ($request->filled('doctor_id')) {
                $query->where('id_dokter', $request->doctor_id);
            }

            if ($request->filled('polyclinic_id')) {
                $query->where('id_poliklinik', $request->polyclinic_id);
            }

            if ($request->filled('day_of_week')) {
                $day = $request->day_of_week;
                $dayName = is_numeric($day)
                    ? (LegacyDoctorSchedule::$dayMap[(int) $day] ?? null)
                    : ucfirst(strtolower(trim((string) $day)));

                if ($dayName) {
                    $query->where('hari_praktik', $dayName);
                }
            }

            if ($request->filled('is_holiday')) {
                $query->where('libur', $request->boolean('is_holiday') ? 1 : 0);
            }

            if ($search = $request->get('search')) {
                $query->search($search);
            }

            $sortBy = $request->input('sort_by', $request->input('sort', 'id'));
            $sortDir = $request->input('sort_dir', $request->input('direction', 'desc'));
            $allowedSorts = [
                'id' => 'id',
                'doctor_id' => 'id_dokter',
                'polyclinic_id' => 'id_poliklinik',
                'day_of_week' => 'index_hari',
                'start_time' => 'jam_mulai',
                'end_time' => 'jam_selesai',
            ];
            $sortCol = $allowedSorts[$sortBy] ?? 'id';
            $query->orderBy($sortCol, strtolower($sortDir) === 'asc' ? 'asc' : 'desc');

            $perPage = $request->input('per_page', 10);
            if ($perPage === '-1' || $perPage === 'all') {
                return DoctorScheduleResource::collection($query->get());
            }

            return DoctorScheduleResource::collection($query->paginate((int) $perPage));
        }

        $query = DoctorSchedule::with(['doctor.employee', 'doctor.specialization', 'polyclinic']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('doctor.employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('polyclinic', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        if ($request->filled('polyclinic_id')) {
            $query->where('polyclinic_id', $request->polyclinic_id);
        }

        if ($request->filled('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        if ($request->filled('is_holiday')) {
            $query->where('is_holiday', $request->boolean('is_holiday'));
        }

        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['id', 'doctor_id', 'polyclinic_id', 'day_of_week', 'start_time', 'end_time'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = $request->input('per_page', 10);
        $schedules = $query->paginate($perPage);

        return DoctorScheduleResource::collection($schedules);
    }

    /**
     * Display a full unpaginated listing for weekly recap.
     */
    public function recap(Request $request)
    {
        if (LegacyAccess::isAvailable('trx_jadwal_dokter')) {
            $schedules = LegacyDoctorSchedule::with([
                'doctor.employee.person',
                'doctor.specialization',
                'polyclinic',
            ])
                ->orderBy('id_poliklinik')
                ->orderBy('id_dokter')
                ->orderBy('index_hari')
                ->orderBy('jam_mulai')
                ->get();

            return DoctorScheduleResource::collection($schedules);
        }

        $schedules = DoctorSchedule::with([
            'doctor.employee',
            'doctor.specialization',
            'polyclinic',
        ])
            ->orderBy('polyclinic_id')
            ->orderBy('doctor_id')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return DoctorScheduleResource::collection($schedules);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDoctorScheduleRequest $request)
    {
        $validated = $request->validated();

        if (LegacyAccess::isAvailable('trx_jadwal_dokter')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], Response::HTTP_FORBIDDEN);
            }

            if ($this->hasOverlapLegacy($validated)) {
                return response()->json([
                    'message' => 'Dokter sudah memiliki jadwal yang bertabrakan pada hari dan waktu tersebut.',
                    'errors' => [
                        'start_time' => ['Jadwal bertabrakan dengan jadwal yang sudah ada.'],
                        'end_time' => ['Jadwal bertabrakan dengan jadwal yang sudah ada.'],
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $schedule = new LegacyDoctorSchedule();
            $schedule->doctor_id = $validated['doctor_id'];
            $schedule->polyclinic_id = $validated['polyclinic_id'];
            $schedule->day_of_week = $validated['day_of_week'];
            $schedule->start_time = $validated['start_time'];
            $schedule->end_time = $validated['end_time'];
            $schedule->is_holiday = $validated['is_holiday'] ?? false;
            $schedule->online_quota = $validated['online_quota'] ?? 0;
            if ($legacyUserId) {
                $schedule->id_user = $legacyUserId;
            }
            $schedule->save();

            return (new DoctorScheduleResource($schedule->load(['doctor.employee.person', 'doctor.specialization', 'polyclinic'])))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        }

        if ($this->hasOverlap($validated)) {
            return response()->json([
                'message' => 'Dokter sudah memiliki jadwal yang bertabrakan pada hari dan waktu tersebut.',
                'errors' => [
                    'start_time' => ['Jadwal bertabrakan dengan jadwal yang sudah ada.'],
                    'end_time' => ['Jadwal bertabrakan dengan jadwal yang sudah ada.'],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $schedule = DoctorSchedule::create($validated);

        return new DoctorScheduleResource($schedule->load(['doctor.employee', 'polyclinic']));
    }

    /**
     * Display the specified resource.
     */
    public function show($doctorSchedule)
    {
        $id = $doctorSchedule instanceof \Illuminate\Database\Eloquent\Model ? $doctorSchedule->id : $doctorSchedule;

        if (LegacyAccess::isAvailable('trx_jadwal_dokter')) {
            $legacy = LegacyDoctorSchedule::with(['doctor.employee.person', 'doctor.specialization', 'polyclinic'])->findOrFail($id);
            return new DoctorScheduleResource($legacy);
        }

        if (! ($doctorSchedule instanceof DoctorSchedule)) {
            $doctorSchedule = DoctorSchedule::findOrFail($id);
        }

        return new DoctorScheduleResource($doctorSchedule->load(['doctor.employee', 'doctor.specialization', 'polyclinic']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDoctorScheduleRequest $request, $doctorSchedule)
    {
        $id = $doctorSchedule instanceof \Illuminate\Database\Eloquent\Model ? $doctorSchedule->id : $doctorSchedule;
        $validated = $request->validated();

        if (LegacyAccess::isAvailable('trx_jadwal_dokter')) {
            $schedule = LegacyDoctorSchedule::findOrFail($id);

            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], Response::HTTP_FORBIDDEN);
            }

            if ($this->hasOverlapLegacy($validated, $schedule->id)) {
                return response()->json([
                    'message' => 'Dokter sudah memiliki jadwal yang bertabrakan pada hari dan waktu tersebut.',
                    'errors' => [
                        'start_time' => ['Jadwal bertabrakan dengan jadwal yang sudah ada.'],
                        'end_time' => ['Jadwal bertabrakan dengan jadwal yang sudah ada.'],
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $schedule->doctor_id = $validated['doctor_id'];
            $schedule->polyclinic_id = $validated['polyclinic_id'];
            $schedule->day_of_week = $validated['day_of_week'];
            $schedule->start_time = $validated['start_time'];
            $schedule->end_time = $validated['end_time'];
            $schedule->is_holiday = $validated['is_holiday'] ?? false;
            $schedule->online_quota = $validated['online_quota'] ?? 0;
            if ($legacyUserId) {
                $schedule->id_user = $legacyUserId;
            }
            $schedule->save();

            return new DoctorScheduleResource($schedule->load(['doctor.employee.person', 'doctor.specialization', 'polyclinic']));
        }

        if (! ($doctorSchedule instanceof DoctorSchedule)) {
            $doctorSchedule = DoctorSchedule::findOrFail($id);
        }

        if ($this->hasOverlap($validated, $doctorSchedule->id)) {
            return response()->json([
                'message' => 'Dokter sudah memiliki jadwal yang bertabrakan pada hari dan waktu tersebut.',
                'errors' => [
                    'start_time' => ['Jadwal bertabrakan dengan jadwal yang sudah ada.'],
                    'end_time' => ['Jadwal bertabrakan dengan jadwal yang sudah ada.'],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $doctorSchedule->update($validated);

        return new DoctorScheduleResource($doctorSchedule->load(['doctor.employee', 'polyclinic']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($doctorSchedule)
    {
        $id = $doctorSchedule instanceof \Illuminate\Database\Eloquent\Model ? $doctorSchedule->id : $doctorSchedule;

        if (LegacyAccess::isAvailable('trx_jadwal_dokter')) {
            $schedule = LegacyDoctorSchedule::findOrFail($id);

            $user = request()->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], Response::HTTP_FORBIDDEN);
            }

            $schedule->performSoftDelete($legacyUserId);

            return response()->noContent();
        }

        if (! ($doctorSchedule instanceof DoctorSchedule)) {
            $doctorSchedule = DoctorSchedule::findOrFail($id);
        }

        $doctorSchedule->delete();

        return response()->noContent();
    }

    private function hasOverlapLegacy(array $data, $ignoreId = null): bool
    {
        $dayVal = $data['day_of_week'];
        $dayName = is_numeric($dayVal)
            ? (LegacyDoctorSchedule::$dayMap[(int) $dayVal] ?? 'Senin')
            : ucfirst(strtolower(trim((string) $dayVal)));

        $startTime = strlen($data['start_time']) === 5 ? $data['start_time'] . ':00' : $data['start_time'];
        $endTime = strlen($data['end_time']) === 5 ? $data['end_time'] . ':00' : $data['end_time'];

        $query = LegacyDoctorSchedule::where('id_dokter', $data['doctor_id'])
            ->where('hari_praktik', $dayName)
            ->where('jam_mulai', '<', $endTime)
            ->where('jam_selesai', '>', $startTime);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    private function hasOverlap(array $data, $ignoreId = null): bool
    {
        $query = DoctorSchedule::where('doctor_id', $data['doctor_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time']);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
