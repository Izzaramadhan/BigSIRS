<?php

namespace App\Http\Controllers\Api\V1\Examination;

use App\Http\Controllers\Controller;
use App\Http\Resources\Examination\ExaminationResource;
use App\Models\Legacy\LegacyClinicalExamination;
use App\Models\Legacy\LegacyIcd10;
use App\Models\Legacy\LegacyLabOrder;
use App\Models\Legacy\LegacyLabOrderDetail;
use App\Models\Legacy\LegacyMedicalProcedure;
use App\Models\Legacy\LegacyPatientDiagnosis;
use App\Models\Legacy\LegacyPatientProcedure;
use App\Models\Legacy\LegacyPerson;
use App\Models\Legacy\LegacyPrescription;
use App\Models\Legacy\LegacyPrescriptionDetail;
use App\Models\Legacy\LegacyRadiologyOrder;
use App\Models\Legacy\LegacyRadiologyOrderDetail;
use App\Models\Legacy\LegacyVisit;
use App\Models\Legacy\LegacyVitalSign;
use App\Support\LegacyAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ExaminationController extends Controller
{
    /**
     * Resolve a valid active visit bound to an active admission or abort 404.
     */
    protected function findValidVisit(int $id, array $with = []): LegacyVisit
    {
        if (!in_array('admission', $with, true)) {
            $with[] = 'admission';
        }

        $visit = LegacyVisit::with($with)->findOrFail($id);

        if ((int) $visit->id_admisi <= 0 || !$visit->admission || (string) $visit->admission->status === '0' || (int) ($visit->status ?? 1) === 0) {
            abort(404, 'Data kunjungan atau admisi pasien tidak valid.');
        }

        return $visit;
    }

    /**
     * Resolve deterministic cache key when idempotency_key is provided.
     */
    protected function idempotencyCacheKey(Request $request, string $scope, int $visitId): ?string
    {
        $key = trim((string) ($request->input('idempotency_key') ?? $request->header('X-Idempotency-Key') ?? ''));
        if ($key === '') {
            return null;
        }

        return sprintf('exam_idem:%s:%d:%s', $scope, $visitId, sha1($key));
    }
    /**
     * Display a listing of outpatient examinations.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->get('per_page', 10);
        $perPage = max(1, min(100, $perPage));

        $withRelations = [
            'polyclinic',
            'doctor.person',
            'admission.patient.person',
            'admission.guarantor',
        ];
        if (LegacyAccess::hasTable('trx_antrian')) {
            $withRelations[] = 'queue';
            $withRelations[] = 'admission.queue';
        }

        $query = LegacyVisit::query()
            ->with($withRelations)
            ->where(function ($q) {
                $q->whereNull('trx_visit.deleted_at')
                    ->orWhere('trx_visit.deleted_at', '0000-00-00 00:00:00');
            })
            ->where('trx_visit.status', 1)
            ->where('trx_visit.id_admisi', '>', 0)
            ->whereHas('admission', function ($aq) {
                $aq->where('status', '!=', '0')
                    ->where(function ($del) {
                        $del->whereNull('deleted_at')
                            ->orWhere('deleted_at', '0000-00-00 00:00:00');
                    });
            });

        // Search by keyword (Nama Pasien, No. RM, NIK)
        $term = trim((string) preg_replace('/\s+/', ' ', (string) ($request->get('q') ?? $request->get('search') ?? '')));
        if ($term !== '') {
            $like = '%' . mb_strtolower($term, 'UTF-8') . '%';
            $strippedRm = str_replace('-', '', $term);
            $likeStrippedRm = $strippedRm !== '' ? '%' . mb_strtolower($strippedRm, 'UTF-8') . '%' : null;

            $query->where(function ($sub) use ($like, $likeStrippedRm) {
                $sub->whereHas('admission.patient.person', function ($pq) use ($like) {
                    $pq->whereRaw('LOWER(nama) LIKE ?', [$like])
                        ->orWhereRaw('CAST(no_ktp AS CHAR) LIKE ?', [$like]);
                })->orWhereHas('admission.patient', function ($pq) use ($like, $likeStrippedRm) {
                    $pq->whereRaw('LOWER(no_rm) LIKE ?', [$like]);
                    if ($likeStrippedRm !== null) {
                        $pq->orWhereRaw("LOWER(REPLACE(no_rm, '-', '')) LIKE ?", [$likeStrippedRm]);
                    }
                });

                if (LegacyAccess::hasTable('trx_antrian')) {
                    $sub->orWhereHas('queue', function ($qq) use ($like) {
                        $qq->whereRaw('LOWER(no_antrian) LIKE ?', [$like]);
                    });
                }
            });
        }

        // Filter Polyclinic
        if ($polyclinicId = $request->get('polyclinic_id')) {
            if ($polyclinicId !== 'all' && $polyclinicId !== 'Semua Poliklinik') {
                $query->where('id_poliklinik', $polyclinicId);
            }
        } elseif ($polyclinic = $request->get('polyclinic')) {
            if ($polyclinic !== 'all' && $polyclinic !== 'Semua Poliklinik') {
                $query->whereHas('polyclinic', function ($pq) use ($polyclinic) {
                    $pq->where('nama_poliklinik', $polyclinic)
                        ->orWhere('nama', $polyclinic);
                });
            }
        }

        // Filter Doctor
        if ($doctorId = $request->get('doctor_id')) {
            if ($doctorId !== 'all' && $doctorId !== 'Semua Dokter Pemeriksa') {
                $query->where('id_dokter', $doctorId);
            }
        } elseif ($doctor = $request->get('doctor')) {
            if ($doctor !== 'all' && $doctor !== 'Semua Dokter Pemeriksa') {
                $docLike = '%' . mb_strtolower(trim((string) $doctor), 'UTF-8') . '%';
                $query->whereHas('doctor', function ($dq) use ($docLike) {
                    $dq->whereRaw('LOWER(nama_lengkap) LIKE ?', [$docLike])
                        ->orWhereHas('person', fn($pq) => $pq->whereRaw('LOWER(nama) LIKE ?', [$docLike]));
                });
            }
        }

        // Filter Guarantor
        if ($guarantorId = $request->get('guarantor_id')) {
            if ($guarantorId !== 'all' && $guarantorId !== 'Semua Penjamin') {
                $query->whereHas('admission', fn($aq) => $aq->where('id_jenis_asuransi', $guarantorId));
            }
        } elseif ($guarantor = $request->get('guarantor')) {
            if ($guarantor !== 'all' && $guarantor !== 'Semua Penjamin') {
                $query->whereHas('admission.guarantor', function ($gq) use ($guarantor) {
                    if (strtoupper((string) $guarantor) === 'UMUM') {
                        $gq->where('type', 'UMUM')->orWhereRaw('LOWER(jenis_asuransi) LIKE ?', ['%umum%']);
                    } else {
                        $gq->where('jenis_asuransi', $guarantor);
                    }
                });
            }
        }

        // Filter Date Range (Periode Tanggal Admisi / Kunjungan)
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        if ($dateFrom || $dateTo) {
            $query->whereHas('admission', function ($aq) use ($dateFrom, $dateTo) {
                if ($dateFrom && $dateTo) {
                    $aq->whereBetween('waktu_admisi', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                } elseif ($dateFrom) {
                    $aq->where('waktu_admisi', '>=', $dateFrom . ' 00:00:00');
                } elseif ($dateTo) {
                    $aq->where('waktu_admisi', '<=', $dateTo . ' 23:59:59');
                }
            });
        } elseif ($date = $request->get('date')) {
            // Backwards compatibility with single date
            $query->where(function ($dq) use ($date) {
                $dq->whereDate('waktu_mulai_periksa', $date)
                    ->orWhereDate('created_at', $date)
                    ->orWhereHas('admission', fn($aq) => $aq->whereDate('waktu_admisi', $date));
            });
        }

        // Filter Status
        if ($status = $request->get('status')) {
            if ($status !== 'all' && $status !== 'Semua Status') {
                $visitStatus = match ($status) {
                    'Sedang Diperiksa', 'Proses' => 'Proses',
                    'Selesai', 'Ya' => 'Ya',
                    'Menunggu', 'Tidak' => 'Tidak',
                    default => null,
                };
                if ($visitStatus !== null) {
                    $query->where('status_dilayani', $visitStatus);
                }
            }
        }

        // Sorting with column whitelist
        $sortBy = $request->get('sort_by', 'id');
        $sortDirection = strtolower((string) $request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['id', 'waktu_mulai_periksa', 'created_at', 'status_dilayani', 'id_poliklinik'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderByDesc('id');
        }

        $visits = $query->paginate($perPage);

        return ExaminationResource::collection($visits);
    }

    /**
     * Display the specified examination with full clinical tabs data.
     */
    public function show(int $id): JsonResponse
    {
        $withDetail = [
            'polyclinic',
            'doctor.person',
            'admission.patient.person',
            'admission.guarantor',
        ];

        if (LegacyAccess::hasTable('trx_antrian')) {
            $withDetail[] = 'queue';
            $withDetail[] = 'admission.queue';
        }
        if (LegacyAccess::hasTable('trx_vital_sign')) {
            $withDetail[] = 'vitalSign';
        }
        if (LegacyAccess::hasTable('trx_pemeriksaan')) {
            $withDetail[] = 'clinicalExamination';
        }
        if (LegacyAccess::hasTable('trx_pemeriksaan_keperawatan')) {
            $withDetail[] = 'nursingExamination';
        }
        if (LegacyAccess::hasTable('trx_diagnosa')) {
            $withDetail[] = 'diagnoses.diagnosa';
        }
        if (LegacyAccess::hasTable('trx_icd_9')) {
            $withDetail[] = 'icd9Procedures.icd9';
        }
        if (LegacyAccess::hasTable('trx_tindakan')) {
            $withDetail[] = 'procedures.tindakan';
        }
        if (LegacyAccess::hasTable('trx_resep')) {
            $withDetail[] = 'prescriptions.details';
        }
        if (LegacyAccess::hasTable('trx_lab')) {
            $withDetail[] = 'labOrders.details.item';
        }
        if (LegacyAccess::hasTable('trx_rad')) {
            $withDetail[] = 'radiologyOrders.details.item';
        }

        $visit = $this->findValidVisit($id, $withDetail);

        // Fetch patient's previous visit history
        if ($visit->admission?->id_pasien) {
            $patientId = $visit->admission->id_pasien;
            $historyRelations = ['polyclinic', 'doctor.person', 'admission.guarantor'];
            if (LegacyAccess::hasTable('trx_vital_sign')) {
                $historyRelations[] = 'vitalSign';
            }
            if (LegacyAccess::hasTable('trx_pemeriksaan')) {
                $historyRelations[] = 'clinicalExamination';
            }
            if (LegacyAccess::hasTable('trx_diagnosa')) {
                $historyRelations[] = 'diagnoses.diagnosa';
            }
            if (LegacyAccess::hasTable('trx_tindakan')) {
                $historyRelations[] = 'procedures.tindakan';
            }
            if (LegacyAccess::hasTable('trx_resep')) {
                $historyRelations[] = 'prescriptions.details';
            }

            $historyVisits = LegacyVisit::with($historyRelations)
                ->where('id', '!=', $visit->id)
                ->whereHas('admission', fn($q) => $q->where('id_pasien', $patientId))
                ->where(function ($q) {
                    $q->whereNull('deleted_at')->orWhere('deleted_at', '0000-00-00 00:00:00');
                })
                ->orderByDesc('id')
                ->limit(10)
                ->get();

            $visit->additional_history = $historyVisits->map(function ($h) {
                $rawDate = $h->waktu_mulai_periksa ?? $h->created_at;
                $vDate = $rawDate ? date('Y-m-d', strtotime($rawDate)) : null;
                $vDateTime = $rawDate ? date('d-m-Y H:i', strtotime($rawDate)) : '-';
                $poliName = $h->polyclinic?->nama ?? $h->polyclinic?->name ?? $h->polyclinic?->nama_poliklinik ?? 'Poli Umum';
                $docName = $h->doctor?->person?->nama ?? $h->doctor?->nama ?? $h->doctor?->nama_lengkap ?? 'Dokter Pemeriksa';
                $guarantorName = $h->admission?->guarantor?->jenis_asuransi ?? $h->admission?->guarantor?->name ?? 'UMUM';

                // Subjective / Complaint
                $s = '-';
                if (LegacyAccess::hasTable('trx_pemeriksaan') && $h->relationLoaded('clinicalExamination') && ($h->clinicalExamination?->subjek_keluhan || $h->clinicalExamination?->subjektif || $h->clinicalExamination?->keluhan_utama)) {
                    $s = $h->clinicalExamination->subjek_keluhan ?? $h->clinicalExamination->subjektif ?? $h->clinicalExamination->keluhan_utama;
                } elseif ($h->admission?->alasan_datang) {
                    $s = $h->admission->alasan_datang;
                } elseif ($h->admission?->keterangan) {
                    $s = $h->admission->keterangan;
                }

                // Objective & Vitals
                $oParts = [];
                if (LegacyAccess::hasTable('trx_vital_sign') && $h->relationLoaded('vitalSign') && $h->vitalSign) {
                    $v = $h->vitalSign;
                    if ($v->tensi_sistole && $v->tensi_diastole) $oParts[] = "TD: {$v->tensi_sistole}/{$v->tensi_diastole} mmHg";
                    elseif ($v->sistole && $v->diastole) $oParts[] = "TD: {$v->sistole}/{$v->diastole} mmHg";
                    if ($v->nadi) $oParts[] = "HR: {$v->nadi} x/m";
                    if ($v->suhu) $oParts[] = "Suhu: {$v->suhu} °C";
                    if ($v->pernafasan) $oParts[] = "RR: {$v->pernafasan} x/m";
                    if ($v->berat_badan) $oParts[] = "BB: {$v->berat_badan} kg";
                }
                if (LegacyAccess::hasTable('trx_pemeriksaan') && $h->relationLoaded('clinicalExamination') && !empty($h->clinicalExamination?->pemeriksaan_fisik)) {
                    $oParts[] = $h->clinicalExamination->pemeriksaan_fisik;
                }
                $o = !empty($oParts) ? implode(', ', $oParts) : '-';

                // Assessment & Diagnoses
                $diagNames = [];
                if (LegacyAccess::hasTable('trx_diagnosa') && $h->relationLoaded('diagnoses')) {
                    $diagNames = $h->diagnoses->map(fn($d) => $d->diagnosa?->nama ?? '')->filter()->values()->all();
                }
                $primaryDiag = !empty($diagNames) ? $diagNames[0] : ($h->admission?->diagnosa_awal ?? '-');
                $assessment = '-';
                if (LegacyAccess::hasTable('trx_pemeriksaan') && $h->relationLoaded('clinicalExamination') && (!empty($h->clinicalExamination?->planning) || !empty($h->clinicalExamination?->rencana_terapi))) {
                    $assessment = $h->clinicalExamination->planning ?? $h->clinicalExamination->rencana_terapi;
                } elseif (!empty($diagNames)) {
                    $assessment = implode('; ', $diagNames);
                }

                // Procedures
                $procNames = [];
                if (LegacyAccess::hasTable('trx_tindakan') && $h->relationLoaded('procedures')) {
                    $procNames = $h->procedures->map(fn($p) => $p->tindakan?->nama ?? $p->tindakan?->name ?? '')->filter()->values()->all();
                }

                // Prescriptions
                $rxNames = [];
                if (LegacyAccess::hasTable('trx_resep') && $h->relationLoaded('prescriptions')) {
                    foreach ($h->prescriptions as $rx) {
                        if ($rx->relationLoaded('details')) {
                            foreach ($rx->details as $d) {
                                $rxNames[] = $d->nama_obat ?? $d->name ?? 'Obat';
                            }
                        }
                    }
                }

                $notes = '-';
                if (LegacyAccess::hasTable('trx_pemeriksaan') && $h->relationLoaded('clinicalExamination') && !empty($h->clinicalExamination?->edukasi)) {
                    $notes = $h->clinicalExamination->edukasi;
                }

                return [
                    'id' => (int) $h->id,
                    'visit_id' => (int) $h->id,
                    'visit_date' => $vDate,
                    'visit_datetime' => $vDateTime,
                    'units' => [$poliName],
                    'polyclinic' => $poliName,
                    'doctor' => $docName,
                    'guarantor' => $guarantorName,
                    'subjective' => $s,
                    'objective' => $o,
                    'diagnosis' => $primaryDiag,
                    'diagnoses' => $diagNames,
                    'assessment' => $assessment,
                    'procedures' => $procNames,
                    'prescriptions' => $rxNames,
                    'notes' => $notes,
                    'status' => $h->status_dilayani === 'Ya' ? 'Selesai' : 'Sedang Diperiksa',
                ];
            })->all();
        }

        return (new ExaminationResource($visit))->response();
    }

    /**
     * Update visit status (e.g. Mulai Periksa -> Proses, Selesai -> Ya).
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:Menunggu,Sedang Diperiksa,Selesai,Tidak,Proses,Ya',
        ]);

        $statusValue = match ($validated['status']) {
            'Sedang Diperiksa', 'Proses' => 'Proses',
            'Selesai', 'Ya' => 'Ya',
            default => 'Tidak',
        };

        $visit = $this->findValidVisit($id);
        $legacyUserId = $request->user()?->getLegacyUserId() ?? 103;

        DB::connection('legacy')->transaction(function () use ($visit, $statusValue, $legacyUserId) {
            $updates = [
                'status_dilayani' => $statusValue,
                'updated_at' => now(),
                'id_user' => $legacyUserId,
            ];

            if ($statusValue === 'Proses' && empty($visit->waktu_mulai_periksa)) {
                $updates['waktu_mulai_periksa'] = now();
            } elseif ($statusValue === 'Ya') {
                if (empty($visit->waktu_mulai_periksa)) {
                    $updates['waktu_mulai_periksa'] = now();
                }
                $updates['waktu_selesai_periksa'] = now();
            }

            $visit->update($updates);
        });

        // Audit log
        $this->logActivity($request, sprintf("Memperbarui status pemeriksaan visit ID #%d menjadi '%s'", $visit->id, $statusValue));

        return response()->json([
            'success' => true,
            'message' => 'Status pemeriksaan berhasil diperbarui.',
            'data' => [
                'id' => $visit->id,
                'status' => match ($statusValue) {
                    'Proses' => 'Sedang Diperiksa',
                    'Ya' => 'Selesai',
                    default => 'Menunggu'
                }
            ]
        ]);
    }

    /**
     * Save or update patient vitals for this visit.
     */
    public function saveVitals(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'systole' => 'nullable|numeric',
            'sistole' => 'nullable|numeric',
            'diastole' => 'nullable|numeric',
            'blood_pressure' => 'nullable|string',
            'nilai_tensi' => 'nullable|string',
            'heart_rate' => 'nullable|numeric',
            'nadi' => 'nullable|numeric',
            'detak_nadi' => 'nullable|numeric',
            'respiratory_rate' => 'nullable|numeric',
            'pernafasan' => 'nullable|numeric',
            'pernapasan' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',
            'suhu' => 'nullable|numeric',
            'suhu_tubuh' => 'nullable|numeric',
            'spo2' => 'nullable|numeric',
            'saturasi_o2' => 'nullable|numeric',
            'weight' => 'nullable|numeric',
            'berat_badan' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'tinggi_badan' => 'nullable|numeric',
            'gcs' => 'nullable|string',
            'skor_gcs' => 'nullable|string',
            'waist_circumference' => 'nullable|numeric',
            'lingkar_pinggang' => 'nullable|numeric',
            'kesadaran' => 'nullable|string',
        ]);

        $visit = $this->findValidVisit($id);
        $legacyUserId = $request->user()?->getLegacyUserId() ?? 103;

        $vitals = DB::connection('legacy')->transaction(function () use ($visit, $validated, $legacyUserId) {
            $vital = LegacyVitalSign::where('id_visit', $visit->id)->first();

            $hasSystole = array_key_exists('systole', $validated) || array_key_exists('sistole', $validated);
            $hasDiastole = array_key_exists('diastole', $validated);
            $hasBp = array_key_exists('blood_pressure', $validated) || array_key_exists('nilai_tensi', $validated);

            $systole = $hasSystole
                ? (float) ($validated['systole'] ?? $validated['sistole'] ?? 0)
                : (float) ($vital?->sistole ?? 0);
            $diastole = $hasDiastole
                ? (float) ($validated['diastole'] ?? 0)
                : (float) ($vital?->diastole ?? 0);
            $bp = $hasBp ? ($validated['blood_pressure'] ?? $validated['nilai_tensi'] ?? null) : null;
            if ((!$hasSystole || !$hasDiastole || $systole === 0.0 || $diastole === 0.0) && !empty($bp)) {
                $parts = explode('/', (string) $bp);
                if (count($parts) === 2) {
                    if (!$hasSystole || $systole === 0.0) {
                        $systole = (float) trim($parts[0]);
                    }
                    if (!$hasDiastole || $diastole === 0.0) {
                        $diastole = (float) trim($parts[1]);
                    }
                }
            }

            $pickNumeric = function (array $keys, $existing = 0) use ($validated): float {
                foreach ($keys as $k) {
                    if (array_key_exists($k, $validated)) {
                        return (float) ($validated[$k] ?? 0);
                    }
                }
                return (float) ($existing ?? 0);
            };

            $payload = [
                'id_visit' => $visit->id,
                'sistole' => $systole,
                'diastole' => $diastole,
                'nilai_tensi' => $systole > 0 && $diastole > 0 ? "{$systole}/{$diastole}" : ($vital?->nilai_tensi ?? null),
                'nadi' => $pickNumeric(['heart_rate', 'nadi', 'detak_nadi'], $vital?->nadi ?? 0),
                'pernafasan' => $pickNumeric(['respiratory_rate', 'pernafasan', 'pernapasan'], $vital?->pernafasan ?? 0),
                'suhu' => $pickNumeric(['temperature', 'suhu', 'suhu_tubuh'], $vital?->suhu ?? 0),
                'spo2' => $pickNumeric(['spo2', 'saturasi_o2'], $vital?->spo2 ?? 0),
                'berat_badan' => $pickNumeric(['weight', 'berat_badan'], $vital?->berat_badan ?? 0),
                'tinggi_badan' => $pickNumeric(['height', 'tinggi_badan'], $vital?->tinggi_badan ?? 0),
                'skor_gcs' => (array_key_exists('gcs', $validated) || array_key_exists('skor_gcs', $validated))
                    ? (($validated['gcs'] ?? $validated['skor_gcs']) !== null && ($validated['gcs'] ?? $validated['skor_gcs']) !== ''
                        ? (float) filter_var($validated['gcs'] ?? $validated['skor_gcs'], FILTER_SANITIZE_NUMBER_INT)
                        : null)
                    : ($vital?->skor_gcs ?? null),
                'lingkar_pinggang' => (array_key_exists('waist_circumference', $validated) || array_key_exists('lingkar_pinggang', $validated))
                    ? (($validated['waist_circumference'] ?? $validated['lingkar_pinggang']) !== null && ($validated['waist_circumference'] ?? $validated['lingkar_pinggang']) !== ''
                        ? (float) ($validated['waist_circumference'] ?? $validated['lingkar_pinggang'])
                        : null)
                    : ($vital?->lingkar_pinggang ?? null),
                'status' => 1,
                'id_user' => $legacyUserId,
                'updated_at' => now(),
            ];

            if ($vital) {
                $vital->update($payload);
                return $vital;
            }

            $payload['created_at'] = now();
            $payload['create_time_stamp'] = now();
            return LegacyVitalSign::create($payload);
        });

        $this->logActivity($request, sprintf("Menyimpan tanda vital visit ID #%d", $visit->id));

        return response()->json([
            'success' => true,
            'message' => 'Tanda-tanda vital berhasil disimpan.',
            'data' => $vitals
        ]);
    }

    /**
     * Save clinical note / examination (SOAP, physical exam, allergy).
     */
    public function saveClinical(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'chief_complaint' => 'nullable|string',
            'anamnesa' => 'nullable|string',
            'subjective' => 'nullable|string',
            'catatan_s' => 'nullable|string',
            'subjektif' => 'nullable|string',
            'objective' => 'nullable|string',
            'catatan_o' => 'nullable|string',
            'objektif' => 'nullable|string',
            'assessment' => 'nullable|string',
            'catatan_a' => 'nullable|string',
            'plan' => 'nullable|string',
            'catatan_p' => 'nullable|string',
            'planning' => 'nullable|string',
            'physical_general' => 'nullable|string',
            'pemeriksaan_fisik' => 'nullable|string',
            'physical_head' => 'nullable|string',
            'physical_neck' => 'nullable|string',
            'physical_chest' => 'nullable|string',
            'physical_abdomen' => 'nullable|string',
            'physical_extremities' => 'nullable|string',
            'physical_localis' => 'nullable|string',
            'allergy_note' => 'nullable|string',
            'ket_riwayat_alergi_obat_makanan' => 'nullable|string',
        ]);

        $visit = $this->findValidVisit($id, ['admission.patient']);
        $legacyUserId = $request->user()?->getLegacyUserId() ?? 103;

        $exam = DB::connection('legacy')->transaction(function () use ($visit, $validated, $legacyUserId) {
            $clinical = LegacyClinicalExamination::where('id_visit', $visit->id)->first();
            $chiefComplaint = $validated['chief_complaint'] ?? $validated['anamnesa'] ?? null;
            $subjective = $validated['subjective'] ?? $validated['catatan_s'] ?? $validated['subjektif'] ?? null;
            $objective = $validated['objective'] ?? $validated['catatan_o'] ?? $validated['objektif'] ?? null;
            $assessment = $validated['assessment'] ?? $validated['catatan_a'] ?? null;
            $planning = $validated['plan'] ?? $validated['catatan_p'] ?? $validated['planning'] ?? null;
            $physGen = $validated['physical_general'] ?? $validated['pemeriksaan_fisik'] ?? null;
            $hasAllergyInput = array_key_exists('allergy_note', $validated) || array_key_exists('ket_riwayat_alergi_obat_makanan', $validated);
            $allergyText = trim((string) ($validated['allergy_note'] ?? $validated['ket_riwayat_alergi_obat_makanan'] ?? ''));
            $hasAllergy = $allergyText !== '' && strtolower($allergyText) !== 'tidak ada' && $allergyText !== '-';

            $payload = [
                'id_visit' => $visit->id,
                'subjek_keluhan' => $chiefComplaint ?? ($clinical->subjek_keluhan ?? ''),
                'subjektif' => $subjective ?? ($clinical->subjektif ?? ''),
                'objektif' => $objective ?? ($clinical->objektif ?? ''),
                'planning' => $planning ?? ($clinical->planning ?? ''),
                'keadaan_umum' => $physGen ?? ($clinical->keadaan_umum ?? null),
                'kepala' => $validated['physical_head'] ?? ($clinical->kepala ?? null),
                'leher' => $validated['physical_neck'] ?? ($clinical->leher ?? null),
                'thorax' => $validated['physical_chest'] ?? ($clinical->thorax ?? null),
                'abdomen' => $validated['physical_abdomen'] ?? ($clinical->abdomen ?? null),
                'ekstremitas_all' => $validated['physical_extremities'] ?? ($clinical->ekstremitas_all ?? null),
                'status' => 1,
                'id_user' => $legacyUserId,
                'updated_at' => now(),
            ];

            if ($assessment !== null && Schema::connection('legacy')->hasColumn('trx_pemeriksaan', 'ket_diagnosis_mulut')) {
                $payload['ket_diagnosis_mulut'] = $assessment;
            }
            if (array_key_exists('physical_localis', $validated) && Schema::connection('legacy')->hasColumn('trx_pemeriksaan', 'ket_lain')) {
                $payload['ket_lain'] = $validated['physical_localis'];
            }
            if ($hasAllergyInput) {
                $payload['riwayat_alergi_obat_makanan'] = $hasAllergy ? 'Ada' : 'Tidak';
                $payload['ket_riwayat_alergi_obat_makanan'] = $allergyText;
            } elseif (!$clinical) {
                $payload['riwayat_alergi_obat_makanan'] = 'Tidak';
                $payload['ket_riwayat_alergi_obat_makanan'] = '';
            }

            if ($clinical) {
                $clinical->update($payload);
            } else {
                $payload['created_at'] = now();
                $clinical = LegacyClinicalExamination::create($payload);
            }

            // Also synchronize patient master record in ref_pasien if allergy was updated
            if ($hasAllergyInput) {
                $patient = $visit->admission?->patient;
                if ($patient) {
                    $patient->update([
                        'alergi' => $hasAllergy ? 'ada' : 'tidak',
                        'ket_alergi' => $allergyText,
                        'id_user' => $legacyUserId,
                        'updated_at' => now(),
                    ]);
                }
            }

            return $clinical;
        });

        $this->logActivity($request, sprintf("Menyimpan catatan klinis visit ID #%d", $visit->id));

        return response()->json([
            'success' => true,
            'message' => 'Catatan klinis pemeriksaan berhasil disimpan.',
            'data' => $exam
        ]);
    }

    /**
     * Get allergy information for a visit / patient.
     */
    public function getAllergies(Request $request, int $id): JsonResponse
    {
        $visit = LegacyVisit::with(['admission.patient.person', 'clinicalExamination'])->findOrFail($id);

        if ($visit->id_admisi <= 0 || !$visit->admission) {
            abort(404, 'Data kunjungan atau admisi pasien tidak valid.');
        }

        $patient = $visit->admission->patient;
        $person = $patient?->person;
        $clinical = $visit->clinicalExamination;

        $allergyNote = '';
        $hasAllergy = false;
        $status = 'tidak';

        if ($clinical && ($clinical->riwayat_alergi_obat_makanan === 'Ada' || !empty($clinical->ket_riwayat_alergi_obat_makanan))) {
            $allergyNote = $clinical->ket_riwayat_alergi_obat_makanan ?: ($clinical->reaksi_alergi ?: '');
            $hasAllergy = true;
            $status = 'ada';
        } elseif ($patient && ($patient->alergi === 'ada' || !empty($patient->ket_alergi))) {
            $allergyNote = $patient->ket_alergi ?: '';
            $hasAllergy = true;
            $status = $patient->alergi ?: 'ada';
        } elseif ($person && (!empty($person->alergi_obat) || !empty($person->alergi_makanan) || !empty($person->alergi_lainnya))) {
            $parts = array_filter([$person->alergi_obat, $person->alergi_makanan, $person->alergi_lainnya]);
            $allergyNote = implode(', ', $parts);
            $hasAllergy = !empty($allergyNote);
            $status = $hasAllergy ? 'ada' : 'tidak';
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'visit_id' => (int) $visit->id,
                'admission_id' => (int) $visit->id_admisi,
                'patient_id' => $patient ? (int) $patient->id : null,
                'patient_name' => $person?->nama ?? null,
                'has_allergy' => $hasAllergy,
                'status' => $status,
                'allergy_note' => $allergyNote,
                'notes' => $allergyNote,
            ],
        ]);
    }

    /**
     * Store or update patient allergy notes from Tab Alergi.
     */
    public function saveAllergies(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'allergy_note' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'has_allergy' => 'nullable',
        ]);

        $visit = LegacyVisit::with(['admission.patient.person'])->findOrFail($id);

        if ($visit->id_admisi <= 0 || !$visit->admission) {
            abort(404, 'Data kunjungan atau admisi pasien tidak valid.');
        }

        $allergyText = trim((string) ($validated['allergy_note'] ?? $validated['notes'] ?? ''));
        $hasAllergy = $allergyText !== '' && strtolower($allergyText) !== 'tidak ada' && $allergyText !== '-';
        $patientStatus = $hasAllergy ? 'ada' : 'tidak';
        $examStatus = $hasAllergy ? 'Ada' : 'Tidak';

        $legacyUserId = $request->user()?->getLegacyUserId() ?? $request->user()?->id ?? 1;

        DB::connection('legacy')->transaction(function () use ($visit, $allergyText, $patientStatus, $examStatus, $legacyUserId) {
            // 1. Update / create clinical examination row in trx_pemeriksaan
            $clinical = LegacyClinicalExamination::where('id_visit', $visit->id)->first();
            if ($clinical) {
                $clinical->update([
                    'riwayat_alergi_obat_makanan' => $examStatus,
                    'ket_riwayat_alergi_obat_makanan' => $allergyText,
                    'id_user' => $legacyUserId,
                    'updated_at' => now(),
                ]);
            } else {
                $payload = [
                    'id_visit' => $visit->id,
                    'riwayat_alergi_obat_makanan' => $examStatus,
                    'ket_riwayat_alergi_obat_makanan' => $allergyText,
                    'status' => 1,
                    'id_user' => $legacyUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                LegacyClinicalExamination::create($payload);
            }

            // 2. Update master patient record in ref_pasien
            $patient = $visit->admission?->patient;
            if ($patient) {
                $patient->update([
                    'alergi' => $patientStatus,
                    'ket_alergi' => $allergyText,
                    'id_user' => $legacyUserId,
                    'updated_at' => now(),
                ]);

                // 3. Update master_person_index if column exists
                if ($patient->id_mpi && LegacyAccess::hasTable('master_person_index')) {
                    $person = LegacyPerson::find($patient->id_mpi);
                    if ($person && Schema::connection('legacy')->hasColumn('master_person_index', 'alergi_lainnya')) {
                        $person->update([
                            'alergi_lainnya' => $allergyText,
                        ]);
                    }
                }
            }
        });

        $this->logActivity($request, sprintf("Menyimpan data alergi pasien untuk visit ID #%d", $visit->id));

        return response()->json([
            'status' => 'success',
            'message' => 'Data alergi berhasil disimpan.',
            'data' => [
                'visit_id' => (int) $visit->id,
                'patient_id' => $visit->admission?->id_pasien ? (int) $visit->admission->id_pasien : null,
                'has_allergy' => $hasAllergy,
                'status' => $patientStatus,
                'allergy_note' => $allergyText,
                'notes' => $allergyText,
            ],
        ]);
    }

    /**
     * Add ICD-10 diagnosis to visit.
     */
    public function addDiagnosis(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'diagnosis_id' => 'required|integer|min:1',
            'type' => 'nullable|string|in:Primer,Sekunder,Utama',
            'case_type' => 'nullable|string|in:Kasus Baru,Kasus Lama',
            'notes' => 'nullable|string',
        ]);

        $visit = $this->findValidVisit($id);

        if (LegacyAccess::hasTable('ref_diagnosa') && !LegacyIcd10::where('id', $validated['diagnosis_id'])->exists()) {
            return response()->json([
                'message' => 'Diagnosis ICD-10 tidak ditemukan di master data.',
                'errors' => ['diagnosis_id' => ['Diagnosis ICD-10 tidak valid atau tidak aktif.']],
            ], 422);
        }

        $duplicate = LegacyPatientDiagnosis::where('id_visit', $visit->id)
            ->where('id_diagnosa', $validated['diagnosis_id'])
            ->where('status', 1)
            ->first();
        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'Diagnosis tersebut sudah tercatat pada kunjungan ini.',
            ], 409);
        }

        $legacyUserId = $request->user()?->getLegacyUserId() ?? 103;

        $category = in_array($validated['type'] ?? '', ['Primer', 'Utama'], true) ? 'Primer' : 'Sekunder';
        $caseType = $validated['case_type'] ?? 'Kasus Baru';

        $diag = DB::connection('legacy')->transaction(function () use ($visit, $validated, $category, $caseType, $legacyUserId) {
            return LegacyPatientDiagnosis::create([
                'id_visit' => $visit->id,
                'id_diagnosa' => $validated['diagnosis_id'],
                'kategori' => $category,
                'kasus' => $caseType,
                'group' => 'umum',
                'catatan' => $validated['notes'] ?? '',
                'status' => 1,
                'id_user' => $legacyUserId,
                'created_at' => now(),
                'create_time_stamp' => now(),
            ]);
        });

        $diag->load('diagnosa');

        $this->logActivity($request, sprintf("Menambahkan diagnosis ICD-10 #%d ke visit #%d", $validated['diagnosis_id'], $visit->id));

        return response()->json([
            'success' => true,
            'message' => 'Diagnosis berhasil ditambahkan.',
            'data' => [
                'id' => $diag->id,
                'code' => $diag->diagnosa?->code ?? '',
                'name' => $diag->diagnosa?->nama ?? '',
                'type' => $diag->kategori === 'Primer' ? 'Utama' : 'Sekunder',
                'case_type' => $diag->kasus,
                'notes' => $diag->catatan,
            ]
        ], 201);
    }

    /**
     * Delete diagnosis from visit.
     */
    public function deleteDiagnosis(Request $request, int $id, int $diagnosisId): JsonResponse
    {
        $visit = $this->findValidVisit($id);
        $diag = LegacyPatientDiagnosis::where('id_visit', $visit->id)->findOrFail($diagnosisId);
        $diag->update(['status' => 0, 'deleted_at' => now()]);

        $this->logActivity($request, sprintf("Menghapus diagnosis #%d dari visit #%d", $diagnosisId, $visit->id));

        return response()->json([
            'success' => true,
            'message' => 'Diagnosis berhasil dihapus.'
        ]);
    }

    /**
     * Add procedure/action (tindakan) to visit.
     */
    public function addProcedure(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'procedure_id' => 'required|integer|min:1',
            'qty' => 'nullable|integer|min:1',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'idempotency_key' => 'nullable|string|max:100',
        ]);

        $visit = $this->findValidVisit($id);

        if (LegacyAccess::hasTable('ref_tarif_tindakan') && !LegacyMedicalProcedure::where('id', $validated['procedure_id'])->exists()) {
            return response()->json([
                'message' => 'Tindakan medis tidak ditemukan di master data.',
                'errors' => ['procedure_id' => ['Tindakan medis tidak valid atau tidak ditemukan.']],
            ], 422);
        }

        $idemKey = $this->idempotencyCacheKey($request, 'procedure', $visit->id);
        if ($idemKey && Cache::has($idemKey)) {
            return response()->json(Cache::get($idemKey), 200);
        }

        $legacyUserId = $request->user()?->getLegacyUserId() ?? 103;

        $proc = DB::connection('legacy')->transaction(function () use ($visit, $validated, $legacyUserId) {
            return LegacyPatientProcedure::create([
                'id_visit' => $visit->id,
                'id_tindakan' => $validated['procedure_id'],
                'qty' => $validated['qty'] ?? 1,
                'diskon' => $validated['discount'] ?? 0,
                'keterangan' => $validated['notes'] ?? '',
                'group' => 'umum',
                'status' => 1,
                'id_user' => $legacyUserId,
                'created_at' => now(),
                'create_time_stamp' => now(),
            ]);
        });

        $proc->load('tindakan');

        $this->logActivity($request, sprintf("Menambahkan tindakan #%d ke visit #%d", $validated['procedure_id'], $visit->id));

        $tariff = $proc->tindakan;
        $price = $tariff ? (int) $tariff->harga : 0;
        $qty = (int) $proc->qty;
        $discount = (int) $proc->diskon;
        $subtotal = max(0, ($price * $qty) - $discount);

        $body = [
            'success' => true,
            'message' => 'Tindakan berhasil ditambahkan.',
            'data' => [
                'id' => $proc->id,
                'procedure_id' => $tariff ? (int) $tariff->id : (int) $proc->id_tindakan,
                'code' => $tariff->kode ?? '',
                'name' => $tariff->nama ?? '',
                'price' => $price,
                'qty' => $qty,
                'discount' => $discount,
                'subtotal' => $subtotal,
                'notes' => $proc->keterangan ?? '',
            ]
        ];

        if ($idemKey) {
            Cache::put($idemKey, $body, now()->addMinutes(5));
        }

        return response()->json($body, 201);
    }

    /**
     * Delete procedure from visit.
     */
    public function deleteProcedure(Request $request, int $id, int $procedureId): JsonResponse
    {
        $visit = $this->findValidVisit($id);
        $proc = LegacyPatientProcedure::where('id_visit', $visit->id)->findOrFail($procedureId);
        $proc->update(['status' => 0, 'deleted_at' => now()]);

        $this->logActivity($request, sprintf("Menghapus tindakan #%d dari visit #%d", $procedureId, $visit->id));

        return response()->json([
            'success' => true,
            'message' => 'Tindakan berhasil dihapus.'
        ]);
    }

    /**
     * Store prescription order for visit.
     */
    public function storePrescription(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.medicine_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.signa' => 'nullable|string',
            'items.*.instruction' => 'nullable|string',
            'items.*.notes' => 'nullable|string',
            'idempotency_key' => 'nullable|string|max:100',
        ]);

        $visit = $this->findValidVisit($id);
        $idemKey = $this->idempotencyCacheKey($request, 'prescription', $visit->id);
        if ($idemKey && Cache::has($idemKey)) {
            return response()->json(Cache::get($idemKey), 200);
        }

        $legacyUserId = $request->user()?->getLegacyUserId() ?? 103;

        $prescription = DB::connection('legacy')->transaction(function () use ($visit, $validated, $legacyUserId) {
            $rx = LegacyPrescription::create([
                'id_visit' => $visit->id,
                'dokter_pengirim' => $visit->id_dokter,
                'status_resep' => 'order',
                'status' => 1,
                'id_user' => $legacyUserId,
                'created_at' => now(),
                'create_time_stamp' => now(),
            ]);

            foreach ($validated['items'] as $item) {
                LegacyPrescriptionDetail::create([
                    'id_resep' => $rx->id,
                    'nama_obat' => $item['medicine_name'],
                    'jml_item' => $item['quantity'],
                    'signa' => $item['signa'] ?? '',
                    'aturan_pakai' => $item['instruction'] ?? '',
                    'catatan' => $item['notes'] ?? '',
                    'approve' => 1,
                    'status' => 1,
                    'id_user' => $legacyUserId,
                    'created_at' => now(),
                ]);
            }

            return $rx->load('details');
        });

        $this->logActivity($request, sprintf("Menyimpan order resep baru untuk visit ID #%d", $visit->id));

        $body = [
            'success' => true,
            'message' => 'Resep berhasil disimpan.',
            'data' => $prescription
        ];
        if ($idemKey) {
            Cache::put($idemKey, $body, now()->addMinutes(5));
        }

        return response()->json($body, 201);
    }

    /**
     * Store laboratory order for visit.
     */
    public function storeLabOrder(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.item_id' => 'nullable|integer',
            'notes' => 'nullable|string',
            'idempotency_key' => 'nullable|string|max:100',
        ]);

        $visit = $this->findValidVisit($id);
        $idemKey = $this->idempotencyCacheKey($request, 'lab', $visit->id);
        if ($idemKey && Cache::has($idemKey)) {
            return response()->json(Cache::get($idemKey), 200);
        }

        $legacyUserId = $request->user()?->getLegacyUserId() ?? 103;

        $lab = DB::connection('legacy')->transaction(function () use ($visit, $validated, $legacyUserId) {
            $order = LegacyLabOrder::create([
                'id_visit' => $visit->id,
                'dokter_pengirim' => $visit->id_dokter,
                'status_pemeriksaan' => 'proses',
                'keterangan' => $validated['notes'] ?? null,
                'status' => 1,
                'id_user' => $legacyUserId,
                'created_at' => now(),
                'create_time_stamp' => now(),
            ]);

            foreach ($validated['items'] as $item) {
                LegacyLabOrderDetail::create([
                    'id_trx_lab' => $order->id,
                    'id_item_lab' => $item['item_id'] ?? 0,
                    'nama_grup_lab' => $item['name'],
                    'status_pemeriksaan' => 'proses',
                    'status' => 1,
                    'id_user' => $legacyUserId,
                    'created_at' => now(),
                ]);
            }

            return $order->load('details');
        });

        $this->logActivity($request, sprintf("Menyimpan order laboratorium untuk visit ID #%d", $visit->id));

        $body = [
            'success' => true,
            'message' => 'Order laboratorium berhasil disimpan.',
            'data' => $lab
        ];
        if ($idemKey) {
            Cache::put($idemKey, $body, now()->addMinutes(5));
        }

        return response()->json($body, 201);
    }

    /**
     * Store radiology order for visit.
     */
    public function storeRadiologyOrder(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.item_id' => 'nullable|integer',
            'notes' => 'nullable|string',
            'idempotency_key' => 'nullable|string|max:100',
        ]);

        $visit = $this->findValidVisit($id);
        $idemKey = $this->idempotencyCacheKey($request, 'radiology', $visit->id);
        if ($idemKey && Cache::has($idemKey)) {
            return response()->json(Cache::get($idemKey), 200);
        }

        $legacyUserId = $request->user()?->getLegacyUserId() ?? 103;

        $rad = DB::connection('legacy')->transaction(function () use ($visit, $validated, $legacyUserId) {
            $order = LegacyRadiologyOrder::create([
                'id_visit' => $visit->id,
                'dokter_pengirim' => $visit->id_dokter,
                'status_pemeriksaan' => 'proses',
                'keterangan' => $validated['notes'] ?? null,
                'status' => '1',
                'id_user' => $legacyUserId,
                'created_at' => now(),
                'create_time_stamp' => now(),
            ]);

            foreach ($validated['items'] as $item) {
                LegacyRadiologyOrderDetail::create([
                    'id_trx_rad' => $order->id,
                    'id_item_rad' => $item['item_id'] ?? 0,
                    'keterangan' => $item['name'],
                    'status' => '1',
                    'id_user' => $legacyUserId,
                    'created_at' => now(),
                ]);
            }

            return $order->load('details');
        });

        $this->logActivity($request, sprintf("Menyimpan order radiologi untuk visit ID #%d", $visit->id));

        $body = [
            'success' => true,
            'message' => 'Order radiologi berhasil disimpan.',
            'data' => $rad
        ];
        if ($idemKey) {
            Cache::put($idemKey, $body, now()->addMinutes(5));
        }

        return response()->json($body, 201);
    }

    /**
     * Helper to write audit log safely into log_user.
     */
    protected function logActivity(Request $request, string $detail): void
    {
        try {
            if (LegacyAccess::isAvailable('log_user') && LegacyAccess::hasTable('log_user')) {
                $userName = $request->user()?->username ?? 'petugas';
                DB::connection('legacy')->table('log_user')->insert([
                    'waktu' => now(),
                    'user' => $userName,
                    'role' => 'pemeriksaan',
                    'log' => $detail,
                    'status' => '1',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal mencatat audit log pemeriksaan: ' . $e->getMessage());
        }
    }
}
