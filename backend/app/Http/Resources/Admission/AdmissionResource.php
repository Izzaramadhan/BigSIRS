<?php

namespace App\Http\Resources\Admission;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $patient = $this->patient;
        $person = $patient?->person;
        $guarantor = $this->guarantor;
        $latestVisit = $this->relationLoaded('latestVisit') && $this->latestVisit
            ? $this->latestVisit
            : ($this->relationLoaded('visits') ? $this->visits->last() : null);

        $polyclinic = $latestVisit?->polyclinic;
        $doctor = $latestVisit?->doctor;
        $doctorPerson = $doctor?->person;

        $admissionTime = null;
        $admissionDate = null;
        $admissionDateTimeFormatted = null;
        $rawAdmissionDateTime = null;

        if (!empty($this->waktu_admisi)) {
            $dt = $this->waktu_admisi instanceof \DateTimeInterface
                ? $this->waktu_admisi
                : new \DateTime($this->waktu_admisi);
            $admissionDate = $dt->format('Y-m-d');
            $admissionTime = $dt->format('H:i') . ' WIB';
            $admissionDateTimeFormatted = $dt->format('d-m-Y H:i:s');
            $rawAdmissionDateTime = $dt->format('Y-m-d H:i:s');
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
        $visitStatus = $latestVisit?->status_dilayani ?? 'Tidak';
        $statusLabel = match (strtolower($visitStatus)) {
            'tidak' => 'Menunggu',
            'proses' => 'Sedang Diperiksa',
            'ya' => 'Selesai',
            default => 'Menunggu',
        };
        if ((string)$this->status === '0') {
            $statusLabel = 'Dibatalkan';
        }

        // Service restriction logic for editing
        $pharmacyChecked = $latestVisit?->cek_farmasi ?? 'Tidak';
        $billingChecked = $latestVisit?->cek_billing ?? 'Tidak';
        $billingStatus = $latestVisit?->cek_lunas_visit ?? 'belum bayar';

        $canChangeClinic = true;
        $cannotChangeReason = null;

        $normalizedVisitStatus = strtolower((string) $visitStatus);
        $normalizedPharmacy = strtolower((string) $pharmacyChecked);
        $normalizedBilling = strtolower((string) $billingChecked);

        $isLocked = ($normalizedVisitStatus === 'ya' || $normalizedPharmacy === 'ya' || $normalizedBilling === 'ya');
        $isInProgress = ($normalizedVisitStatus === 'proses');

        if ($isLocked) {
            $canChangeClinic = false;
            $cannotChangeReason = 'Pelayanan atau billing sudah selesai. Data poliklinik dan dokter dikunci.';
        } elseif ($isInProgress) {
            $canChangeClinic = false;
            $cannotChangeReason = 'Pasien sedang dalam proses pemeriksaan klinis di poliklinik. Perubahan poliklinik dibatasi.';
        }

        return [
            'id' => (int) $this->id,
            'admission_date' => $admissionDate,
            'admission_time' => $admissionTime,
            'admission_datetime' => $admissionDateTimeFormatted,
            'raw_admission_datetime' => $rawAdmissionDateTime,
            'queue_number' => '—', // visual decoupled placeholder
            'patient_id' => $patient ? (int) $patient->id : null,
            'medical_record_number' => $patient->no_rm ?? null,
            'patient_name' => $person->nama ?? null,
            'nik' => $person && $person->no_ktp !== null ? (string) $person->no_ktp : null,
            'gender' => $person->gender ?? 'L',
            'birth_place' => $person->tempat_lahir ?? 'Yogyakarta',
            'birth_date' => $birthDate,
            'age' => $age,
            'phone' => $person->no_hp ?? null,
            'address' => $person->alamat ?? null,
            'polyclinic_id' => $polyclinic ? (int) $polyclinic->id : null,
            'polyclinic' => $polyclinic->nama_poliklinik ?? $polyclinic->name ?? $polyclinic->nama ?? 'Poli Umum',
            'doctor_id' => $doctor ? (int) $doctor->id : null,
            'doctor' => $doctorPerson->nama ?? $doctor->nama_lengkap ?? $doctor->nama ?? $doctor->name ?? null,
            'guarantor_id' => $this->id_jenis_asuransi ? (int) $this->id_jenis_asuransi : ($guarantor ? (int) $guarantor->id : null),
            'guarantor' => $guarantor->jenis_asuransi ?? $guarantor->name ?? 'UMUM',
            'guarantor_type' => $guarantor->jenis_asuransi ?? $guarantor->name ?? 'UMUM',
            'insurance_number' => $this->no_bpjs ?? null,
            'referral_source_id' => $this->id_asal_rujukan ? (int) $this->id_asal_rujukan : null,
            'referral_source' => $this->asal_rujukan ?? null,
            'referral_name' => $this->asal_rujukan ?? null,
            'visit_type' => $this->cara_masuk ?? 'Rawat Jalan',
            'case_type' => $latestVisit?->kasus ?? 'Pemeriksaan Kasus Baru',
            'service_status' => $visitStatus,
            'pharmacy_checked' => $pharmacyChecked,
            'billing_checked' => $billingChecked,
            'billing_status' => $billingStatus,
            'can_change_clinic' => $canChangeClinic,
            'cannot_change_reason' => $cannotChangeReason,
            'family_name' => $this->nama_keluarga_dekat ?? null,
            'family_relationship' => $this->hubungan_keluarga_dekat ?? null,
            'family_phone' => $this->nohp_keluarga_dekat ?? null,
            'family_address' => $this->alamat_keluarga_dekat ?? null,
            'emergency_contact' => [
                'name' => $this->nama_keluarga_dekat ?? '',
                'relationship' => $this->hubungan_keluarga_dekat ?? '',
                'phone' => $this->nohp_keluarga_dekat ?? '',
                'address' => $this->alamat_keluarga_dekat ?? '',
            ],
            'status' => $statusLabel,
            'notes' => $this->alasan_datang ?? $this->keterangan ?? '',
            'visit_id' => $latestVisit ? (int) $latestVisit->id : null,
            'created_at' => $this->created_at,
        ];
    }
}
