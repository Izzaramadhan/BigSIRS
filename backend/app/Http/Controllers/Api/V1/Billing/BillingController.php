<?php

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Controller;
use App\Http\Resources\Billing\BillingResource;
use App\Models\Legacy\LegacyBilling;
use App\Models\Legacy\LegacyBillingDetail;
use App\Models\Legacy\LegacyVisit;
use App\Support\LegacyAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BillingController extends Controller
{
    /**
     * Display paginated read-only list of outpatient visit billing states.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->get('per_page', 10)));

        if (!LegacyAccess::isAvailable('trx_visit')) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'from' => 0,
                    'to' => 0,
                    'read_only_mode' => true,
                    'payment_enabled' => false,
                ],
                'message' => 'Tabel transaksi kunjungan legacy (trx_visit) tidak tersedia pada koneksi database aktif.',
            ]);
        }

        $with = [
            'polyclinic',
            'doctor.person',
            'admission.patient.person',
            'admission.guarantor',
        ];
        if (LegacyAccess::hasTable('trx_antrian')) {
            $with[] = 'queue';
            $with[] = 'admission.queue';
        }
        if (LegacyAccess::hasTable('trx_tindakan')) {
            $with[] = 'procedures.tindakan';
        }
        if (LegacyAccess::hasTable('trx_resep')) {
            $with[] = 'prescriptions.details';
        }
        if (LegacyAccess::hasTable('trx_lab')) {
            $with[] = 'labOrders.details.item';
        }
        if (LegacyAccess::hasTable('trx_rad')) {
            $with[] = 'radiologyOrders.details.item';
        }

        $query = LegacyVisit::query()
            ->with($with)
            ->where('trx_visit.status', 1)
            ->where('trx_visit.id_admisi', '>', 0)
            ->whereHas('admission', function ($aq) {
                $aq->where('status', '!=', '0');
            });

        $hasBillingTable = LegacyAccess::hasTable('trx_billing');

        $term = trim((string) preg_replace('/\s+/', ' ', (string) ($request->get('q') ?? $request->get('search') ?? '')));
        if ($term !== '') {
            $like = '%' . mb_strtolower($term, 'UTF-8') . '%';
            $strippedRm = str_replace('-', '', $term);
            $likeStrippedRm = $strippedRm !== '' ? '%' . mb_strtolower($strippedRm, 'UTF-8') . '%' : null;
            $invoiceVisitIds = [];

            if ($hasBillingTable) {
                $invoiceVisitIds = LegacyBilling::query()
                    ->whereRaw('LOWER(no_invoice) LIKE ?', [$like])
                    ->pluck('id_visit')
                    ->filter()
                    ->all();
            }

            $query->where(function ($sub) use ($like, $likeStrippedRm, $invoiceVisitIds) {
                $sub->whereHas('admission.patient.person', function ($pq) use ($like) {
                    $pq->whereRaw('LOWER(nama) LIKE ?', [$like])
                        ->orWhereRaw('CAST(no_ktp AS CHAR) LIKE ?', [$like]);
                })->orWhereHas('admission.patient', function ($pq) use ($like, $likeStrippedRm) {
                    $pq->whereRaw('LOWER(no_rm) LIKE ?', [$like]);
                    if ($likeStrippedRm !== null) {
                        $pq->orWhereRaw("LOWER(REPLACE(no_rm, '-', '')) LIKE ?", [$likeStrippedRm]);
                    }
                });

                if (!empty($invoiceVisitIds)) {
                    $sub->orWhereIn('trx_visit.id', $invoiceVisitIds);
                }
            });
        }

        if ($polyclinicId = $request->get('polyclinic_id')) {
            if ($polyclinicId !== 'all' && $polyclinicId !== 'Semua Poliklinik') {
                $query->where('trx_visit.id_poliklinik', $polyclinicId);
            }
        } elseif ($polyclinic = $request->get('polyclinic')) {
            if ($polyclinic !== 'all' && $polyclinic !== 'Semua Poliklinik') {
                $query->whereHas('polyclinic', function ($pq) use ($polyclinic) {
                    $pq->where('nama_poliklinik', $polyclinic)
                        ->orWhere('nama', $polyclinic);
                });
            }
        }

        if ($guarantorId = $request->get('guarantor_id')) {
            if ($guarantorId !== 'all' && $guarantorId !== 'Semua Jenis Penjamin') {
                $query->whereHas('admission', fn ($aq) => $aq->where('id_jenis_asuransi', $guarantorId));
            }
        } elseif ($guarantor = $request->get('guarantor')) {
            if ($guarantor !== 'all' && $guarantor !== 'Semua Jenis Penjamin') {
                $gLower = mb_strtolower(trim((string) $guarantor), 'UTF-8');
                $query->whereHas('admission.guarantor', function ($gq) use ($gLower, $guarantor) {
                    if ($gLower === 'umum' || $gLower === 'pasien umum') {
                        $gq->where('type', 'UMUM')->orWhereRaw('LOWER(jenis_asuransi) LIKE ?', ['%umum%']);
                    } elseif (str_contains($gLower, 'bpjs')) {
                        $gq->where('type', 'BPJS')->orWhereRaw('LOWER(jenis_asuransi) LIKE ?', ['%bpjs%']);
                    } elseif (str_contains($gLower, 'inhealth')) {
                        $gq->whereRaw('LOWER(jenis_asuransi) LIKE ?', ['%inhealth%']);
                    } elseif ($gLower === 'asuransi lainnya') {
                        $gq->whereNotIn('type', ['UMUM', 'BPJS'])
                            ->whereRaw('LOWER(jenis_asuransi) NOT LIKE ?', ['%umum%'])
                            ->whereRaw('LOWER(jenis_asuransi) NOT LIKE ?', ['%bpjs%']);
                    } else {
                        $gq->where('jenis_asuransi', $guarantor);
                    }
                });
            }
        }

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
        }

        if ($status = $request->get('status')) {
            if ($status !== 'all' && $status !== 'Semua') {
                $mappedStatuses = match ($status) {
                    'Lunas' => ['sudah bayar'],
                    'Dibayar Sebagian' => ['kurang bayar'],
                    'Menunggu Verifikasi' => ['menunggu bayar'],
                    'Belum Bayar', 'Siap Dibayar' => ['belum bayar', 'kurang bayar', 'menunggu bayar'],
                    default => [],
                };

                if (!empty($mappedStatuses)) {
                    $billingVisitIds = $hasBillingTable
                        ? LegacyBilling::query()
                            ->where('status', '1')
                            ->whereIn('status_lunas', $mappedStatuses)
                            ->pluck('id_visit')
                            ->filter()
                            ->all()
                        : [];

                    $allBillingVisitIds = $hasBillingTable
                        ? LegacyBilling::query()
                            ->where('status', '1')
                            ->pluck('id_visit')
                            ->filter()
                            ->all()
                        : [];

                    $query->where(function ($sq) use ($mappedStatuses, $billingVisitIds, $allBillingVisitIds) {
                        if (!empty($billingVisitIds)) {
                            $sq->whereIn('trx_visit.id', $billingVisitIds);
                        }
                        // Include visits without active trx_billing matching trx_visit.cek_lunas_visit
                        $sq->orWhere(function ($vq) use ($mappedStatuses, $allBillingVisitIds) {
                            if (!empty($allBillingVisitIds)) {
                                $vq->whereNotIn('trx_visit.id', $allBillingVisitIds);
                            }
                            $vq->whereIn('trx_visit.cek_lunas_visit', $mappedStatuses);
                        });
                    });
                }
            }
        }

        $sortBy = $request->get('sort_by', 'id');
        $sortDir = strtolower((string) $request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = [
            'id' => 'trx_visit.id',
            'antrian' => 'trx_visit.id',
            'waktu' => 'trx_visit.created_at',
            'klinik' => 'trx_visit.id_poliklinik',
            'exam_status' => 'trx_visit.status_dilayani',
            'payment_status' => 'trx_visit.cek_lunas_visit',
        ];
        $query->orderBy($allowedSorts[$sortBy] ?? 'trx_visit.id', $sortDir);

        $paginator = $query->paginate($perPage);
        $visitIds = collect($paginator->items())->pluck('id')->map(fn ($v) => (int) $v)->all();

        $billingsByVisit = collect();
        $detailsByVisit = collect();

        if (!empty($visitIds)) {
            if ($hasBillingTable) {
                $billingsByVisit = LegacyBilling::query()
                    ->where('status', '1')
                    ->whereIn('id_visit', $visitIds)
                    ->orderByDesc('id')
                    ->get()
                    ->groupBy('id_visit');
            }
            if (LegacyAccess::hasTable('trx_billing_detail')) {
                $detailsByVisit = LegacyBillingDetail::query()
                    ->where('status', 1)
                    ->whereIn('id_visit', $visitIds)
                    ->orderBy('id')
                    ->get()
                    ->groupBy('id_visit');
            }
        }

        $records = collect($paginator->items())->map(function (LegacyVisit $visit) use ($billingsByVisit, $detailsByVisit) {
            return new BillingResource($this->buildBillingPayload(
                $visit,
                $billingsByVisit->get((int) $visit->id, collect()),
                $detailsByVisit->get((int) $visit->id, collect()),
                false
            ));
        });

        return response()->json([
            'data' => $records->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem() ?? 0,
                'to' => $paginator->lastItem() ?? 0,
                'read_only_mode' => true,
                'payment_enabled' => false,
            ],
        ]);
    }

    /**
     * Display read-only itemized billing detail for a valid visit_id.
     */
    public function show(int $id): JsonResponse
    {
        if (!LegacyAccess::isAvailable('trx_visit')) {
            abort(404, 'Tabel kunjungan legacy tidak tersedia.');
        }

        $with = [
            'polyclinic',
            'doctor.person',
            'admission.patient.person',
            'admission.guarantor',
        ];
        if (LegacyAccess::hasTable('trx_antrian')) {
            $with[] = 'queue';
            $with[] = 'admission.queue';
        }
        if (LegacyAccess::hasTable('trx_tindakan')) {
            $with[] = 'procedures.tindakan';
        }
        if (LegacyAccess::hasTable('trx_resep')) {
            $with[] = 'prescriptions.details';
        }
        if (LegacyAccess::hasTable('trx_lab')) {
            $with[] = 'labOrders.details.item';
        }
        if (LegacyAccess::hasTable('trx_rad')) {
            $with[] = 'radiologyOrders.details.item';
        }

        $visit = LegacyVisit::with($with)->findOrFail($id);

        if ((int) $visit->id_admisi <= 0 || !$visit->admission || (string) $visit->admission->status === '0' || (int) ($visit->status ?? 1) === 0) {
            abort(404, 'Data kunjungan atau admisi billing tidak valid.');
        }

        $billings = LegacyAccess::hasTable('trx_billing')
            ? LegacyBilling::query()->where('status', '1')->where('id_visit', $visit->id)->orderByDesc('id')->get()
            : collect();

        $details = LegacyAccess::hasTable('trx_billing_detail')
            ? LegacyBillingDetail::query()->where('status', 1)->where('id_visit', $visit->id)->orderBy('id')->get()
            : collect();

        return response()->json([
            'data' => new BillingResource($this->buildBillingPayload($visit, $billings, $details, true)),
        ]);
    }

    /**
     * Build consistent read-only billing payload from DB transactions without writing.
     */
    protected function buildBillingPayload(
        LegacyVisit $visit,
        Collection $billings,
        Collection $billingDetails,
        bool $includeItems = false
    ): array {
        $admission = $visit->admission;
        $patient = $admission?->patient;
        $person = $patient?->person;
        $guarantor = $admission?->guarantor;
        $polyclinic = $visit->polyclinic;
        $doctor = $visit->doctor;
        $doctorPerson = $doctor?->person;
        $primaryBilling = $billings->first();

        $rawServiceDate = $primaryBilling?->tgl_transaksi ?? $admission?->waktu_admisi ?? $visit->created_at;
        $serviceDate = null;
        $serviceTime = null;
        if (!empty($rawServiceDate)) {
            $dt = $rawServiceDate instanceof \DateTimeInterface ? $rawServiceDate : new \DateTime((string) $rawServiceDate);
            $serviceDate = $dt->format('Y-m-d');
            if ($admission?->waktu_admisi) {
                $adt = $admission->waktu_admisi instanceof \DateTimeInterface
                    ? $admission->waktu_admisi
                    : new \DateTime((string) $admission->waktu_admisi);
                $serviceTime = $adt->format('H:i') . ' WIB';
            }
        }

        $birthDate = null;
        $age = null;
        if ($person && !empty($person->tgl_lahir)) {
            $bdt = $person->tgl_lahir instanceof \DateTimeInterface ? $person->tgl_lahir : new \DateTime((string) $person->tgl_lahir);
            $birthDate = $bdt->format('Y-m-d');
            $age = (new \DateTime())->diff($bdt)->y;
        }

        $queueNumber = '—';
        if ($visit->relationLoaded('queue') && !empty($visit->queue?->no_antrian)) {
            $queueNumber = (string) $visit->queue->no_antrian;
        } elseif ($admission && $admission->relationLoaded('queue') && !empty($admission->queue?->no_antrian)) {
            $queueNumber = (string) $admission->queue->no_antrian;
        }

        $items = [];
        $coveredProcedureIds = [];
        $coveredLabIds = [];
        $coveredRxIds = [];
        $seenBillingDetailIds = [];

        foreach ($billingDetails as $detail) {
            if (in_array((int) $detail->id, $seenBillingDetailIds, true)) {
                continue;
            }
            $seenBillingDetailIds[] = (int) $detail->id;

            if (!empty($detail->ref_id_trx_tindakan)) {
                $coveredProcedureIds[] = (int) $detail->ref_id_trx_tindakan;
            } elseif (($detail->jenis ?? '') === 'TDK' && !empty($detail->ref_id)) {
                $coveredProcedureIds[] = (int) $detail->ref_id;
            }
            if (!empty($detail->ref_id_trx_lab)) {
                $coveredLabIds[] = (int) $detail->ref_id_trx_lab;
            }
            if (!empty($detail->id_trx_resep)) {
                $coveredRxIds[] = (int) $detail->id_trx_resep;
            }

            $qty = max(1, (int) ($detail->jumlah_item ?? 1));
            $hasValidPrice = $detail->harga !== null && is_numeric($detail->harga) && (float) $detail->harga >= 0;
            $unitPrice = $hasValidPrice ? (int) round((float) $detail->harga) : null;
            $discount = max(0, (int) ($detail->diskon ?? 0));
            $subtotal = $hasValidPrice ? max(0, ($unitPrice * $qty) - $discount) : null;

            $items[] = [
                'id' => 'BD-' . (int) $detail->id,
                'source' => 'trx_billing_detail',
                'billing_detail_id' => (int) $detail->id,
                'billing_id' => $detail->id_billing ? (int) $detail->id_billing : null,
                'order_date' => $detail->created_at ? date('Y-m-d', strtotime((string) $detail->created_at)) : $serviceDate,
                'jenis' => (string) ($detail->jenis ?? 'TDK'),
                'code' => (string) ($detail->kode ?? ''),
                'name' => (string) ($detail->nama ?? '-'),
                'qty' => $qty,
                'price' => $unitPrice,
                'discount' => $discount,
                'discount_percent' => 0,
                'subtotal' => $subtotal,
                'is_verified_price' => $hasValidPrice,
                'pricing_status' => $hasValidPrice ? 'verified' : 'unpriced',
                'status' => $this->mapDetailPaymentLabel($detail->status_lunas),
                'raw_status_lunas' => $detail->status_lunas ?? 'belum bayar',
            ];
        }

        // Clinical procedures from trx_tindakan not yet materialized in trx_billing_detail
        if ($visit->relationLoaded('procedures')) {
            foreach ($visit->procedures as $proc) {
                if (in_array((int) $proc->id, $coveredProcedureIds, true) || (int) ($proc->status ?? 1) === 0) {
                    continue;
                }
                $tariff = $proc->tindakan;
                $hasValidPrice = $tariff && $tariff->harga !== null && is_numeric($tariff->harga) && (float) $tariff->harga >= 0;
                $unitPrice = $hasValidPrice ? (int) round((float) $tariff->harga) : null;
                $qty = max(1, (int) ($proc->qty ?? 1));
                $discount = max(0, (int) ($proc->diskon ?? 0));
                $subtotal = $hasValidPrice ? max(0, ($unitPrice * $qty) - $discount) : null;

                $items[] = [
                    'id' => 'TDK-' . (int) $proc->id,
                    'source' => 'trx_tindakan',
                    'procedure_id' => $tariff ? (int) $tariff->id : (int) $proc->id_tindakan,
                    'order_date' => $proc->created_at ? date('Y-m-d', strtotime((string) $proc->created_at)) : $serviceDate,
                    'jenis' => 'TDK',
                    'code' => (string) ($tariff->kode ?? ''),
                    'name' => (string) ($tariff->nama ?? $proc->nama_tindakan ?? 'Tindakan Medis'),
                    'qty' => $qty,
                    'price' => $unitPrice,
                    'discount' => $discount,
                    'discount_percent' => 0,
                    'subtotal' => $subtotal,
                    'is_verified_price' => $hasValidPrice,
                    'pricing_status' => $hasValidPrice ? 'verified' : 'unpriced',
                    'status' => 'Belum Bayar',
                    'raw_status_lunas' => 'belum bayar',
                ];
            }
        }

        // Clinical prescriptions not yet materialized in trx_billing_detail (no verified unit price on trx_resep_detail)
        if ($visit->relationLoaded('prescriptions')) {
            foreach ($visit->prescriptions as $rx) {
                if (in_array((int) $rx->id, $coveredRxIds, true) || (int) ($rx->status ?? 1) === 0) {
                    continue;
                }
                foreach ($rx->details as $detail) {
                    if ((int) ($detail->status ?? 1) === 0) {
                        continue;
                    }
                    $items[] = [
                        'id' => 'RSP-' . (int) $detail->id,
                        'source' => 'trx_resep',
                        'prescription_id' => (int) $rx->id,
                        'order_date' => $rx->created_at ? date('Y-m-d', strtotime((string) $rx->created_at)) : $serviceDate,
                        'jenis' => 'RSP',
                        'code' => '',
                        'name' => (string) ($detail->nama_obat ?? 'Resep Obat'),
                        'qty' => max(1, (int) ($detail->jml_item ?? 1)),
                        'price' => null,
                        'discount' => 0,
                        'discount_percent' => 0,
                        'subtotal' => null,
                        'is_verified_price' => false,
                        'pricing_status' => 'unpriced',
                        'status' => 'Belum Bayar',
                        'raw_status_lunas' => 'belum bayar',
                    ];
                }
            }
        }

        // Clinical lab orders not yet materialized in trx_billing_detail
        if ($visit->relationLoaded('labOrders')) {
            foreach ($visit->labOrders as $lab) {
                if (in_array((int) $lab->id, $coveredLabIds, true) || (int) ($lab->status ?? 1) === 0) {
                    continue;
                }
                foreach ($lab->details as $detail) {
                    if ((int) ($detail->status ?? 1) === 0) {
                        continue;
                    }
                    $items[] = [
                        'id' => 'LAB-' . (int) $detail->id,
                        'source' => 'trx_lab',
                        'lab_order_id' => (int) $lab->id,
                        'order_date' => $lab->created_at ? date('Y-m-d', strtotime((string) $lab->created_at)) : $serviceDate,
                        'jenis' => 'LAB',
                        'code' => '',
                        'name' => (string) ($detail->nama_grup_lab ?? $detail->item?->nama ?? 'Pemeriksaan Lab'),
                        'qty' => 1,
                        'price' => null,
                        'discount' => 0,
                        'discount_percent' => 0,
                        'subtotal' => null,
                        'is_verified_price' => false,
                        'pricing_status' => 'unpriced',
                        'status' => 'Belum Bayar',
                        'raw_status_lunas' => 'belum bayar',
                    ];
                }
            }
        }

        // Clinical radiology orders not yet materialized in trx_billing_detail
        if ($visit->relationLoaded('radiologyOrders')) {
            foreach ($visit->radiologyOrders as $rad) {
                if ((string) ($rad->status ?? '1') === '0') {
                    continue;
                }
                foreach ($rad->details as $detail) {
                    if ((string) ($detail->status ?? '1') === '0') {
                        continue;
                    }
                    $items[] = [
                        'id' => 'RAD-' . (int) $detail->id,
                        'source' => 'trx_rad',
                        'radiology_order_id' => (int) $rad->id,
                        'order_date' => $rad->created_at ? date('Y-m-d', strtotime((string) $rad->created_at)) : $serviceDate,
                        'jenis' => 'RAD',
                        'code' => '',
                        'name' => (string) ($detail->item?->nama ?? $detail->keterangan ?? 'Pemeriksaan Radiologi'),
                        'qty' => 1,
                        'price' => null,
                        'discount' => 0,
                        'discount_percent' => 0,
                        'subtotal' => null,
                        'is_verified_price' => false,
                        'pricing_status' => 'unpriced',
                        'status' => 'Belum Bayar',
                        'raw_status_lunas' => 'belum bayar',
                    ];
                }
            }
        }

        $verifiedSubtotal = 0;
        $unpricedItemCount = 0;
        foreach ($items as $it) {
            if ($it['is_verified_price'] && $it['subtotal'] !== null) {
                $verifiedSubtotal += (int) $it['subtotal'];
            } else {
                $unpricedItemCount++;
            }
        }

        $recordedDiscount = $primaryBilling ? max(0, (int) ($primaryBilling->diskon ?? 0)) : 0;
        $recordedPaidAmount = $primaryBilling ? max(0, (int) ($primaryBilling->bayar ?? 0)) : 0;

        $examStatusLabel = match (strtolower((string) $visit->status_dilayani)) {
            'ya' => 'Sudah Dilayani',
            'proses' => 'Sedang Diperiksa',
            default => 'Belum Dilayani',
        };

        $hasRx = $visit->relationLoaded('prescriptions') && $visit->prescriptions->isNotEmpty();
        $pharmacyStatusLabel = 'Tidak Ada Resep';
        if ($hasRx) {
            $allDelivered = $visit->prescriptions->every(fn ($rx) => strtolower((string) ($rx->penyerahan_obat ?? '')) === 'sudah');
            $pharmacyStatusLabel = $allDelivered ? 'Sudah Diterima' : 'Menunggu Obat';
        } elseif (strtolower((string) $visit->cek_farmasi) === 'ya') {
            $pharmacyStatusLabel = 'Sudah Diterima';
        }

        $billingStatus = $this->resolveOverallStatus($primaryBilling, $visit);

        $guarantorName = $guarantor->jenis_asuransi ?? $guarantor->name ?? 'UMUM';
        $guarantorType = $guarantor->type ?? $guarantor->jenis_asuransi ?? 'UMUM';

        $payload = [
            'id' => (int) $visit->id,
            'visit_id' => (int) $visit->id,
            'admission_id' => $admission ? (int) $admission->id : null,
            'patient_id' => $patient ? (int) $patient->id : null,
            'billing_id' => $primaryBilling ? (int) $primaryBilling->id : null,
            'invoice_number' => $primaryBilling?->no_invoice ?: null,
            'visit_number' => 'VIS-' . (int) $visit->id,
            'queue_number' => $queueNumber,
            'service_date' => $serviceDate,
            'service_time' => $serviceTime,
            'medical_record_number' => $patient->no_rm ?? null,
            'patient_name' => $person->nama ?? null,
            'nik' => $person && $person->no_ktp !== null ? (string) $person->no_ktp : null,
            'gender' => $person->gender ?? null,
            'birth_place' => $person->tempat_lahir ?? null,
            'birth_date' => $birthDate,
            'age' => $age,
            'address' => $person->alamat ?? null,
            'polyclinic_id' => $polyclinic ? (int) $polyclinic->id : null,
            'polyclinic' => $polyclinic->nama ?? $polyclinic->name ?? $polyclinic->nama_poliklinik ?? '—',
            'doctor_id' => $doctor ? (int) $doctor->id : null,
            'doctor' => $doctorPerson->nama ?? $doctor->nama ?? $doctor->nama_lengkap ?? $doctor->name ?? '—',
            'guarantor_id' => $guarantor ? (int) $guarantor->id : null,
            'guarantor' => $guarantorName,
            'guarantor_type' => $guarantorType,
            'guarantor_detail' => $guarantorName,
            'exam_status' => $examStatusLabel,
            'pharmacy_status' => $pharmacyStatusLabel,
            'status' => $billingStatus,
            'raw_visit_cek_billing' => $visit->cek_billing ?? 'Tidak',
            'raw_visit_cek_lunas_visit' => $visit->cek_lunas_visit ?? 'belum bayar',
            'raw_billing_status_lunas' => $primaryBilling?->status_lunas,
            'verified_subtotal' => $verifiedSubtotal,
            'total_amount' => $verifiedSubtotal,
            'recorded_discount' => $recordedDiscount,
            'paid_amount' => $recordedPaidAmount,
            'remaining_balance' => max(0, $verifiedSubtotal - $recordedDiscount - $recordedPaidAmount),
            'unpriced_item_count' => $unpricedItemCount,
            'item_count' => count($items),
            'read_only_mode' => true,
            'payment_enabled' => false,
        ];

        if ($includeItems) {
            $payload['items'] = $items;
        }

        return $payload;
    }

    protected function resolveOverallStatus(?LegacyBilling $primaryBilling, LegacyVisit $visit): string
    {
        $raw = $primaryBilling && !empty($primaryBilling->status_lunas)
            ? strtolower(trim((string) $primaryBilling->status_lunas))
            : strtolower(trim((string) ($visit->cek_lunas_visit ?? 'belum bayar')));

        return match ($raw) {
            'sudah bayar' => 'Lunas',
            'kurang bayar' => 'Dibayar Sebagian',
            'menunggu bayar' => 'Menunggu Verifikasi',
            default => 'Belum Bayar',
        };
    }

    protected function mapDetailPaymentLabel(?string $rawStatus): string
    {
        return match (strtolower(trim((string) $rawStatus))) {
            'sudah bayar' => 'Lunas',
            'kurang bayar' => 'Dibayar Sebagian',
            'tertagih' => 'Tertagih',
            default => 'Belum Bayar',
        };
    }
}
