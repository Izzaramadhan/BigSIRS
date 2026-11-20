<?php

namespace App\Http\Resources\Registration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $person = $this->whenLoaded('person');
        $guarantor = $this->whenLoaded('guarantor');

        $birthDate = null;
        if ($person && $person->tgl_lahir) {
            $birthDate = $person->tgl_lahir instanceof \DateTimeInterface
                ? $person->tgl_lahir->format('Y-m-d')
                : (string) $person->tgl_lahir;
        }

        $rawNik = $person && $person->no_ktp !== null ? (string) $person->no_ktp : null;
        $maskedNik = null;
        if ($rawNik !== null && $rawNik !== '') {
            $visible = min(4, strlen($rawNik));
            $maskedNik = substr($rawNik, 0, $visible) . str_repeat('*', max(0, strlen($rawNik) - $visible));
        }

        $latestAdmission = $this->whenLoaded('latestAdmission');
        $effectiveGuarantor = $guarantor ?: ($latestAdmission?->relationLoaded('guarantor') ? $latestAdmission->guarantor : null);
        $latestVisit = $latestAdmission?->relationLoaded('latestVisit') ? $latestAdmission->latestVisit : null;
        $polyclinic = $latestVisit?->relationLoaded('polyclinic') ? $latestVisit->polyclinic : null;

        $admissionDate = null;
        $admissionTime = null;
        if ($latestAdmission && $latestAdmission->waktu_admisi) {
            $admTime = \Carbon\Carbon::parse($latestAdmission->waktu_admisi);
            $admissionDate = $admTime->format('Y-m-d');
            $admissionTime = $admTime->format('H:i') . ' WIB';
        } elseif ($this->created_at) {
            $cTime = \Carbon\Carbon::parse($this->created_at);
            $admissionDate = $cTime->format('Y-m-d');
            $admissionTime = $cTime->format('H:i') . ' WIB';
        }

        $hasUnpaidBills = false;
        try {
            if (\App\Support\LegacyAccess::isAvailable('trx_visit') && \App\Support\LegacyAccess::hasTable('trx_visit')) {
                $hasUnpaidBills = \Illuminate\Support\Facades\DB::connection('legacy')
                    ->table('trx_visit')
                    ->join('trx_admisi', 'trx_admisi.id', '=', 'trx_visit.id_admisi')
                    ->where('trx_admisi.id_pasien', $this->id)
                    ->where(function ($q) {
                        $q->where('trx_visit.cek_lunas_visit', 'belum bayar')
                            ->orWhere('trx_visit.cek_lunas_visit', 'belum_lunas');
                    })
                    ->exists();
            }
        } catch (\Throwable) {
            $hasUnpaidBills = false;
        }

        return [
            'id' => (int) $this->id,
            'person_id' => $this->id_mpi !== null ? (int) $this->id_mpi : null,
            'medical_record_number' => $this->no_rm,
            'no_rm' => $this->no_rm,
            'nik' => $rawNik,
            'no_ktp' => $rawNik,
            'nik_masked' => $maskedNik,
            'name' => $person ? $person->nama : null,
            'nama' => $person ? $person->nama : null,
            'birth_place' => $person ? $person->tempat_lahir : null,
            'tempat_lahir' => $person ? $person->tempat_lahir : null,
            'birth_date' => $birthDate,
            'tgl_lahir' => $birthDate,
            'gender' => $person ? $person->gender : null,
            'phone' => $person ? $person->no_hp : null,
            'no_hp' => $person ? $person->no_hp : null,
            'address' => $person ? $person->alamat : null,
            'alamat' => $person ? $person->alamat : null,
            'hak_kelas' => $person ? $person->hak_kelas : null,
            'guarantor' => $effectiveGuarantor ? [
                'id' => (int) $effectiveGuarantor->id,
                'name' => $effectiveGuarantor->jenis_asuransi ?? $effectiveGuarantor->name ?? null,
            ] : null,
            'has_unpaid_bills' => $hasUnpaidBills,
            'unpaid_billing_message' => 'Pasien ini belum lunas pembayaran. Silahkan lakukan pembayaran di Billing. Atau silahkan hubungi petugas jika mengalami kesulitan.',
            'is_active' => (string) $this->status === '1',
            'admission' => [
                'id' => $latestAdmission?->id,
                'visit_id' => $latestVisit?->id,
                'date' => $admissionDate,
                'time' => $admissionTime,
                'polyclinic' => $polyclinic?->nama ?? 'Poli Umum',
                'notes' => $latestAdmission?->alasan_datang ?? '',
                'general_consent' => 'Belum diprint',
            ],
        ];
    }
}

