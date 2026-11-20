<?php

namespace App\Http\Resources\Registration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $patient = $this->resource['patient'];
        $refs = $this->resource['references'] ?? [];
        $person = $patient->person;
        $guarantor = $patient->guarantor;

        $birthDate = null;
        if ($person && $person->tgl_lahir) {
            $birthDate = $person->tgl_lahir instanceof \DateTimeInterface
                ? $person->tgl_lahir->format('Y-m-d')
                : (string) $person->tgl_lahir;
        }

        return [
            'id' => (int) $patient->id,
            'person_id' => $patient->id_mpi !== null ? (int) $patient->id_mpi : null,
            'medical_record_number' => $patient->no_rm,
            'nik' => $person && $person->no_ktp !== null ? (string) $person->no_ktp : null,
            'ihs_number' => $person->ihs_id ?? null,
            'name' => $person->nama ?? null,
            'birth_place' => $person->tempat_lahir ?? null,
            'birth_date' => $birthDate,
            'gender' => $person->gender ?? null,
            'nationality' => $person->kebangsaan ?? null,
            'blood_type' => $person->gol_darah ?? null,
            'religion' => $person->agama ?? null,
            'marital_status' => $person->status_perkawinan ?? null,
            'phone' => $person->no_hp ?? null,
            'email' => $person->email ?? null,
            'address' => $person->alamat ?? null,
            'postal_code' => $person->kode_pos ?? null,
            'region' => [
                'province' => $refs['province'] ?? null,
                'regency' => $refs['regency'] ?? null,
                'district' => $refs['district'] ?? null,
                'village' => $refs['village'] ?? null,
            ],
            'education' => $refs['education'] ?? null,
            'occupation' => $refs['occupation'] ?? null,
            'guarantor' => $guarantor ? [
                'id' => (int) $guarantor->id,
                'name' => $guarantor->jenis_asuransi ?? $guarantor->name ?? null,
                'type' => $guarantor->type ?? null,
            ] : null,
            'insurance_number' => $patient->no_bpjs ?? null,
            'hak_kelas' => $person->hak_kelas ?? null,
            'allergies' => [
                'status' => $patient->alergi ?? 'tidak',
                'notes' => $patient->ket_alergi ?? null,
            ],
            'fast_track' => ($patient->fast_track ?? 'tidak') === 'ya',
            'emergency_contact' => [
                'name' => $person->nama_keluarga_dekat ?? null,
                'relationship' => $person->hubungan_keluarga_dekat ?? null,
                'phone' => $person->nohp_keluarga_dekat ?? null,
                'address' => $person->alamat_keluarga_dekat ?? null,
            ],
            'registered_at' => $patient->created_at ? (string) $patient->created_at : null,
            'is_active' => (string) $patient->status === '1',
            'last_visit' => $this->resource['last_visit'] ?? null,
        ];
    }
}

