<?php

namespace App\Services\Registration;

use App\Models\Legacy\LegacyAdmission;
use App\Models\Legacy\LegacyDistrict;
use App\Models\Legacy\LegacyDoctor;
use App\Models\Legacy\LegacyEducation;
use App\Models\Legacy\LegacyOccupation;
use App\Models\Legacy\LegacyPatient;
use App\Models\Legacy\LegacyPolyclinic;
use App\Models\Legacy\LegacyProvince;
use App\Models\Legacy\LegacyRegency;
use App\Models\Legacy\LegacyVillage;
use App\Support\LegacyAccess;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PatientService
{
    /**
     * Cari pasien aktif (read-only) dari koneksi legacy (ref_pasien -> master_person_index).
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 10);
        $perPage = max(1, min(50, $perPage));
        $page = isset($filters['page']) ? max(1, (int) $filters['page']) : null;

        $query = LegacyPatient::query()
            ->select('ref_pasien.*')
            ->join('master_person_index', 'master_person_index.id', '=', 'ref_pasien.id_mpi')
            ->with(['person', 'guarantor', 'latestAdmission.latestVisit.polyclinic', 'latestAdmission.guarantor'])
            ->active()
            ->where(function (Builder $q) {
                $q->whereNull('master_person_index.deleted_at')
                    ->orWhere('master_person_index.deleted_at', '0000-00-00 00:00:00')
                    ->orWhere('master_person_index.deleted_at', '0000-00-00');
            });

        $rawTerm = isset($filters['q']) ? trim((string) $filters['q']) : '';
        $term = $rawTerm !== '' ? (preg_replace('/\s+/u', ' ', $rawTerm) ?? $rawTerm) : '';
        if ($term !== '') {
            $like = '%' . mb_strtolower($term, 'UTF-8') . '%';
            $strippedRm = str_replace('-', '', $term);
            $likeStrippedRm = $strippedRm !== '' ? '%' . mb_strtolower($strippedRm, 'UTF-8') . '%' : null;

            $query->where(function (Builder $sub) use ($like, $likeStrippedRm) {
                $sub->whereRaw('LOWER(ref_pasien.no_rm) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(master_person_index.nama) LIKE ?', [$like])
                    ->orWhereRaw('CAST(master_person_index.no_ktp AS CHAR) LIKE ?', [$like]);

                if ($likeStrippedRm !== null) {
                    $sub->orWhereRaw("LOWER(REPLACE(ref_pasien.no_rm, '-', '')) LIKE ?", [$likeStrippedRm]);
                }
            });
        }

        if (!empty($filters['birth_date'])) {
            $query->whereDate('master_person_index.tgl_lahir', $filters['birth_date']);
        }

        $guarantor = isset($filters['guarantor_id']) && $filters['guarantor_id'] !== ''
            ? trim((string) $filters['guarantor_id'])
            : (isset($filters['guarantor']) ? trim((string) $filters['guarantor']) : '');

        if ($guarantor !== '' && $guarantor !== 'all') {
            if (is_numeric($guarantor)) {
                $guarantorId = (int) $guarantor;
                $query->where(function (Builder $sub) use ($guarantorId) {
                    $sub->where('ref_pasien.id_jenis_asuransi', $guarantorId)
                        ->orWhereHas('latestAdmission', function (Builder $aq) use ($guarantorId) {
                            $aq->where('id_jenis_asuransi', $guarantorId);
                        });
                });
            } elseif (strcasecmp($guarantor, 'BPJS Kesehatan') === 0 || strcasecmp($guarantor, 'BPJS') === 0) {
                $query->where(function (Builder $sub) {
                    $sub->whereHas('guarantor', function (Builder $gq) {
                        $gq->where('type', 'BPJS')
                            ->orWhere('jenis_asuransi', 'LIKE', '%BPJS%')
                            ->orWhere('jenis_asuransi', 'LIKE', '%JKN%');
                    })->orWhereHas('latestAdmission.guarantor', function (Builder $gq) {
                        $gq->where('type', 'BPJS')
                            ->orWhere('jenis_asuransi', 'LIKE', '%BPJS%')
                            ->orWhere('jenis_asuransi', 'LIKE', '%JKN%');
                    });
                });
            } elseif (strcasecmp($guarantor, 'UMUM') === 0) {
                $query->where(function (Builder $sub) {
                    $sub->whereHas('guarantor', function (Builder $gq) {
                        $gq->where('type', 'UMUM')
                            ->orWhere('jenis_asuransi', 'LIKE', '%UMUM%');
                    })->orWhereHas('latestAdmission.guarantor', function (Builder $gq) {
                        $gq->where('type', 'UMUM')
                            ->orWhere('jenis_asuransi', 'LIKE', '%UMUM%');
                    });
                });
            } elseif (strcasecmp($guarantor, 'Asuransi Swasta') === 0 || strcasecmp($guarantor, 'PRIVATE') === 0) {
                $query->where(function (Builder $sub) {
                    $sub->whereHas('guarantor', function (Builder $gq) {
                        $gq->where('type', 'PRIVATE')
                            ->orWhere('jenis_asuransi', 'LIKE', '%SWASTA%')
                            ->orWhere('jenis_asuransi', 'LIKE', '%ASURANSI%');
                    })->orWhereHas('latestAdmission.guarantor', function (Builder $gq) {
                        $gq->where('type', 'PRIVATE')
                            ->orWhere('jenis_asuransi', 'LIKE', '%SWASTA%')
                            ->orWhere('jenis_asuransi', 'LIKE', '%ASURANSI%');
                    });
                });
            } else {
                $query->where(function (Builder $sub) use ($guarantor) {
                    $sub->whereHas('guarantor', function (Builder $gq) use ($guarantor) {
                        $gq->where('jenis_asuransi', 'LIKE', "%{$guarantor}%")
                            ->orWhere('type', $guarantor);
                    })->orWhereHas('latestAdmission.guarantor', function (Builder $gq) use ($guarantor) {
                        $gq->where('jenis_asuransi', 'LIKE', "%{$guarantor}%")
                            ->orWhere('type', $guarantor);
                    });
                });
            }
        }

        return $query
            ->orderByDesc('ref_pasien.id')
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }

    /**
     * Ambil detail lengkap pasien aktif (read-only) beserta referensi wilayah, pendidikan, dan pekerjaan.
     */
    public function findDetail(int $id): ?array
    {
        $patient = LegacyPatient::query()
            ->select('ref_pasien.*')
            ->join('master_person_index', 'master_person_index.id', '=', 'ref_pasien.id_mpi')
            ->with(['person', 'guarantor', 'latestAdmission.latestVisit.polyclinic'])
            ->active()
            ->where(function (Builder $q) {
                $q->whereNull('master_person_index.deleted_at')
                    ->orWhere('master_person_index.deleted_at', '0000-00-00 00:00:00')
                    ->orWhere('master_person_index.deleted_at', '0000-00-00');
            })
            ->where('ref_pasien.id', $id)
            ->first();

        if (!$patient || !$patient->person) {
            return null;
        }

        $person = $patient->person;

        $province = null;
        if (!empty($person->id_provinsi) && $person->id_provinsi !== '00' && LegacyAccess::hasTable('ref_provinsi')) {
            $province = LegacyProvince::where('id', $person->id_provinsi)->first();
        }

        $regency = null;
        if (!empty($person->id_kabupaten) && $person->id_kabupaten !== '00' && LegacyAccess::hasTable('ref_kabupaten')) {
            $regency = LegacyRegency::where('id', $person->id_kabupaten)->first();
        }

        $district = null;
        if (!empty($person->id_kecamatan) && $person->id_kecamatan !== '00' && LegacyAccess::hasTable('ref_kecamatan')) {
            $district = LegacyDistrict::where('id', $person->id_kecamatan)->first();
        }

        $village = null;
        if (!empty($person->id_kelurahan) && $person->id_kelurahan !== '00' && LegacyAccess::hasTable('ref_kelurahan')) {
            $village = LegacyVillage::where('id', $person->id_kelurahan)
                ->orWhere('kode_kelurahan', $person->id_kelurahan)
                ->first();
        }

        $education = ($person->id_pendidikan && LegacyAccess::hasTable('ref_pendidikan'))
            ? LegacyEducation::find($person->id_pendidikan)
            : null;
        $occupation = ($person->id_pekerjaan && LegacyAccess::hasTable('ref_pekerjaan'))
            ? LegacyOccupation::find($person->id_pekerjaan)
            : null;

        $latestAdm = $patient->latestAdmission;
        $latestVisit = $latestAdm?->latestVisit;
        $lastVisitInfo = null;
        if ($latestAdm) {
            $lastVisitInfo = [
                'admission_id' => (int) $latestAdm->id,
                'admission_date' => $latestAdm->waktu_admisi ? (string) $latestAdm->waktu_admisi : null,
                'polyclinic_id' => $latestVisit?->id_poliklinik ? (int) $latestVisit->id_poliklinik : null,
                'polyclinic_name' => $latestVisit?->polyclinic?->nama ?? $latestVisit?->polyclinic?->name ?? null,
                'doctor_id' => $latestVisit?->id_dokter ? (int) $latestVisit->id_dokter : null,
                'doctor_name' => $latestVisit?->doctor?->name ?? null,
                'case_type' => $latestVisit?->kasus ?? null,
            ];
        }

        return [
            'patient' => $patient,
            'last_visit' => $lastVisitInfo,
            'references' => [
                'province' => $province ? ['id' => (string) $province->id, 'name' => $province->nama ?? $province->name] : null,
                'regency' => $regency ? ['id' => (string) $regency->id, 'name' => $regency->nama ?? $regency->name] : null,
                'district' => $district ? ['id' => (string) $district->id, 'name' => $district->nama ?? $district->name] : null,
                'village' => $village ? ['id' => (string) $village->id, 'name' => $village->nama ?? $village->name] : null,
                'education' => $education ? ['id' => (int) $education->id, 'name' => $education->pendidikan ?? $education->name] : null,
                'occupation' => $occupation ? ['id' => (int) $occupation->id, 'name' => $occupation->pekerjaan ?? $occupation->name] : null,
            ],
        ];
    }

    /**
     * Ambil riwayat admisi & kunjungan pasien (read-only) dari trx_admisi & trx_visit.
     */
    public function admissionsHistory(int $patientId, array $filters = []): ?LengthAwarePaginator
    {
        $patientExists = LegacyPatient::query()
            ->active()
            ->where('id', $patientId)
            ->exists();

        if (!$patientExists) {
            return null;
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min(50, $perPage));
        $page = isset($filters['page']) ? max(1, (int) $filters['page']) : null;

        return LegacyAdmission::query()
            ->where('id_pasien', $patientId)
            ->where(function (Builder $q) {
                $q->whereNull('deleted_at')
                    ->orWhere('deleted_at', '0000-00-00 00:00:00')
                    ->orWhere('deleted_at', '0000-00-00');
            })
            ->with([
                'guarantor',
                'visits' => function ($vq) {
                    $vq->where(function (Builder $sq) {
                        $sq->whereNull('deleted_at')
                            ->orWhere('deleted_at', '0000-00-00 00:00:00')
                            ->orWhere('deleted_at', '0000-00-00');
                    })->orderByDesc('id');
                },
                'visits.polyclinic',
                'visits.doctor.person'
            ])
            ->orderByDesc('waktu_admisi')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }

    /**
     * Ambil kronologi timeline pelayanan pasien (read-only) dari seluruh transaksi nyata.
     * Meliputi: Admisi, Pemeriksaan/Poli, Resep Farmasi, Laboratorium, Radiologi, Bed Ranap, dan Billing.
     */
    public function timeline(int $patientId, array $filters = []): ?array
    {
        $patientExists = LegacyPatient::query()
            ->active()
            ->where('id', $patientId)
            ->exists();

        if (!$patientExists) {
            return null;
        }

        if (!LegacyAccess::hasTable('trx_admisi')) {
            return [];
        }

        // 1. Dapatkan seluruh admisi pasien yang sah
        $admissions = DB::connection('legacy')->table('trx_admisi')
            ->where('id_pasien', $patientId)
            ->where(function ($q) {
                $q->whereNull('deleted_at')
                    ->orWhere('deleted_at', '0000-00-00 00:00:00')
                    ->orWhere('deleted_at', '0000-00-00');
            })
            ->get();

        if ($admissions->isEmpty()) {
            return [];
        }

        $admIds = $admissions->pluck('id')->all();

        // 2. Dapatkan seluruh visit terkait
        $visits = collect();
        if (LegacyAccess::hasTable('trx_visit')) {
            $visits = DB::connection('legacy')->table('trx_visit')
                ->whereIn('id_admisi', $admIds)
                ->where(function ($q) {
                    $q->whereNull('deleted_at')
                        ->orWhere('deleted_at', '0000-00-00 00:00:00')
                        ->orWhere('deleted_at', '0000-00-00');
                })
                ->get();
        }

        $visitMap = $visits->keyBy('id');
        $visitIds = $visits->pluck('id')->all();

        // Resolusi Nama Dokter & Poliklinik
        $doctorIds = $visits->pluck('id_dokter')->filter()->unique()->all();
        $doctors = [];
        if (!empty($doctorIds) && LegacyAccess::hasTable('ref_dokter')) {
            $docRows = LegacyDoctor::whereIn('id', $doctorIds)->with('person')->get();
            foreach ($docRows as $d) {
                $doctors[$d->id] = $d->person?->nama ?? $d->name ?? "Dokter #{$d->id}";
            }
        }

        $poliIds = $visits->pluck('id_poliklinik')->filter()->unique()->all();
        $polyclinics = [];
        if (!empty($poliIds) && LegacyAccess::hasTable('ref_poliklinik')) {
            $poliRows = LegacyPolyclinic::whereIn('id', $poliIds)->get();
            foreach ($poliRows as $p) {
                $polyclinics[$p->id] = $p->nama ?? $p->name ?? "Poli #{$p->id}";
            }
        }

        $events = [];

        // A. Event Admisi
        foreach ($admissions as $adm) {
            $ts = $adm->waktu_admisi ?? $adm->created_at;
            $statusText = match ((string) $adm->status) {
                '2' => 'Selesai',
                '1' => 'Aktif',
                default => 'Tercatat',
            };
            $caraMasuk = !empty($adm->cara_masuk) ? ucfirst(str_replace('_', ' ', $adm->cara_masuk)) : '-';
            $notes = [];
            if ($caraMasuk !== '-') $notes[] = "Cara Masuk: {$caraMasuk}";
            if (!empty($adm->alasan_datang)) $notes[] = "Alasan: {$adm->alasan_datang}";

            $events[] = [
                'id' => "admisi-{$adm->id}",
                'type' => 'admisi',
                'category' => 'Admisi',
                'title' => 'Admisi Pasien',
                'timestamp' => $ts ? (string) $ts : null,
                'unit' => 'Pendaftaran / Admisi',
                'practitioner' => null,
                'status' => $statusText,
                'status_variant' => $adm->status === '2' ? 'success' : 'primary',
                'notes' => !empty($notes) ? implode(' • ', $notes) : null,
                'admission_id' => (int) $adm->id,
                'visit_id' => null,
            ];
        }

        // B. Event Pemeriksaan & Kunjungan Poliklinik
        foreach ($visits as $v) {
            $ts = $v->waktu_mulai_periksa ?? $v->created_at;
            $poli = $polyclinics[$v->id_poliklinik] ?? 'Poliklinik Rawat Jalan';
            $doc = $doctors[$v->id_dokter] ?? null;
            $statusDilayani = match ($v->status_dilayani) {
                'Ya' => 'Selesai Dilayani',
                'Proses' => 'Sedang Dilayani',
                default => 'Menunggu Pelayanan',
            };
            $statusVariant = match ($v->status_dilayani) {
                'Ya' => 'success',
                'Proses' => 'warning',
                default => 'secondary',
            };

            $notes = [];
            if (!empty($v->kasus)) $notes[] = "Kasus: {$v->kasus}";
            if (!empty($v->cek_lunas_visit)) $notes[] = "Billing: {$v->cek_lunas_visit}";

            $events[] = [
                'id' => "visit-{$v->id}",
                'type' => 'pemeriksaan',
                'category' => 'Pemeriksaan',
                'title' => "Pelayanan {$poli}",
                'timestamp' => $ts ? (string) $ts : null,
                'unit' => $poli,
                'practitioner' => $doc,
                'status' => $statusDilayani,
                'status_variant' => $statusVariant,
                'notes' => !empty($notes) ? implode(' • ', $notes) : null,
                'admission_id' => (int) $v->id_admisi,
                'visit_id' => (int) $v->id,
            ];
        }

        // C. Event Pelayanan Resep Farmasi
        if (!empty($visitIds) && LegacyAccess::hasTable('trx_resep')) {
            $reseps = DB::connection('legacy')->table('trx_resep')
                ->whereIn('id_visit', $visitIds)
                ->where(function ($q) {
                    $q->whereNull('deleted_at')
                        ->orWhere('deleted_at', '0000-00-00 00:00:00')
                        ->orWhere('deleted_at', '0000-00-00');
                })
                ->get();

            foreach ($reseps as $r) {
                $ts = $r->created_at ?? $r->create_time_stamp;
                $linkedVisit = $visitMap[$r->id_visit] ?? null;
                $poli = $linkedVisit ? ($polyclinics[$linkedVisit->id_poliklinik] ?? 'Poliklinik') : 'Instalasi Farmasi';
                $doc = $doctors[$r->dokter_pengirim] ?? null;
                $statusText = match ($r->status_resep) {
                    'selesai' => 'Resep Selesai',
                    'proses' => 'Sedang Diproses',
                    'menunggu_bayar' => 'Menunggu Pembayaran',
                    default => 'Order Resep',
                };
                if ($r->penyerahan_obat === 'Sudah') {
                    $statusText .= ' (Obat Diserahkan)';
                }
                $statusVariant = match ($r->status_resep) {
                    'selesai' => 'success',
                    'proses' => 'warning',
                    default => 'info',
                };

                $notes = [];
                if (!empty($r->keterangan)) $notes[] = $r->keterangan;
                if (!empty($r->naratif)) $notes[] = $r->naratif;

                $events[] = [
                    'id' => "resep-{$r->id}",
                    'type' => 'farmasi',
                    'category' => 'Farmasi',
                    'title' => "Resep Farmasi #{$r->id}",
                    'timestamp' => $ts ? (string) $ts : null,
                    'unit' => "Farmasi ({$poli})",
                    'practitioner' => $doc,
                    'status' => $statusText,
                    'status_variant' => $statusVariant,
                    'notes' => !empty($notes) ? implode(' • ', $notes) : 'Order resep farmasi tercatat',
                    'admission_id' => $linkedVisit ? (int) $linkedVisit->id_admisi : null,
                    'visit_id' => (int) $r->id_visit,
                ];
            }
        }

        // D. Event Pemeriksaan Laboratorium
        if (!empty($visitIds) && LegacyAccess::hasTable('trx_lab')) {
            $labs = DB::connection('legacy')->table('trx_lab')
                ->whereIn('id_visit', $visitIds)
                ->where(function ($q) {
                    $q->whereNull('deleted_at')
                        ->orWhere('deleted_at', '0000-00-00 00:00:00')
                        ->orWhere('deleted_at', '0000-00-00');
                })
                ->get();

            foreach ($labs as $l) {
                $ts = $l->waktu_ambil_sample ?? $l->created_at ?? $l->create_time_stamp;
                $linkedVisit = $visitMap[$l->id_visit] ?? null;
                $doc = $doctors[$l->dokter_pengirim] ?? null;
                $statusText = match ($l->status_pemeriksaan) {
                    'selesai' => 'Hasil Selesai',
                    'proses' => 'Pemeriksaan Diproses',
                    default => 'Order Lab',
                };
                $statusVariant = match ($l->status_pemeriksaan) {
                    'selesai' => 'success',
                    'proses' => 'warning',
                    default => 'info',
                };

                $notes = [];
                if (!empty($l->no_register)) $notes[] = "No. Reg: {$l->no_register}";
                if (!empty($l->jenis)) $notes[] = "Jenis: {$l->jenis}";
                if (!empty($l->resume)) $notes[] = "Resume: {$l->resume}";
                if (!empty($l->keterangan)) $notes[] = "Ket: {$l->keterangan}";

                $events[] = [
                    'id' => "lab-{$l->id}",
                    'type' => 'laboratorium',
                    'category' => 'Laboratorium',
                    'title' => !empty($l->jenis) ? "Pemeriksaan Lab: {$l->jenis}" : "Pemeriksaan Laboratorium #{$l->id}",
                    'timestamp' => $ts ? (string) $ts : null,
                    'unit' => 'Laboratorium',
                    'practitioner' => $doc,
                    'status' => $statusText,
                    'status_variant' => $statusVariant,
                    'notes' => !empty($notes) ? implode(' • ', $notes) : 'Pemeriksaan laboratorium tercatat',
                    'admission_id' => $linkedVisit ? (int) $linkedVisit->id_admisi : null,
                    'visit_id' => (int) $l->id_visit,
                ];
            }
        }

        // E. Event Pemeriksaan Radiologi
        if (!empty($visitIds) && LegacyAccess::hasTable('trx_rad')) {
            $rads = DB::connection('legacy')->table('trx_rad')
                ->whereIn('id_visit', $visitIds)
                ->where(function ($q) {
                    $q->whereNull('deleted_at')
                        ->orWhere('deleted_at', '0000-00-00 00:00:00')
                        ->orWhere('deleted_at', '0000-00-00');
                })
                ->get();

            foreach ($rads as $rad) {
                $ts = $rad->waktu_periksa ?? $rad->created_at ?? $rad->create_time_stamp;
                $linkedVisit = $visitMap[$rad->id_visit] ?? null;
                $doc = $doctors[$rad->dokter_pengirim] ?? null;
                $statusText = match ($rad->status_pemeriksaan) {
                    'selesai' => 'Hasil Selesai',
                    'proses' => 'Pemeriksaan Diproses',
                    default => 'Order Radiologi',
                };
                $statusVariant = match ($rad->status_pemeriksaan) {
                    'selesai' => 'success',
                    'proses' => 'warning',
                    default => 'info',
                };

                $notes = [];
                if (!empty($rad->accession_number)) $notes[] = "Accession: {$rad->accession_number}";
                if (!empty($rad->catatan)) $notes[] = "Catatan: {$rad->catatan}";
                if (!empty($rad->keterangan)) $notes[] = "Ket: {$rad->keterangan}";

                $events[] = [
                    'id' => "rad-{$rad->id}",
                    'type' => 'radiologi',
                    'category' => 'Radiologi',
                    'title' => "Pemeriksaan Radiologi #{$rad->id}",
                    'timestamp' => $ts ? (string) $ts : null,
                    'unit' => 'Radiologi',
                    'practitioner' => $doc,
                    'status' => $statusText,
                    'status_variant' => $statusVariant,
                    'notes' => !empty($notes) ? implode(' • ', $notes) : 'Pemeriksaan radiologi tercatat',
                    'admission_id' => $linkedVisit ? (int) $linkedVisit->id_admisi : null,
                    'visit_id' => (int) $rad->id_visit,
                ];
            }
        }

        // F. Event Bed / Rawat Inap
        if (!empty($visitIds) && LegacyAccess::hasTable('trx_bed')) {
            $beds = DB::connection('legacy')->table('trx_bed')
                ->whereIn('id_visit', $visitIds)
                ->where(function ($q) {
                    $q->whereNull('deleted_at')
                        ->orWhere('deleted_at', '0000-00-00 00:00:00')
                        ->orWhere('deleted_at', '0000-00-00');
                })
                ->get();

            foreach ($beds as $b) {
                $ts = $b->waktu_masuk_ranap ?? $b->created_at;
                $linkedVisit = $visitMap[$b->id_visit] ?? null;
                $statusText = match ($b->cek_ranap) {
                    'Ranap' => 'Rawat Inap Aktif',
                    'Keluar' => 'Sudah Keluar Ranap',
                    'Administrasi' => 'Administrasi Ranap',
                    default => 'Proses Ranap',
                };
                $statusVariant = match ($b->cek_ranap) {
                    'Ranap' => 'primary',
                    'Keluar' => 'success',
                    default => 'warning',
                };

                $notes = [];
                if (!empty($b->cara_tiba)) $notes[] = "Cara Tiba: {$b->cara_tiba}";
                if (!empty($b->macam_kasus_trauma)) $notes[] = "Kasus: {$b->macam_kasus_trauma}";
                if (!empty($b->waktu_keluar_ranap)) $notes[] = "Keluar: {$b->waktu_keluar_ranap}";

                $events[] = [
                    'id' => "bed-{$b->id}",
                    'type' => 'rawat_inap',
                    'category' => 'Rawat Inap',
                    'title' => 'Penempatan Bed Rawat Inap',
                    'timestamp' => $ts ? (string) $ts : null,
                    'unit' => "Rawat Inap (Bed #{$b->id_bed})",
                    'practitioner' => null,
                    'status' => $statusText,
                    'status_variant' => $statusVariant,
                    'notes' => !empty($notes) ? implode(' • ', $notes) : 'Penempatan ruang rawat inap',
                    'admission_id' => $linkedVisit ? (int) $linkedVisit->id_admisi : null,
                    'visit_id' => (int) $b->id_visit,
                ];
            }
        }

        // G. Event Billing & Kasir
        if (!empty($admIds) && LegacyAccess::hasTable('trx_billing')) {
            $billings = DB::connection('legacy')->table('trx_billing')
                ->whereIn('id_admisi', $admIds)
                ->where(function ($q) {
                    $q->whereNull('deleted_at')
                        ->orWhere('deleted_at', '0000-00-00 00:00:00')
                        ->orWhere('deleted_at', '0000-00-00');
                })
                ->get();

            foreach ($billings as $bill) {
                $ts = $bill->created_at ?? $bill->tgl_transaksi;
                $statusText = match ($bill->status_lunas) {
                    'sudah bayar' => 'Lunas',
                    'kurang bayar' => 'Kurang Bayar',
                    'menunggu bayar' => 'Menunggu Bayar',
                    default => 'Belum Lunas',
                };
                $statusVariant = match ($bill->status_lunas) {
                    'sudah bayar' => 'success',
                    'kurang bayar' => 'warning',
                    default => 'danger',
                };

                $notes = [];
                if (!empty($bill->no_invoice)) $notes[] = "No. Inv: {$bill->no_invoice}";
                if (!empty($bill->cara_bayar)) $notes[] = "Metode: {$bill->cara_bayar}";
                if ($bill->total_bayar !== null && $bill->total_bayar > 0) {
                    $notes[] = "Total: Rp " . number_format($bill->total_bayar, 0, ',', '.');
                }
                if ($bill->bayar !== null && $bill->bayar > 0) {
                    $notes[] = "Dibayar: Rp " . number_format($bill->bayar, 0, ',', '.');
                }

                $events[] = [
                    'id' => "billing-{$bill->id}",
                    'type' => 'billing',
                    'category' => 'Billing',
                    'title' => !empty($bill->no_invoice) ? "Tagihan Invoice {$bill->no_invoice}" : "Transaksi Billing #{$bill->id}",
                    'timestamp' => $ts ? (string) $ts : null,
                    'unit' => 'Kasir / Billing',
                    'practitioner' => null,
                    'status' => $statusText,
                    'status_variant' => $statusVariant,
                    'notes' => !empty($notes) ? implode(' • ', $notes) : 'Tagihan administrasi dan pelayanan',
                    'admission_id' => (int) $bill->id_admisi,
                    'visit_id' => $bill->id_visit ? (int) $bill->id_visit : null,
                ];
            }
        }

        // Urutkan menurun berdasarkan timestamp yang sah
        usort($events, function ($a, $b) {
            $timeA = $a['timestamp'] ? strtotime($a['timestamp']) : 0;
            $timeB = $b['timestamp'] ? strtotime($b['timestamp']) : 0;
            return $timeB <=> $timeA;
        });

        // Filter tipe jika diminta
        if (!empty($filters['type']) && $filters['type'] !== 'all') {
            $typeFilter = strtolower(trim((string) $filters['type']));
            $events = array_values(array_filter($events, function ($ev) use ($typeFilter) {
                return strtolower($ev['type']) === $typeFilter;
            }));
        }

        return $events;
    }
}

