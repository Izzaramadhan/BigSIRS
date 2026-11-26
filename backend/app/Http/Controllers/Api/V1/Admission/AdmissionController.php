<?php

namespace App\Http\Controllers\Api\V1\Admission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admission\StoreAdmissionRequest;
use App\Http\Requests\Admission\UpdateAdmissionRequest;
use App\Http\Resources\Admission\AdmissionResource;
use App\Models\Legacy\LegacyAdmission;
use App\Models\Legacy\LegacyDoctor;
use App\Models\Legacy\LegacyPatient;
use App\Models\Legacy\LegacyPolyclinic;
use App\Models\Legacy\LegacyVisit;
use App\Support\GuarantorValidation;
use App\Support\LegacyAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdmissionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->get('per_page', 10);
        $perPage = max(1, min(50, $perPage));

        $query = LegacyAdmission::query()
            ->with(['patient.person', 'guarantor', 'latestVisit.polyclinic', 'latestVisit.doctor.person'])
            ->where(function ($q) {
                $q->whereNull('deleted_at')
                    ->orWhere('deleted_at', '0000-00-00 00:00:00');
            });

        // Search by keyword (Nama Pasien, No. RM, NIK)
        $term = trim((string) ($request->get('q') ?? $request->get('search') ?? ''));
        if ($term !== '') {
            $like = '%' . mb_strtolower($term, 'UTF-8') . '%';
            $query->where(function ($sub) use ($like) {
                $sub->whereHas('patient.person', function ($pq) use ($like) {
                    $pq->whereRaw('LOWER(nama) LIKE ?', [$like])
                        ->orWhereRaw('CAST(no_ktp AS CHAR) LIKE ?', [$like]);
                })->orWhereHas('patient', function ($pq) use ($like) {
                    $pq->whereRaw('LOWER(no_rm) LIKE ?', [$like]);
                });
            });
        }

        // Filter Polyclinic
        if ($polyclinicId = $request->get('polyclinic_id')) {
            $query->whereHas('latestVisit', function ($vq) use ($polyclinicId) {
                $vq->where('id_poliklinik', $polyclinicId);
            });
        }

        // Filter Doctor
        if ($doctorId = $request->get('doctor_id')) {
            $query->whereHas('latestVisit', function ($vq) use ($doctorId) {
                $vq->where('id_dokter', $doctorId);
            });
        }

        // Filter Guarantor (ID atau nama penjamin)
        $guarantorFilter = trim((string) ($request->get('guarantor_id') ?? $request->get('guarantor') ?? ''));
        if ($guarantorFilter !== '' && strtolower($guarantorFilter) !== 'all') {
            if (is_numeric($guarantorFilter)) {
                $query->where('id_jenis_asuransi', (int) $guarantorFilter);
            } else {
                $likeGuar = '%' . mb_strtolower($guarantorFilter, 'UTF-8') . '%';
                $query->whereHas('guarantor', function ($gq) use ($likeGuar) {
                    $gq->whereRaw('LOWER(jenis_asuransi) LIKE ?', [$likeGuar]);
                });
            }
        }

        // Filter Date
        if ($date = $request->get('date')) {
            $query->whereDate('waktu_admisi', $date);
        }

        // Filter Status
        if ($status = $request->get('status')) {
            if ($status === 'Dibatalkan') {
                $query->where('status', '0');
            } elseif ($status !== 'all') {
                $visitStatus = match ($status) {
                    'Sedang Diperiksa' => 'Proses',
                    'Selesai' => 'Ya',
                    default => 'Tidak',
                };
                $query->where('status', '!=', '0')
                    ->whereHas('latestVisit', function ($vq) use ($visitStatus) {
                        $vq->where('status_dilayani', $visitStatus);
                    });
            }
        }

        $admissions = $query->orderByDesc('id')->paginate($perPage);

        return AdmissionResource::collection($admissions);
    }

    public function store(StoreAdmissionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Idempotency: prevent duplicate submission on retry
        $idempotencyKey = $request->header('X-Idempotency-Key') ?? $validated['idempotency_key'] ?? null;
        if ($idempotencyKey) {
            $cachedAdmissionId = Cache::get('adm_idemp_' . $idempotencyKey);
            $existingAdmission = null;
            if ($cachedAdmissionId) {
                $existingAdmission = LegacyAdmission::with(['patient.person', 'guarantor', 'latestVisit.polyclinic', 'latestVisit.doctor.person'])->find($cachedAdmissionId);
            }
            if (!$existingAdmission && \Illuminate\Support\Facades\Schema::connection('legacy')->hasColumn('trx_admisi', 'kode_booking')) {
                $existingAdmission = LegacyAdmission::with(['patient.person', 'guarantor', 'latestVisit.polyclinic', 'latestVisit.doctor.person'])
                    ->where('kode_booking', substr($idempotencyKey, 0, 50))
                    ->first();
            }
            if ($existingAdmission) {
                return (new AdmissionResource($existingAdmission))
                    ->response()
                    ->setStatusCode(200);
            }
        }

        $patient = LegacyPatient::findOrFail($validated['patient_id']);

        // Check if patient has unpaid visits/billing in legacy database
        $hasUnpaidBills = false;
        try {
            if (LegacyAccess::isAvailable('trx_visit') && LegacyAccess::hasTable('trx_visit')) {
                $hasUnpaidBills = DB::connection('legacy')
                    ->table('trx_visit')
                    ->join('trx_admisi', 'trx_admisi.id', '=', 'trx_visit.id_admisi')
                    ->where('trx_admisi.id_pasien', $patient->id)
                    ->where(function ($q) {
                        $q->where('trx_visit.cek_lunas_visit', 'belum bayar')
                            ->orWhere('trx_visit.cek_lunas_visit', 'belum_lunas');
                    })
                    ->exists();
            }
        } catch (\Throwable) {
            $hasUnpaidBills = false;
        }

        if ($hasUnpaidBills) {
            return response()->json([
                'success' => false,
                'message' => 'Pasien ini belum lunas pembayaran. Silahkan lakukan pembayaran di Billing. Atau silahkan hubungi petugas jika mengalami kesulitan.',
                'errors' => [
                    'patient_id' => [
                        'Pasien ini belum lunas pembayaran. Silahkan lakukan pembayaran di Billing. Atau silahkan hubungi petugas jika mengalami kesulitan.'
                    ]
                ]
            ], 422);
        }

        $polyclinicId = !empty($validated['polyclinic_id']) ? (int) $validated['polyclinic_id'] : null;
        if (!$polyclinicId && !empty($validated['polyclinic'])) {
            $polyQuery = $validated['polyclinic'];
            $polyclinicId = LegacyPolyclinic::where('id', $polyQuery)
                ->orWhere('nama', 'LIKE', "%{$polyQuery}%")
                ->orWhere('nama_poliklinik', 'LIKE', "%{$polyQuery}%")
                ->value('id');
        }
        if (!$polyclinicId) {
            $polyclinicId = LegacyPolyclinic::value('id') ?? 1;
        }

        $doctorId = !empty($validated['doctor_id']) ? (int) $validated['doctor_id'] : null;
        if (!$doctorId && !empty($validated['doctor'])) {
            $doctorQuery = $validated['doctor'];
            $doctorId = LegacyDoctor::where('id', $doctorQuery)
                ->orWhereHas('person', fn($q) => $q->where('nama', 'LIKE', "%{$doctorQuery}%"))
                ->value('id');
        }
        if (!$doctorId) {
            $doctorId = LegacyDoctor::value('id') ?? 1;
        }

        // Parse admission datetime
        $admissionDateTime = null;
        if (!empty($validated['admission_datetime'])) {
            try {
                $admissionDateTime = \Carbon\Carbon::parse($validated['admission_datetime'])->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                $admissionDateTime = now()->format('Y-m-d H:i:s');
            }
        } elseif (!empty($validated['admission_date'])) {
            try {
                $admissionDateTime = \Carbon\Carbon::parse($validated['admission_date'])->format('Y-m-d') . ' ' . now()->format('H:i:s');
            } catch (\Throwable) {
                $admissionDateTime = now()->format('Y-m-d H:i:s');
            }
        } else {
            $admissionDateTime = now()->format('Y-m-d H:i:s');
        }

        $guarantorId = (int) ($validated['guarantor_id'] ?? $patient->id_jenis_asuransi ?? GuarantorValidation::resolveDefaultSelfPayId());
        $insuranceNumber = $validated['insurance_number'] ?? null;
        if (GuarantorValidation::isSelfPay($guarantorId)) {
            $insuranceNumber = null;
        } elseif (empty($insuranceNumber) && $guarantorId === (int) ($patient->id_jenis_asuransi ?? 0)) {
            $insuranceNumber = $patient->no_bpjs ?? null;
        }

        $referralSourceId = !empty($validated['referral_source_id']) ? (int) $validated['referral_source_id'] : null;
        $referralName = $validated['referral_name'] ?? $validated['referral_source'] ?? null;
        if (!empty($referralSourceId) && empty($referralName)) {
            try {
                if (LegacyAccess::isAvailable('ref_asal_rujukan') && LegacyAccess::hasTable('ref_asal_rujukan')) {
                    $referralName = DB::connection('legacy')->table('ref_asal_rujukan')
                        ->where('id', $referralSourceId)
                        ->value('nama');
                }
            } catch (\Throwable) {
                $referralName = null;
            }
        }

        $familyName = $validated['family_name'] ?? $validated['emergency_contact']['name'] ?? null;
        $familyRelationship = $validated['family_relationship'] ?? $validated['emergency_contact']['relationship'] ?? null;
        $familyPhone = $validated['family_phone'] ?? $validated['emergency_contact']['phone'] ?? null;
        $familyAddress = $validated['family_address'] ?? $validated['emergency_contact']['address'] ?? null;

        $caseType = $validated['case_type'] ?? 'pemeriksaan kasus baru';
        $notes = $validated['notes'] ?? '';
        $legacyUserId = $request->user()?->getLegacyUserId() ?? 103;
        $bookingCode = $validated['kode_booking'] ?? $idempotencyKey ?? null;

        $admission = DB::connection('legacy')->transaction(function () use (
            $patient,
            $guarantorId,
            $insuranceNumber,
            $referralSourceId,
            $referralName,
            $admissionDateTime,
            $notes,
            $familyName,
            $familyRelationship,
            $familyPhone,
            $familyAddress,
            $polyclinicId,
            $doctorId,
            $caseType,
            $legacyUserId,
            $bookingCode
        ) {
            $now = now();

            $admissionPayload = [
                'id_pasien' => $patient->id,
                'id_jenis_asuransi' => $guarantorId,
                'no_bpjs' => $insuranceNumber,
                'id_asal_rujukan' => $referralSourceId,
                'asal_rujukan' => $referralName,
                'waktu_admisi' => $admissionDateTime,
                'alasan_datang' => $notes,
                'keterangan' => $notes,
                'cara_masuk' => 'poliklinik',
                'status' => '1',
                'nama_keluarga_dekat' => $familyName,
                'hubungan_keluarga_dekat' => $familyRelationship,
                'nohp_keluarga_dekat' => $familyPhone,
                'alamat_keluarga_dekat' => $familyAddress,
                'created_at' => $now,
                'id_user' => $legacyUserId,
            ];

            if ($bookingCode && \Illuminate\Support\Facades\Schema::connection('legacy')->hasColumn('trx_admisi', 'kode_booking')) {
                $admissionPayload['kode_booking'] = substr($bookingCode, 0, 50);
            }

            $admissionRecord = LegacyAdmission::create($admissionPayload);

            LegacyVisit::create([
                'id_admisi' => $admissionRecord->id,
                'id_poliklinik' => $polyclinicId,
                'id_dokter' => $doctorId,
                'kasus' => $caseType,
                'status_dilayani' => 'Tidak',
                'status_triase' => 'Tidak',
                'status_janjian' => 'Tidak',
                'status_surveilans' => 'Tidak',
                'waktu_mulai_periksa' => $admissionDateTime,
                'status' => 1,
                'status_coding' => 'belum_coding',
                'cek_farmasi' => 'Tidak',
                'cek_billing' => 'Tidak',
                'cek_lunas_visit' => 'belum bayar',
                'created_at' => $now,
                'id_user' => $legacyUserId,
            ]);

            $admissionRecord->load(['patient.person', 'guarantor', 'latestVisit.polyclinic', 'latestVisit.doctor.person']);

            return $admissionRecord;
        });

        // Audit Logging to log_user
        try {
            if (LegacyAccess::isAvailable('log_user') && LegacyAccess::hasTable('log_user')) {
                $userName = $request->user()?->username ?? 'petugas';
                $logDetail = sprintf(
                    "Pendaftaran admisi baru ID #%d (Pasien RM: %s). Poli ID #%d, Dokter ID #%d, Penjamin ID #%d, Rujukan: %s",
                    $admission->id,
                    $patient->no_rm ?? '-',
                    $polyclinicId,
                    $doctorId,
                    $guarantorId,
                    $referralName ?? '-'
                );

                DB::connection('legacy')->table('log_user')->insert([
                    'waktu' => now(),
                    'user' => $userName,
                    'role' => 'pendaftaran',
                    'log' => $logDetail,
                    'status' => '1',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal mencatat audit log admisi: ' . $e->getMessage());
        }

        Log::info('Admisi baru berhasil disimpan', [
            'admission_id' => $admission->id,
            'patient_id' => $patient->id,
            'polyclinic_id' => $polyclinicId,
            'doctor_id' => $doctorId,
        ]);

        if ($idempotencyKey) {
            Cache::put('adm_idemp_' . $idempotencyKey, $admission->id, now()->addMinutes(15));
        }

        return (new AdmissionResource($admission))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $admission = LegacyAdmission::with(['patient.person', 'guarantor', 'latestVisit.polyclinic', 'latestVisit.doctor.person'])
            ->findOrFail($id);

        return (new AdmissionResource($admission))->response();
    }

    public function update(UpdateAdmissionRequest $request, int $id): JsonResponse
    {
        $admission = LegacyAdmission::with(['patient.person', 'guarantor', 'latestVisit.polyclinic', 'latestVisit.doctor.person', 'visits'])
            ->findOrFail($id);

        $validated = $request->validated();

        $visit = null;
        if (!empty($validated['visit_id'])) {
            $visit = LegacyVisit::where('id_admisi', $admission->id)
                ->where('id', $validated['visit_id'])
                ->first();
        }
        if (!$visit) {
            $visit = $admission->latestVisit ?? $admission->visits()->latest('id')->first();
        }

        // Enforce clinical restrictions based on service status
        if ($visit) {
            $visitStatus = strtolower((string) ($visit->status_dilayani ?? 'tidak'));
            $pharmacyChecked = strtolower((string) ($visit->cek_farmasi ?? 'tidak'));
            $billingChecked = strtolower((string) ($visit->cek_billing ?? 'tidak'));

            $isLocked = ($visitStatus === 'ya' || $pharmacyChecked === 'ya' || $billingChecked === 'ya');
            $isInProgress = ($visitStatus === 'proses');

            if ($isLocked) {
                if ((int) $validated['polyclinic_id'] !== (int) $visit->id_poliklinik || (int) $validated['doctor_id'] !== (int) $visit->id_dokter) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pelayanan atau billing pasien sudah selesai. Perubahan poliklinik dan dokter tidak diperbolehkan.',
                        'errors' => [
                            'polyclinic_id' => [
                                'Pelayanan atau billing sudah selesai. Data poliklinik dan dokter dikunci.'
                            ]
                        ]
                    ], 422);
                }
            } elseif ($isInProgress) {
                if ((int) $validated['polyclinic_id'] !== (int) $visit->id_poliklinik) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pasien sedang dalam proses pemeriksaan klinis di poliklinik. Perubahan poliklinik dibatasi.',
                        'errors' => [
                            'polyclinic_id' => [
                                'Pasien sedang dalam proses pemeriksaan klinis di poliklinik. Perubahan poliklinik dibatasi.'
                            ]
                        ]
                    ], 422);
                }
            }
        }

        $legacyUserId = $request->user()?->getLegacyUserId() ?? 103;

        $oldAdmissionData = [
            'id_jenis_asuransi' => $admission->id_jenis_asuransi,
            'no_bpjs' => $admission->no_bpjs,
            'id_asal_rujukan' => $admission->id_asal_rujukan,
            'asal_rujukan' => $admission->asal_rujukan,
            'alasan_datang' => $admission->alasan_datang,
            'nama_keluarga_dekat' => $admission->nama_keluarga_dekat,
        ];

        $oldVisitData = [
            'id_poliklinik' => $visit?->id_poliklinik,
            'id_dokter' => $visit?->id_dokter,
            'kasus' => $visit?->kasus,
        ];

        DB::connection('legacy')->transaction(function () use ($validated, $admission, $visit, $legacyUserId) {
            $now = now();

            // Update existing trx_admisi record directly (NO INSERT)
            $isSelfPay = GuarantorValidation::isSelfPay((int) $validated['guarantor_id']);
            $admissionUpdates = [
                'id_jenis_asuransi' => $validated['guarantor_id'],
                'no_bpjs' => $isSelfPay ? null : ($validated['insurance_number'] ?? null),
                'id_asal_rujukan' => $validated['referral_source_id'] ?? null,
                'asal_rujukan' => $validated['referral_name'] ?? $validated['referral_source'] ?? null,
                'updated_at' => $now,
            ];

            if (isset($validated['notes'])) {
                $admissionUpdates['alasan_datang'] = $validated['notes'];
                $admissionUpdates['keterangan'] = $validated['notes'];
            }

            if (isset($validated['family_name'])) {
                $admissionUpdates['nama_keluarga_dekat'] = $validated['family_name'];
            }
            if (isset($validated['family_relationship'])) {
                $admissionUpdates['hubungan_keluarga_dekat'] = $validated['family_relationship'];
            }
            if (isset($validated['family_phone'])) {
                $admissionUpdates['nohp_keluarga_dekat'] = $validated['family_phone'];
            }
            if (isset($validated['family_address'])) {
                $admissionUpdates['alamat_keluarga_dekat'] = $validated['family_address'];
            }

            $admission->update($admissionUpdates);

            // Update existing trx_visit record directly (NO INSERT)
            if ($visit) {
                $visitUpdates = [
                    'id_poliklinik' => (int) $validated['polyclinic_id'],
                    'id_dokter' => (int) $validated['doctor_id'],
                    'updated_at' => $now,
                ];

                if (!empty($validated['case_type'])) {
                    $visitUpdates['kasus'] = $validated['case_type'];
                }

                $visit->update($visitUpdates);
            }
        });

        // Audit Logging to log_user and Log::info
        try {
            if (LegacyAccess::isAvailable('log_user') && LegacyAccess::hasTable('log_user')) {
                $userName = $request->user()?->username ?? 'petugas';
                $logDetail = sprintf(
                    "Update admisi ID #%d (Pasien RM: %s). Perubahan: Poli [%s -> %s], Dokter [%s -> %s], Asuransi [%s -> %s]. Catatan/Alasan: %s",
                    $admission->id,
                    $admission->patient?->no_rm ?? '-',
                    $oldVisitData['id_poliklinik'] ?? '-',
                    $validated['polyclinic_id'],
                    $oldVisitData['id_dokter'] ?? '-',
                    $validated['doctor_id'],
                    $oldAdmissionData['id_jenis_asuransi'] ?? '-',
                    $validated['guarantor_id'],
                    $validated['reason'] ?? $validated['notes'] ?? 'Edit admisi pasien'
                );

                DB::connection('legacy')->table('log_user')->insert([
                    'waktu' => now(),
                    'user' => $userName,
                    'role' => 'pendaftaran',
                    'log' => $logDetail,
                    'status' => '1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal mencatat log_user: ' . $e->getMessage());
        }

        Log::info('Admisi pasien berhasil diperbarui', [
            'admission_id' => $admission->id,
            'visit_id' => $visit?->id,
            'user' => $request->user()?->id,
            'old_admission' => $oldAdmissionData,
            'old_visit' => $oldVisitData,
            'new_polyclinic_id' => $validated['polyclinic_id'],
            'new_doctor_id' => $validated['doctor_id'],
            'new_guarantor_id' => $validated['guarantor_id'],
        ]);

        $admission->refresh();
        $admission->load(['patient.person', 'guarantor', 'latestVisit.polyclinic', 'latestVisit.doctor.person']);

        return (new AdmissionResource($admission))->response()->setStatusCode(200);
    }
}
