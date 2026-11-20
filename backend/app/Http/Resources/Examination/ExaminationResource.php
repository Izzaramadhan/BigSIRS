<?php

namespace App\Http\Resources\Examination;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExaminationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $admission = $this->admission;
        $patient = $admission?->patient;
        $person = $patient?->person;
        $guarantor = $admission?->guarantor;
        $polyclinic = $this->polyclinic;
        $doctor = $this->doctor;
        $doctorPerson = $doctor?->person;

        $visitTime = null;
        $visitDate = null;
        $rawTime = $this->waktu_mulai_periksa ?? $this->created_at ?? $admission?->waktu_admisi;
        if (!empty($rawTime)) {
            $dt = $rawTime instanceof \DateTimeInterface ? $rawTime : new \DateTime($rawTime);
            $visitDate = $dt->format('Y-m-d');
            $visitTime = $dt->format('H:i') . ' WIB';
        }

        $birthDate = null;
        $age = 35;
        if ($person && !empty($person->tgl_lahir)) {
            $bdt = $person->tgl_lahir instanceof \DateTimeInterface
                ? $person->tgl_lahir
                : new \DateTime($person->tgl_lahir);
            $birthDate = $bdt->format('Y-m-d');
            $now = new \DateTime();
            $age = $now->diff($bdt)->y;
        }

        // Status mapping
        $statusLabel = match (strtolower((string) $this->status_dilayani)) {
            'tidak' => 'Menunggu',
            'proses' => 'Sedang Diperiksa',
            'ya' => 'Selesai',
            default => 'Menunggu',
        };

        return [
            'id' => (int) $this->id,
            'visit_id' => (int) $this->id,
            'admission_id' => $admission ? (int) $admission->id : null,
            'patient_id' => $patient ? (int) $patient->id : null,
            'medical_record_number' => $patient->no_rm ?? null,
            'patient_name' => $person->nama ?? null,
            'nik' => $person && $person->no_ktp !== null ? (string) $person->no_ktp : null,
            'gender' => $person->gender ?? 'L',
            'birth_date' => $birthDate,
            'age' => $age,
            'phone' => $person->no_hp ?? null,
            'address' => $person->alamat ?? null,
            'polyclinic_id' => $polyclinic ? (int) $polyclinic->id : null,
            'polyclinic' => $polyclinic->nama_poliklinik ?? $polyclinic->name ?? $polyclinic->nama ?? 'Poli Umum',
            'doctor_id' => $doctor ? (int) $doctor->id : null,
            'doctor' => $doctorPerson->nama ?? $doctor->nama_lengkap ?? $doctor->nama ?? $doctor->name ?? null,
            'guarantor' => $guarantor->jenis_asuransi ?? $guarantor->name ?? 'UMUM',
            'queue_number' => '—', // decoupled visual placeholder
            'visit_date' => $visitDate,
            'visit_time' => $visitTime,
            'status' => $statusLabel,
            'complaint' => $admission->alasan_datang ?? $admission->keterangan ?? '',
            'created_at' => $this->created_at,
        ];
    }
}
