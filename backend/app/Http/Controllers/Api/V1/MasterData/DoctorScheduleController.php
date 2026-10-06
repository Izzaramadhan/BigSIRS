<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreDoctorScheduleRequest;
use App\Http\Requests\MasterData\UpdateDoctorScheduleRequest;
use App\Http\Resources\MasterData\DoctorScheduleResource;
use App\Models\MasterData\DoctorSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DoctorScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
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
     * Store a newly created resource in storage.
     */
    public function store(StoreDoctorScheduleRequest $request)
    {
        $validated = $request->validated();

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
    public function show(DoctorSchedule $doctorSchedule)
    {
        return new DoctorScheduleResource($doctorSchedule->load(['doctor.employee', 'doctor.specialization', 'polyclinic']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDoctorScheduleRequest $request, DoctorSchedule $doctorSchedule)
    {
        $validated = $request->validated();

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
    public function destroy(DoctorSchedule $doctorSchedule)
    {
        $doctorSchedule->delete();

        return response()->noContent();
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
