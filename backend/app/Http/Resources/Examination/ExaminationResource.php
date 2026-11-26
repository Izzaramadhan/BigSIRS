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

        // Real queue number or '—'
        $queueNumber = '—';
        if ($this->relationLoaded('queue') && !empty($this->queue?->no_antrian)) {
            $queueNumber = (string) $this->queue->no_antrian;
        } elseif ($admission && $admission->relationLoaded('queue') && !empty($admission->queue?->no_antrian)) {
            $queueNumber = (string) $admission->queue->no_antrian;
        }

        $poliName = $polyclinic->nama ?? $polyclinic->name ?? $polyclinic->nama_poliklinik ?? 'Poli Umum';
        $docName = $doctorPerson->nama ?? $doctor->nama ?? $doctor->nama_lengkap ?? $doctor->name ?? null;
        $guarantorName = $guarantor->jenis_asuransi ?? $guarantor->name ?? 'UMUM';
        $guarantorType = $guarantor->type ?? $guarantor->jenis_asuransi ?? 'UMUM';

        $data = [
            'id' => (int) $this->id,
            'visit_id' => (int) $this->id,
            'admission_id' => $admission ? (int) $admission->id : null,
            'patient_id' => $patient ? (int) $patient->id : null,
            'medical_record_number' => $patient->no_rm ?? null,
            'patient_name' => $person->nama ?? null,
            'nik' => $person && $person->no_ktp !== null ? (string) $person->no_ktp : null,
            'gender' => $person->gender ?? null,
            'birth_place' => $person->tempat_lahir ?? null,
            'birth_date' => $birthDate,
            'age' => $age,
            'phone' => $person->no_hp ?? null,
            'address' => $person->alamat ?? null,
            'polyclinic_id' => $polyclinic ? (int) $polyclinic->id : null,
            'polyclinic' => $poliName,
            'doctor_id' => $doctor ? (int) $doctor->id : null,
            'doctor' => $docName,
            'guarantor_id' => $guarantor ? (int) $guarantor->id : null,
            'guarantor' => $guarantorName,
            'guarantor_type' => $guarantorType,
            'queue_number' => $queueNumber,
            'visit_date' => $visitDate,
            'visit_time' => $visitTime,
            'status' => $statusLabel,
            'complaint' => $admission->alasan_datang ?? $admission->keterangan ?? '',
            'created_at' => $this->created_at,
            'patient' => [
                'id' => $patient ? (int) $patient->id : null,
                'medical_record_number' => $patient->no_rm ?? null,
                'name' => $person->nama ?? null,
                'nik' => $person && $person->no_ktp !== null ? (string) $person->no_ktp : null,
                'gender' => $person->gender ?? null,
                'birth_place' => $person->tempat_lahir ?? null,
                'birth_date' => $birthDate,
                'address' => $person->alamat ?? null,
                'phone' => $person->no_hp ?? null,
                'allergy_status' => $patient->alergi ?? 'tidak',
                'allergy_note' => $patient->ket_alergi ?? null,
            ],
            'polyclinic_info' => [
                'id' => $polyclinic ? (int) $polyclinic->id : null,
                'name' => $poliName,
            ],
            'doctor_info' => [
                'id' => $doctor ? (int) $doctor->id : null,
                'name' => $docName,
            ],
            'guarantor_info' => [
                'id' => $guarantor ? (int) $guarantor->id : null,
                'name' => $guarantorName,
                'type' => $guarantorType,
            ],
            'allergy_note' => ($patient && ($patient->alergi === 'ada' || !empty($patient->ket_alergi)))
                ? ($patient->ket_alergi ?: 'Ada alergi tercatat')
                : '',
            'visit_history' => $this->additional_history ?? [],
        ];

        // Detailed clinical relations for show endpoint
        if ($this->relationLoaded('vitalSign')) {
            $vital = $this->vitalSign;
            $data['vitals'] = $vital ? [
                'systole' => $vital->sistole ? (string) (int) $vital->sistole : '',
                'diastole' => $vital->diastole ? (string) (int) $vital->diastole : '',
                'blood_pressure' => ($vital->sistole && $vital->diastole)
                    ? ((int) $vital->sistole . '/' . (int) $vital->diastole)
                    : ($vital->nilai_tensi ?? ''),
                'heart_rate' => $vital->nadi ? (string) (int) $vital->nadi : '',
                'temperature' => $vital->suhu ? (string) $vital->suhu : '',
                'respiratory_rate' => $vital->pernafasan ? (string) (int) $vital->pernafasan : '',
                'spo2' => $vital->spo2 ? (string) (int) $vital->spo2 : '',
                'weight' => $vital->berat_badan ? (string) $vital->berat_badan : '',
                'height' => $vital->tinggi_badan ? (string) $vital->tinggi_badan : '',
                'waist_circumference' => $vital->lingkar_pinggang ? (string) $vital->lingkar_pinggang : '',
                'gcs' => $vital->skor_gcs ? (string) $vital->skor_gcs : 'E4V5M6 (Compos Mentis)',
            ] : null;
        }

        if ($this->relationLoaded('clinicalExamination')) {
            $exam = $this->clinicalExamination;
            $data['clinical_examination'] = $exam ? [
                'anamnesa' => $exam->subjek_keluhan ?? $exam->subjektif ?? '',
                'riwayat_penyakit_sekarang' => $exam->riwayat_penyakit_sekarang ?? '',
                'riwayat_penyakit_dahulu' => $exam->riwayat_penyakit_dahulu ?? '',
                'riwayat_alergi' => $exam->ket_riwayat_alergi_obat_makanan ?? '',
                'pemeriksaan_fisik' => $exam->pemeriksaan_fisik ?? '',
            ] : null;
            $data['clinical_note'] = $exam?->subjek_keluhan ?? $exam?->subjektif ?? '';
            if ($exam && ($exam->riwayat_alergi_obat_makanan === 'Ada' || !empty($exam->ket_riwayat_alergi_obat_makanan))) {
                $data['allergy_note'] = $exam->ket_riwayat_alergi_obat_makanan ?: ($exam->reaksi_alergi ?: 'Ada alergi tercatat');
            }
            $data['soap'] = $exam ? [
                'subjective' => $exam->subjektif ?? $exam->subjek_keluhan ?? '',
                'objective' => $exam->objektif ?? $exam->pemeriksaan_fisik ?? '',
                'assessment' => $exam->ket_diagnosis_mulut ?? '',
                'plan' => $exam->planning ?? $exam->instruksi_pulang ?? '',
            ] : null;
            $data['physical'] = $exam ? [
                'general' => $exam->keadaan_umum ?? '',
                'consciousness' => $exam->kesadaran ?? '',
                'head' => $exam->kepala ?? '',
                'eye' => '',
                'ent' => '',
                'mouth' => $exam->keadaan_rongga_mulut ?? '',
                'neck' => $exam->leher ?? '',
                'chest' => $exam->thorax ?? '',
                'heart' => $exam->cor ?? '',
                'lungs' => $exam->pulmo ?? '',
                'abdomen' => $exam->abdomen ?? '',
                'extremities' => $exam->ekstremitas_all ?? '',
                'skin' => $exam->kulit ?? '',
                'other' => $exam->ket_lain ?? '',
            ] : null;
        }

        $data['diagnoses'] = $this->relationLoaded('diagnoses') ? $this->diagnoses->map(function ($diag) {
            return [
                'id' => (int) $diag->id,
                'code' => $diag->diagnosa?->code ?? '',
                'name' => $diag->diagnosa?->nama ?? '',
                'type' => $diag->kategori === 'Primer' ? 'Utama' : 'Sekunder',
                'case_type' => $diag->kasus ?? 'Kasus Baru',
                'notes' => $diag->catatan ?? '',
            ];
        })->values()->all() : [];

        $data['icd9List'] = $this->relationLoaded('icd9Procedures') ? $this->icd9Procedures->map(function ($proc) {
            return [
                'id' => (int) $proc->id,
                'code' => $proc->icd9?->code ?? '',
                'name' => $proc->icd9?->nama ?? '',
            ];
        })->values()->all() : [];

        $data['procedures'] = $this->relationLoaded('procedures') ? $this->procedures->map(function ($proc) {
            $tariff = $proc->tindakan;
            $price = $tariff ? (int) $tariff->harga : 0;
            $qty = (int) ($proc->qty ?? 1);
            $discount = (int) ($proc->diskon ?? 0);
            $subtotal = max(0, ($price * $qty) - $discount);

            return [
                'id' => (int) $proc->id,
                'procedure_id' => $tariff ? (int) $tariff->id : null,
                'code' => $tariff->kode ?? '',
                'name' => $tariff->nama ?? $proc->nama_tindakan ?? '',
                'price' => $price,
                'qty' => $qty,
                'discount' => $discount,
                'subtotal' => $subtotal,
                'notes' => $proc->keterangan ?? '',
            ];
        })->values()->all() : [];

        $pharmacy = [];
        if ($this->relationLoaded('prescriptions')) {
            foreach ($this->prescriptions as $rx) {
                foreach ($rx->details as $item) {
                    $pharmacy[] = [
                        'id' => (int) $item->id,
                        'prescription_id' => (int) $rx->id,
                        'medicine_name' => $item->nama_obat ?? '',
                        'quantity' => (int) $item->jml_item,
                        'unit' => $item->id_satuan ?? 'Tablet',
                        'signa' => $item->signa ?? '',
                        'instruction' => $item->aturan_pakai ?? '',
                        'notes' => $item->catatan ?? '',
                        'status' => $rx->status_resep ?? 'order',
                    ];
                }
            }
        }
        $data['prescriptions'] = $pharmacy;

        $labOrders = $this->relationLoaded('labOrders') ? $this->labOrders->flatMap(function ($lab) {
            return $lab->details->map(function ($detail) use ($lab) {
                return [
                    'id' => (int) $detail->id,
                    'order_id' => (int) $lab->id,
                    'name' => $detail->nama_grup_lab ?? $detail->item?->nama ?? '',
                    'result' => $detail->hasil ?? '',
                    'status' => $detail->status_pemeriksaan ?? $lab->status_pemeriksaan ?? 'proses',
                    'notes' => $detail->catatan ?? '',
                ];
            });
        })->values()->all() : [];
        $data['lab_orders'] = $labOrders;

        $radOrders = $this->relationLoaded('radiologyOrders') ? $this->radiologyOrders->flatMap(function ($rad) {
            return $rad->details->map(function ($detail) use ($rad) {
                return [
                    'id' => (int) $detail->id,
                    'order_id' => (int) $rad->id,
                    'name' => $detail->item?->nama ?? '',
                    'result' => $detail->hasil ?? '',
                    'status' => $rad->status_pemeriksaan ?? 'proses',
                    'notes' => $detail->catatan ?? '',
                ];
            });
        })->values()->all() : [];
        $data['radiology_orders'] = $radOrders;

        $data['orders'] = [
            'pharmacy' => $pharmacy,
            'racikan' => [],
            'lab' => $labOrders,
            'radiology' => $radOrders,
            'consult' => [],
        ];

        if (isset($this->additional_history) && is_array($this->additional_history)) {
            $data['visit_history'] = $this->additional_history;
        }

        return $data;
    }
}
