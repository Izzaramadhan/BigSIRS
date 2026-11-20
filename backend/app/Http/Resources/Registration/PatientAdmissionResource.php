<?php

namespace App\Http\Resources\Registration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientAdmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $guarantor = $this->whenLoaded('guarantor');
        $visits = $this->whenLoaded('visits');

        return [
            'id' => (int) $this->id,
            'patient_id' => (int) $this->id_pasien,
            'admission_time' => $this->waktu_admisi,
            'discharge_time' => $this->waktu_keluar,
            'reason' => $this->alasan_datang,
            'entry_mode' => $this->cara_masuk,
            'guarantor' => $guarantor ? [
                'id' => (int) $guarantor->id,
                'name' => $guarantor->jenis_asuransi ?? $guarantor->name ?? null,
            ] : null,
            'insurance_number' => $this->no_bpjs,
            'status' => (string) $this->status,
            'status_label' => match ((string) $this->status) {
                '2' => 'Selesai',
                '1' => 'Aktif',
                default => 'Tercatat',
            },
            'visits' => $visits ? $visits->map(function ($visit) {
                $doctorName = null;
                if ($visit->doctor) {
                    $doctorName = $visit->doctor->person->nama ?? $visit->doctor->name ?? null;
                }

                return [
                    'id' => (int) $visit->id,
                    'polyclinic' => $visit->polyclinic ? [
                        'id' => (int) $visit->polyclinic->id,
                        'name' => $visit->polyclinic->nama ?? $visit->polyclinic->name ?? null,
                    ] : null,
                    'doctor' => $visit->doctor ? [
                        'id' => (int) $visit->doctor->id,
                        'name' => $doctorName,
                    ] : null,
                    'case_type' => $visit->kasus,
                    'service_status' => $visit->status_dilayani,
                    'service_status_label' => match ($visit->status_dilayani) {
                        'Ya' => 'Selesai Dilayani',
                        'Proses' => 'Sedang Dilayani',
                        default => 'Menunggu Pelayanan',
                    },
                    'billing_status' => $visit->cek_lunas_visit,
                    'examination_time' => $visit->waktu_mulai_periksa ? (string) $visit->waktu_mulai_periksa : null,
                    'examination_end_time' => $visit->waktu_selesai_periksa ? (string) $visit->waktu_selesai_periksa : null,
                ];
            })->values() : [],
        ];
    }
}

