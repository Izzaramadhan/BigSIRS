<?php

namespace App\Http\Controllers\Api\V1\Registration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registration\SearchPatientRequest;
use App\Http\Requests\Registration\StorePatientRequest;
use App\Http\Requests\Registration\UpdatePatientRequest;
use App\Http\Resources\Registration\PatientAdmissionResource;
use App\Http\Resources\Registration\PatientDetailResource;
use App\Http\Resources\Registration\PatientResource;
use App\Services\Registration\PatientService;
use App\Services\Registration\PatientWriteService;
use App\Support\LegacyAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PatientController extends Controller
{
    public function __construct(
        protected PatientService $patientService,
        protected PatientWriteService $patientWriteService
    ) {
    }

    /**
     * GET /api/v1/patients/search
     */
    public function search(SearchPatientRequest $request): AnonymousResourceCollection|JsonResponse
    {
        \Illuminate\Support\Facades\Log::info('PatientController@search received request', [
            'validated' => $request->validated(),
            'query' => $request->query(),
            'user' => $request->user()?->id,
        ]);

        if (!LegacyAccess::isAvailable('ref_pasien') || !LegacyAccess::hasTable('master_person_index')) {
            return response()->json([
                'message' => 'Sumber data pasien legacy tidak tersedia atau dinonaktifkan.',
            ], 503);
        }

        $patients = $this->patientService->search($request->validated());

        \Illuminate\Support\Facades\Log::info('PatientController@search results count: ' . $patients->total());

        return PatientResource::collection($patients);
    }

    /**
     * GET /api/v1/patients/{id}
     */
    public function show(int $id): PatientDetailResource|JsonResponse
    {
        if (!LegacyAccess::isAvailable('ref_pasien') || !LegacyAccess::hasTable('master_person_index')) {
            return response()->json([
                'message' => 'Sumber data pasien legacy tidak tersedia atau dinonaktifkan.',
            ], 503);
        }

        $detail = $this->patientService->findDetail($id);
        if (!$detail) {
            return response()->json([
                'message' => 'Data pasien tidak ditemukan.',
            ], 404);
        }

        return new PatientDetailResource($detail);
    }

    /**
     * GET /api/v1/patients/{id}/admissions
     */
    public function admissions(Request $request, int $id): AnonymousResourceCollection|JsonResponse
    {
        if (!LegacyAccess::isAvailable('ref_pasien') || !LegacyAccess::hasTable('trx_admisi')) {
            return response()->json([
                'message' => 'Sumber data riwayat admisi legacy tidak tersedia atau dinonaktifkan.',
            ], 503);
        }

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $admissions = $this->patientService->admissionsHistory($id, $validated);
        if ($admissions === null) {
            return response()->json([
                'message' => 'Data pasien tidak ditemukan.',
            ], 404);
        }

        return PatientAdmissionResource::collection($admissions);
    }

    /**
     * GET /api/v1/patients/{id}/timeline
     */
    public function timeline(Request $request, int $id): JsonResponse
    {
        if (!LegacyAccess::isAvailable('ref_pasien') || !LegacyAccess::hasTable('trx_admisi')) {
            return response()->json([
                'message' => 'Sumber data riwayat pelayanan legacy tidak tersedia atau dinonaktifkan.',
            ], 503);
        }

        $timeline = $this->patientService->timeline($id, $request->all());
        if ($timeline === null) {
            return response()->json([
                'message' => 'Data pasien tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'data' => $timeline,
        ]);
    }

    /**
     * POST /api/v1/patients
     * Registrasi pasien baru ke master_person_index dan ref_pasien.
     */
    public function store(StorePatientRequest $request): JsonResponse
    {
        if (!LegacyAccess::isAvailable('ref_pasien') || !LegacyAccess::hasTable('master_person_index')) {
            return response()->json([
                'message' => 'Sumber data pasien legacy tidak tersedia atau dinonaktifkan.',
            ], 503);
        }

        $patient = $this->patientWriteService->create(
            $request->validated(),
            $request->user()?->getLegacyUserId()
        );

        $detail = $this->patientService->findDetail($patient->id);

        return (new PatientDetailResource($detail))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT /api/v1/patients/{id}
     * Pembaruan data pasien.
     */
    public function update(UpdatePatientRequest $request, int $id): JsonResponse
    {
        if (!LegacyAccess::isAvailable('ref_pasien') || !LegacyAccess::hasTable('master_person_index')) {
            return response()->json([
                'message' => 'Sumber data pasien legacy tidak tersedia atau dinonaktifkan.',
            ], 503);
        }

        $patient = $this->patientWriteService->update(
            $id,
            $request->validated(),
            $request->user()?->getLegacyUserId()
        );

        $detail = $this->patientService->findDetail($patient->id);

        return (new PatientDetailResource($detail))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * DELETE /api/v1/patients/{id}
     * Nonaktifkan / soft delete pasien jika tidak memiliki riwayat transaksi/admisi medis.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!LegacyAccess::isAvailable('ref_pasien') || !LegacyAccess::hasTable('master_person_index')) {
            return response()->json([
                'message' => 'Sumber data pasien legacy tidak tersedia atau dinonaktifkan.',
            ], 503);
        }

        $result = $this->patientWriteService->delete(
            $id,
            $request->user()?->getLegacyUserId(),
            $request->input('reason')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }
}

