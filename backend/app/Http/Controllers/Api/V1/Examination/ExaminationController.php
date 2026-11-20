<?php

namespace App\Http\Controllers\Api\V1\Examination;

use App\Http\Controllers\Controller;
use App\Http\Resources\Examination\ExaminationResource;
use App\Models\Legacy\LegacyVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExaminationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->get('per_page', 10);
        $perPage = max(1, min(50, $perPage));

        $query = LegacyVisit::query()
            ->with(['polyclinic', 'doctor.person', 'admission.patient.person', 'admission.guarantor'])
            ->where(function ($q) {
                $q->whereNull('deleted_at')
                    ->orWhere('deleted_at', '0000-00-00 00:00:00');
            })
            ->where('status', 1)
            ->whereHas('admission', function ($aq) {
                $aq->where('status', '!=', '0')
                    ->where(function ($del) {
                        $del->whereNull('deleted_at')
                            ->orWhere('deleted_at', '0000-00-00 00:00:00');
                    });
            });

        // Search by keyword (Nama Pasien, No. RM, NIK)
        $term = trim((string) ($request->get('q') ?? $request->get('search') ?? ''));
        if ($term !== '') {
            $like = '%' . mb_strtolower($term, 'UTF-8') . '%';
            $query->where(function ($sub) use ($like) {
                $sub->whereHas('admission.patient.person', function ($pq) use ($like) {
                    $pq->whereRaw('LOWER(nama) LIKE ?', [$like])
                        ->orWhereRaw('CAST(no_ktp AS CHAR) LIKE ?', [$like]);
                })->orWhereHas('admission.patient', function ($pq) use ($like) {
                    $pq->whereRaw('LOWER(no_rm) LIKE ?', [$like]);
                });
            });
        }

        // Filter Polyclinic
        if ($polyclinicId = $request->get('polyclinic_id')) {
            $query->where('id_poliklinik', $polyclinicId);
        }

        // Filter Doctor
        if ($doctorId = $request->get('doctor_id')) {
            $query->where('id_dokter', $doctorId);
        }

        // Filter Date
        if ($date = $request->get('date')) {
            $query->where(function ($dq) use ($date) {
                $dq->whereDate('waktu_mulai_periksa', $date)
                    ->orWhereDate('created_at', $date);
            });
        }

        // Filter Status
        if ($status = $request->get('status')) {
            if ($status !== 'all' && $status !== 'Semua Status') {
                $visitStatus = match ($status) {
                    'Sedang Diperiksa' => 'Proses',
                    'Selesai' => 'Ya',
                    default => 'Tidak',
                };
                $query->where('status_dilayani', $visitStatus);
            }
        }

        $visits = $query->orderByDesc('id')->paginate($perPage);

        return ExaminationResource::collection($visits);
    }

    public function show(int $id): JsonResponse
    {
        $visit = LegacyVisit::with(['polyclinic', 'doctor.person', 'admission.patient.person', 'admission.guarantor'])
            ->findOrFail($id);

        return (new ExaminationResource($visit))->response();
    }
}
