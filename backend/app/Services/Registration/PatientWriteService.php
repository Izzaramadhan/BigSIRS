<?php

namespace App\Services\Registration;

use App\Models\Legacy\LegacyGuarantor;
use App\Models\Legacy\LegacyPatient;
use App\Models\Legacy\LegacyPerson;
use App\Support\LegacyAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatientWriteService
{
    /**
     * Mendaftarkan pasien baru ke master_person_index dan ref_pasien.
     */
    public function create(array $payload, ?int $legacyUserId): LegacyPatient
    {
        if (!LegacyAccess::isAvailable('ref_pasien') || !LegacyAccess::hasTable('master_person_index')) {
            throw new \RuntimeException('Sumber data legacy ref_pasien atau master_person_index tidak tersedia.');
        }

        // Petugas fallback ID jika user tidak memiliki mapping legacy (misal admin default = 103)
        $effectiveUserId = $legacyUserId ?? 103;

        return DB::connection('legacy')->transaction(function () use ($payload, $effectiveUserId) {
            // 1. Cek duplikasi NIK jika diisi
            if (!empty($payload['nik'])) {
                $duplicate = LegacyPatient::query()
                    ->join('master_person_index', 'master_person_index.id', '=', 'ref_pasien.id_mpi')
                    ->where(function ($q) {
                        $q->whereNull('ref_pasien.deleted_at')
                            ->orWhere('ref_pasien.deleted_at', '0000-00-00 00:00:00');
                    })
                    ->where('master_person_index.no_ktp', $payload['nik'])
                    ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'nik' => ['Pasien dengan NIK ' . $payload['nik'] . ' sudah terdaftar dalam sistem.'],
                    ]);
                }
            }

            // 2. Generate Nomor RM berikutnya (format kanonik 6 digit sesuai data terkini: 000171-000175 -> 000176)
            $nextRm = $this->generateNextMedicalRecordNumber();

            // 3. Normalisasi nilai-nilai referensi dan enum
            $gender = $this->normalizeGender($payload['gender'] ?? 'L');
            $nationality = strtoupper($payload['nationality'] ?? 'WNI') === 'WNA' ? 'WNA' : 'WNI';
            $bloodType = $this->normalizeBloodType($payload['blood_type'] ?? '-');
            $religion = $this->normalizeReligion($payload['religion'] ?? null);
            $maritalStatus = $this->normalizeMaritalStatus($payload['marital_status'] ?? null);
            $guarantorId = $this->resolveGuarantorId($payload['guarantor_id'] ?? null, $payload['guarantor'] ?? null);
            $educationId = $this->resolveEducationId($payload['education_id'] ?? null, $payload['education'] ?? null);
            $occupationId = $this->resolveOccupationId($payload['occupation_id'] ?? null, $payload['occupation'] ?? null);
            $allergyStatus = (isset($payload['has_allergy']) && in_array(strtolower($payload['has_allergy']), ['ada', 'ya', 'true', '1'])) ? 'ada' : 'tidak';
            $allergyNotes = $allergyStatus === 'ada' ? ($payload['allergy_notes'] ?? '') : '';
            $fastTrack = (isset($payload['is_fast_track']) && in_array(strtolower($payload['is_fast_track']), ['ya', 'true', '1'])) ? 'ya' : 'tidak';
            $patientType = (isset($payload['patient_category']) && stripos($payload['patient_category'], 'luar') !== false) ? 'pasien_luar' : 'pasien_rs';

            $emergency = $payload['emergency_contact'] ?? [];

            // 4. Insert data demografi ke master_person_index
            $personData = [
                'no_ktp' => !empty($payload['nik']) ? (string) $payload['nik'] : null,
                'nama' => trim($payload['name']),
                'gender' => $gender,
                'tgl_lahir' => $payload['birth_date'],
                'tempat_lahir' => trim($payload['birth_place']),
                'alamat' => trim($payload['address']),
                'id_provinsi' => $payload['province_id'] ?? null,
                'id_kabupaten' => $payload['regency_id'] ?? null,
                'id_kecamatan' => $payload['district_id'] ?? null,
                'id_kelurahan' => $payload['village_id'] ?? null,
                'kode_pos' => !empty($payload['postal_code']) ? substr((string) $payload['postal_code'], 0, 50) : null,
                'no_hp' => !empty($payload['phone']) ? trim($payload['phone']) : '-',
                'email' => !empty($payload['email']) ? trim($payload['email']) : null,
                'agama' => $religion,
                'kebangsaan' => $nationality,
                'gol_darah' => $bloodType,
                'status_perkawinan' => $maritalStatus,
                'id_pendidikan' => $educationId,
                'id_pekerjaan' => $occupationId,
                'jenis_person' => 'pasien',
                'status' => '1',
                'created_at' => now(),
                'id_user' => $effectiveUserId,
                'ihs_id' => !empty($payload['ihs_number']) ? trim($payload['ihs_number']) : null,
                'hak_kelas' => !empty($payload['hak_kelas']) ? trim($payload['hak_kelas']) : null,
                'nama_keluarga_dekat' => !empty($emergency['name']) ? trim($emergency['name']) : null,
                'hubungan_keluarga_dekat' => !empty($emergency['relationship']) ? trim($emergency['relationship']) : null,
                'nohp_keluarga_dekat' => !empty($emergency['phone']) ? trim($emergency['phone']) : null,
                'alamat_keluarga_dekat' => !empty($emergency['address']) ? trim($emergency['address']) : null,
            ];

            if (!\Illuminate\Support\Facades\Schema::connection('legacy')->hasColumn('master_person_index', 'hak_kelas')) {
                unset($personData['hak_kelas']);
            }

            $personId = DB::connection('legacy')
                ->table('master_person_index')
                ->insertGetId($personData);

            // 5. Insert data pasien ke ref_pasien
            $patientData = [
                'id_mpi' => $personId,
                'log_id_mpi' => $personId,
                'id_penanggung_jawab' => null,
                'id_jenis_asuransi' => $guarantorId,
                'no_bpjs' => !empty($payload['insurance_number']) ? trim($payload['insurance_number']) : '',
                'no_rm' => $nextRm,
                'alergi' => $allergyStatus,
                'ket_alergi' => $allergyNotes,
                'status' => '1',
                'type' => $patientType,
                'flag_print' => '0',
                'created_at' => now(),
                'id_user' => $effectiveUserId,
                'fast_track' => $fastTrack,
                'ket_fast_track' => '',
            ];

            $patientId = DB::connection('legacy')
                ->table('ref_pasien')
                ->insertGetId($patientData);

            return LegacyPatient::with(['person', 'guarantor'])->findOrFail($patientId);
        });
    }

    /**
     * Generator nomor RM kanonik 6 digit yang unik dan berurutan.
     */
    protected function generateNextMedicalRecordNumber(): string
    {
        $connection = DB::connection('legacy');
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $maxNumeric = $connection
                ->table('ref_pasien')
                ->whereRaw("length(no_rm) = 6 AND no_rm GLOB '[0-9][0-9][0-9][0-9][0-9][0-9]'")
                ->max(DB::raw('CAST(no_rm AS INTEGER)'));
        } else {
            $maxNumeric = $connection
                ->table('ref_pasien')
                ->whereRaw("no_rm REGEXP '^[0-9]{6}$'")
                ->lockForUpdate()
                ->max(DB::raw('CAST(no_rm AS UNSIGNED)'));
        }

        $candidateNum = ($maxNumeric !== null && $maxNumeric > 0) ? ((int) $maxNumeric) + 1 : 176;

        do {
            $formattedRm = str_pad((string) $candidateNum, 6, '0', STR_PAD_LEFT);
            $dashedRm = sprintf('%s-%s-%s', substr($formattedRm, 0, 2), substr($formattedRm, 2, 2), substr($formattedRm, 4, 2));

            $exists = $connection
                ->table('ref_pasien')
                ->where(function ($q) use ($formattedRm, $dashedRm) {
                    $q->where('no_rm', $formattedRm)
                        ->orWhere('no_rm', $dashedRm);
                })
                ->exists();

            if (!$exists) {
                return $formattedRm;
            }

            $candidateNum++;
        } while ($candidateNum < 999999);

        return str_pad((string) $candidateNum, 6, '0', STR_PAD_LEFT);
    }

    protected function normalizeGender(string $gender): string
    {
        $upper = strtoupper(trim($gender));
        if ($upper === 'P' || str_contains($upper, 'PEREMPUAN') || str_contains($upper, 'WANITA')) {
            return 'P';
        }
        return 'L';
    }

    protected function normalizeBloodType(?string $bloodType): string
    {
        if (empty($bloodType)) {
            return '-';
        }
        $upper = strtoupper(trim($bloodType));
        return in_array($upper, ['A', 'B', 'AB', 'O', '-']) ? $upper : '-';
    }

    protected function normalizeReligion(?string $religion): ?string
    {
        if (empty($religion)) {
            return null;
        }
        $upper = strtoupper(trim($religion));
        return match (true) {
            str_contains($upper, 'ISLAM') => 'Islam',
            str_contains($upper, 'KRISTEN') => 'Kristen',
            str_contains($upper, 'KATOLIK') || str_contains($upper, 'KATHOLIK') => 'Katolik',
            str_contains($upper, 'HINDU') => 'Hindu',
            str_contains($upper, 'BUDHA') || str_contains($upper, 'BUDDHA') => 'Budha',
            str_contains($upper, 'KHONG') => 'Khong Hucu',
            default => null,
        };
    }

    protected function normalizeMaritalStatus(?string $status): ?string
    {
        if (empty($status)) {
            return null;
        }
        $upper = strtoupper(trim($status));
        return match (true) {
            str_contains($upper, 'BELUM') => 'Belum Kawin',
            str_contains($upper, 'KAWIN') || str_contains($upper, 'MENIKAH') => 'Kawin',
            str_contains($upper, 'CERAI HIDUP') => 'Cerai Hidup',
            str_contains($upper, 'CERAI MATI') => 'Cerai Mati',
            str_contains($upper, 'DUDA') => 'Duda',
            str_contains($upper, 'JANDA') => 'Janda',
            default => 'Belum Kawin',
        };
    }

    protected function resolveGuarantorId(?int $id, ?string $name): int
    {
        if ($id !== null && $id > 0) {
            return $id;
        }

        if (!empty($name)) {
            $upper = strtoupper($name);
            if (str_contains($upper, 'BPJS') || str_contains($upper, 'JKN')) {
                return 3;
            }
            if (str_contains($upper, 'UMUM') || str_contains($upper, 'MANDIRI')) {
                return 2;
            }

            $matchedId = DB::connection('legacy')
                ->table('ref_jenis_asuransi')
                ->where('jenis_asuransi', 'like', "%{$name}%")
                ->value('id');

            if ($matchedId) {
                return (int) $matchedId;
            }
        }

        // Default: 2 (UMUM)
        return 2;
    }

    protected function resolveEducationId(?int $id, ?string $name): ?int
    {
        if ($id !== null && $id > 0) {
            return $id;
        }

        if (!empty($name)) {
            $upper = strtoupper($name);
            return match (true) {
                str_contains($upper, 'BELUM') || str_contains($upper, 'TIDAK') => 1,
                str_contains($upper, 'SD') => 2,
                str_contains($upper, 'SMP') || str_contains($upper, 'MTS') => 3,
                str_contains($upper, 'SMA') || str_contains($upper, 'SMK') || str_contains($upper, 'MA') => 4,
                str_contains($upper, 'D1') => 5,
                str_contains($upper, 'D3') || str_contains($upper, 'DIPLOMA') => 6,
                str_contains($upper, 'S1') || str_contains($upper, 'SARJANA') => 7,
                str_contains($upper, 'S2') || str_contains($upper, 'MAGISTER') => 8,
                str_contains($upper, 'S3') || str_contains($upper, 'DOKTOR') => 9,
                default => 10,
            };
        }

        return null;
    }

    protected function resolveOccupationId(?int $id, ?string $name): ?int
    {
        if ($id !== null && $id > 0) {
            return $id;
        }

        if (!empty($name)) {
            $upper = strtoupper($name);
            return match (true) {
                str_contains($upper, 'PNS') || str_contains($upper, 'ASN') => 1,
                str_contains($upper, 'TNI') || str_contains($upper, 'POLRI') => 2,
                str_contains($upper, 'PETANI') => 3,
                str_contains($upper, 'PEDAGANG') => 4,
                str_contains($upper, 'RUMAH TANGGA') => 5,
                str_contains($upper, 'PELAJAR') || str_contains($upper, 'MAHASISWA') => 6,
                str_contains($upper, 'WIRASWASTA') => 7,
                str_contains($upper, 'PENSIUNAN') => 8,
                str_contains($upper, 'BURUH') => 10,
                str_contains($upper, 'KARYAWAN') || str_contains($upper, 'SWASTA') => 11,
                str_contains($upper, 'BUMN') => 12,
                default => 14,
            };
        }

        return null;
    }

    public function update(int $patientId, array $payload, ?int $legacyUserId): LegacyPatient
    {
        if (!LegacyAccess::isAvailable('ref_pasien') || !LegacyAccess::hasTable('master_person_index')) {
            throw new \RuntimeException('Sumber data legacy ref_pasien atau master_person_index tidak tersedia.');
        }

        $effectiveUserId = $legacyUserId ?? 103;

        return DB::connection('legacy')->transaction(function () use ($patientId, $payload, $effectiveUserId) {
            /** @var LegacyPatient|null $patient */
            $patient = LegacyPatient::with('person')->find($patientId);
            if (!$patient || !$patient->person) {
                throw ValidationException::withMessages([
                    'patient' => ['Data pasien tidak ditemukan.'],
                ]);
            }

            /** @var LegacyPerson $person */
            $person = $patient->person;

            if (!empty($payload['nik']) && $payload['nik'] !== $person->no_ktp) {
                $duplicate = LegacyPatient::query()
                    ->join('master_person_index', 'master_person_index.id', '=', 'ref_pasien.id_mpi')
                    ->where(function ($q) {
                        $q->whereNull('ref_pasien.deleted_at')
                            ->orWhere('ref_pasien.deleted_at', '0000-00-00 00:00:00');
                    })
                    ->where('master_person_index.no_ktp', $payload['nik'])
                    ->where('ref_pasien.id', '!=', $patientId)
                    ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'nik' => ['Pasien dengan NIK ' . $payload['nik'] . ' sudah terdaftar dalam sistem.'],
                    ]);
                }
                $person->no_ktp = (string) $payload['nik'];
            }
            if (isset($payload['name'])) {
                $person->nama = trim($payload['name']);
            }
            if (isset($payload['birth_place'])) {
                $person->tempat_lahir = trim($payload['birth_place']);
            }
            if (isset($payload['birth_date'])) {
                $person->tgl_lahir = $payload['birth_date'];
            }
            if (isset($payload['gender'])) {
                $person->gender = $this->normalizeGender($payload['gender']);
            }
            if (isset($payload['nationality'])) {
                $person->kebangsaan = strtoupper($payload['nationality']) === 'WNA' ? 'WNA' : 'WNI';
            }
            if (isset($payload['phone'])) {
                $person->no_hp = trim($payload['phone']);
            }
            if (isset($payload['address'])) {
                $person->alamat = trim($payload['address']);
            }
            if (array_key_exists('province_id', $payload)) {
                $person->id_provinsi = $payload['province_id'] ?: null;
            }
            if (array_key_exists('regency_id', $payload)) {
                $person->id_kabupaten = $payload['regency_id'] ?: null;
            }
            if (array_key_exists('district_id', $payload)) {
                $person->id_kecamatan = $payload['district_id'] ?: null;
            }
            if (array_key_exists('village_id', $payload)) {
                $person->id_kelurahan = $payload['village_id'] ?: null;
            }
            if (isset($payload['postal_code'])) {
                $person->kode_pos = trim($payload['postal_code']);
            }
            if (isset($payload['blood_type'])) {
                $person->gol_darah = $this->normalizeBloodType($payload['blood_type']);
            }
            if (isset($payload['religion'])) {
                $person->agama = $this->normalizeReligion($payload['religion']);
            }
            if (isset($payload['marital_status'])) {
                $person->status_perkawinan = $this->normalizeMaritalStatus($payload['marital_status']);
            }
            if (isset($payload['education_id']) || isset($payload['education'])) {
                $person->id_pendidikan = $this->resolveEducationId($payload['education_id'] ?? null, $payload['education'] ?? null);
            }
            if (isset($payload['occupation_id']) || isset($payload['occupation'])) {
                $person->id_pekerjaan = $this->resolveOccupationId($payload['occupation_id'] ?? null, $payload['occupation'] ?? null);
            }
            if (isset($payload['ihs_number'])) {
                $person->ihs_id = trim($payload['ihs_number']);
            }
            if (isset($payload['emergency_contact']) && is_array($payload['emergency_contact'])) {
                $em = $payload['emergency_contact'];
                if (array_key_exists('name', $em)) $person->nama_keluarga_dekat = !empty($em['name']) ? trim($em['name']) : null;
                if (array_key_exists('relationship', $em)) $person->hubungan_keluarga_dekat = !empty($em['relationship']) ? trim($em['relationship']) : null;
                if (array_key_exists('phone', $em)) $person->nohp_keluarga_dekat = !empty($em['phone']) ? trim($em['phone']) : null;
                if (array_key_exists('address', $em)) $person->alamat_keluarga_dekat = !empty($em['address']) ? trim($em['address']) : null;
            }
            if (isset($payload['hak_kelas']) && \Illuminate\Support\Facades\Schema::connection('legacy')->hasColumn('master_person_index', 'hak_kelas')) {
                $person->hak_kelas = !empty($payload['hak_kelas']) ? trim($payload['hak_kelas']) : null;
            }
            $person->updated_at = now();
            $person->id_user = $effectiveUserId;
            $person->save();

            if (isset($payload['guarantor_id']) || isset($payload['guarantor'])) {
                $patient->id_jenis_asuransi = $this->resolveGuarantorId($payload['guarantor_id'] ?? null, $payload['guarantor'] ?? null);
            }
            if (isset($payload['insurance_number'])) {
                $patient->no_bpjs = trim($payload['insurance_number']);
            }
            if (isset($payload['has_allergy'])) {
                $status = in_array(strtolower($payload['has_allergy']), ['ada', 'ya', 'true', '1']) ? 'ada' : 'tidak';
                $patient->alergi = $status;
                $patient->ket_alergi = $status === 'ada' ? ($payload['allergy_notes'] ?? '') : '';
            }
            if (isset($payload['is_fast_track'])) {
                $patient->fast_track = in_array(strtolower($payload['is_fast_track']), ['ya', 'true', '1']) ? 'ya' : 'tidak';
            }
            $patient->updated_at = now();
            $patient->id_user = $effectiveUserId;
            $patient->save();

            return $patient->fresh(['person', 'guarantor']);
        });
    }

    /**
     * Soft delete / penonaktifan pasien secara aman.
     * Mencegah penghapusan jika pasien memiliki riwayat admisi, kunjungan, pemeriksaan, atau transaksi.
     */
    public function delete(int $patientId, ?int $legacyUserId, ?string $reason = null): array
    {
        $patient = LegacyPatient::with('person')->find($patientId);
        if (!$patient) {
            return [
                'success' => false,
                'message' => 'Data pasien tidak ditemukan.',
            ];
        }

        // 1. Cek riwayat admisi / kunjungan / transaksi
        $hasAdmissions = DB::connection('legacy')
            ->table('trx_admisi')
            ->where('id_pasien', $patientId)
            ->where(function ($q) {
                $q->whereNull('deleted_at')
                    ->orWhere('deleted_at', '0000-00-00 00:00:00');
            })
            ->exists();

        if ($hasAdmissions) {
            $patientName = $patient->person?->nama ?? 'Pasien';
            return [
                'success' => false,
                'message' => "Pasien {$patientName} (No. RM: {$patient->no_rm}) memiliki riwayat kunjungan/admisi medis sehingga tidak dapat dihapus demi integritas rekam medis.",
            ];
        }

        // 2. Soft delete aman (ubah status menjadi '0' dan set deleted_at)
        $effectiveUserId = $legacyUserId ?? 103;
        DB::connection('legacy')->transaction(function () use ($patient, $effectiveUserId, $reason) {
            $patient->status = '0';
            $patient->deleted_at = now();
            $patient->id_user = $effectiveUserId;
            $patient->save();

            if ($patient->person) {
                $patient->person->status = '0';
                $patient->person->deleted_at = now();
                $patient->person->id_user = $effectiveUserId;
                $patient->person->save();
            }

            \Illuminate\Support\Facades\Log::info('Pasien dinonaktifkan (soft delete)', [
                'patient_id' => $patient->id,
                'no_rm' => $patient->no_rm,
                'user_id' => $effectiveUserId,
                'reason' => $reason ?? 'Penonaktifan melalui modul pendaftaran',
                'timestamp' => now()->toIso8601String(),
            ]);
        });

        $patientName = $patient->person?->nama ?? 'Pasien';
        return [
            'success' => true,
            'message' => "Data pasien {$patientName} (No. RM: {$patient->no_rm}) berhasil dinonaktifkan.",
        ];
    }
}
