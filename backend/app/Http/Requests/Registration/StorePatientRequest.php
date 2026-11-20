<?php

namespace App\Http\Requests\Registration;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nik' => ['required', 'string', 'regex:/^[0-9]{16}$/'],
            'ihs_number' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:50'],
            'birth_place' => ['required', 'string', 'max:50'],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'gender' => ['required', 'string'],
            'nationality' => ['nullable', 'string'],
            'address' => ['required', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'province_id' => ['nullable', 'string', 'max:10'],
            'regency_id' => ['nullable', 'string', 'max:10'],
            'district_id' => ['nullable', 'string', 'max:10'],
            'village_id' => ['nullable'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:50'],
            'education' => ['nullable', 'string'],
            'education_id' => ['nullable', 'integer'],
            'occupation' => ['nullable', 'string'],
            'occupation_id' => ['nullable', 'integer'],
            'blood_type' => ['nullable', 'string'],
            'religion' => ['nullable', 'string'],
            'marital_status' => ['nullable', 'string'],
            'guarantor' => ['nullable', 'string'],
            'guarantor_id' => ['nullable', 'integer'],
            'insurance_number' => ['nullable', 'string', 'max:50'],
            'hak_kelas' => ['nullable', 'string', 'max:50'],
            'has_allergy' => ['nullable', 'string'],
            'allergy_notes' => ['nullable', 'string'],
            'is_fast_track' => ['nullable', 'string'],
            'patient_category' => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'array'],
            'emergency_contact.name' => ['nullable', 'string', 'max:255'],
            'emergency_contact.relationship' => ['nullable', 'string', 'max:255'],
            'emergency_contact.phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact.address' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nik.required' => 'NIK/No. KTP/KIA wajib diisi.',
            'nik.regex' => 'NIK harus berupa 16 digit angka.',
            'name.required' => 'Nama lengkap pasien wajib diisi.',
            'birth_place.required' => 'Tempat lahir wajib diisi.',
            'birth_date.required' => 'Tanggal lahir wajib diisi.',
            'birth_date.before_or_equal' => 'Tanggal lahir tidak boleh melebihi hari ini.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'address.required' => 'Alamat domisili wajib diisi.',
            'phone.required' => 'Nomor HP wajib diisi.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $provinceId = $this->input('province_id');
            $regencyId = $this->input('regency_id');
            $districtId = $this->input('district_id');
            $villageId = $this->input('village_id');
            $educationId = $this->input('education_id');
            $occupationId = $this->input('occupation_id');
            $guarantorId = $this->input('guarantor_id');

            // 1. Validasi Hierarki Wilayah
            if (!empty($provinceId)) {
                if (LegacyAccess::isAvailable('ref_provinsi')) {
                    $validProvince = DB::connection('legacy')->table('ref_provinsi')
                        ->where('id', (string) $provinceId)
                        ->whereIn('status', [1, '1'])
                        ->exists();

                    if (!$validProvince) {
                        $v->errors()->add('province_id', 'Provinsi yang dipilih tidak valid.');
                    }
                } elseif (\Illuminate\Support\Facades\Schema::hasTable('provinces')) {
                    $validProvince = DB::table('provinces')
                        ->where('id', $provinceId)
                        ->where('is_active', true)
                        ->exists();

                    if (!$validProvince) {
                        $v->errors()->add('province_id', 'Provinsi yang dipilih tidak valid.');
                    }
                }
            }

            if (!empty($regencyId)) {
                if (empty($provinceId)) {
                    $v->errors()->add('regency_id', 'Kabupaten/Kota memerlukan pilihan Provinsi yang valid.');
                } elseif (LegacyAccess::isAvailable('ref_kabupaten')) {
                    $validRegency = DB::connection('legacy')->table('ref_kabupaten')
                        ->where('id', (string) $regencyId)
                        ->where('id_provinsi', (string) $provinceId)
                        ->whereIn('status', [1, '1'])
                        ->exists();

                    if (!$validRegency) {
                        $v->errors()->add('regency_id', 'Kabupaten/Kota yang dipilih tidak berada di bawah Provinsi tersebut.');
                    }
                } elseif (\Illuminate\Support\Facades\Schema::hasTable('regencies')) {
                    $validRegency = DB::table('regencies')
                        ->where('id', $regencyId)
                        ->where('province_id', $provinceId)
                        ->where('is_active', true)
                        ->exists();

                    if (!$validRegency) {
                        $v->errors()->add('regency_id', 'Kabupaten/Kota yang dipilih tidak berada di bawah Provinsi tersebut.');
                    }
                }
            }

            if (!empty($districtId)) {
                if (empty($regencyId)) {
                    $v->errors()->add('district_id', 'Kecamatan memerlukan pilihan Kabupaten/Kota yang valid.');
                } elseif (LegacyAccess::isAvailable('ref_kecamatan')) {
                    $validDistrict = DB::connection('legacy')->table('ref_kecamatan')
                        ->where('id', (string) $districtId)
                        ->where('id_kabupaten', (string) $regencyId)
                        ->whereIn('status', [1, '1'])
                        ->exists();

                    if (!$validDistrict) {
                        $v->errors()->add('district_id', 'Kecamatan yang dipilih tidak berada di bawah Kabupaten/Kota tersebut.');
                    }
                } elseif (\Illuminate\Support\Facades\Schema::hasTable('districts')) {
                    $validDistrict = DB::table('districts')
                        ->where('id', $districtId)
                        ->where('regency_id', $regencyId)
                        ->where('is_active', true)
                        ->exists();

                    if (!$validDistrict) {
                        $v->errors()->add('district_id', 'Kecamatan yang dipilih tidak berada di bawah Kabupaten/Kota tersebut.');
                    }
                }
            }

            if (!empty($villageId)) {
                if (empty($districtId)) {
                    $v->errors()->add('village_id', 'Kelurahan/Desa memerlukan pilihan Kecamatan yang valid.');
                } elseif (LegacyAccess::isAvailable('ref_kelurahan')) {
                    $validVillage = DB::connection('legacy')->table('ref_kelurahan')
                        ->where(function ($q) use ($villageId) {
                            $q->where('id', (string) $villageId)
                              ->orWhere('kode_kelurahan', (string) $villageId);
                        })
                        ->where('id_kecamatan', (string) $districtId)
                        ->whereIn('status', [1, '1'])
                        ->exists();

                    if (!$validVillage) {
                        $v->errors()->add('village_id', 'Kelurahan/Desa yang dipilih tidak berada di bawah Kecamatan tersebut.');
                    }
                } elseif (\Illuminate\Support\Facades\Schema::hasTable('villages')) {
                    $validVillage = DB::table('villages')
                        ->where(function ($q) use ($villageId) {
                            $q->where('id', $villageId)
                              ->orWhere('code', (string) $villageId);
                        })
                        ->where('district_id', $districtId)
                        ->where('is_active', true)
                        ->exists();

                    if (!$validVillage) {
                        $v->errors()->add('village_id', 'Kelurahan/Desa yang dipilih tidak berada di bawah Kecamatan tersebut.');
                    }
                }
            }

            // 2. Validasi Master Pendidikan
            if (!empty($educationId) && LegacyAccess::isAvailable('ref_pendidikan')) {
                $validEdu = DB::connection('legacy')->table('ref_pendidikan')
                    ->where('id', $educationId)
                    ->where('status', '1')
                    ->exists();

                if (!$validEdu) {
                    $v->errors()->add('education_id', 'Pilihan jenjang pendidikan tidak valid atau sudah nonaktif.');
                }
            }

            // 3. Validasi Master Pekerjaan
            if (!empty($occupationId) && LegacyAccess::isAvailable('ref_pekerjaan')) {
                $validOcc = DB::connection('legacy')->table('ref_pekerjaan')
                    ->where('id', $occupationId)
                    ->where('status', '1')
                    ->exists();

                if (!$validOcc) {
                    $v->errors()->add('occupation_id', 'Pilihan pekerjaan tidak valid atau sudah nonaktif.');
                }
            }

            // 4. Validasi Master Jenis Jaminan
            if (!empty($guarantorId) && LegacyAccess::isAvailable('ref_jenis_asuransi')) {
                $validGuar = DB::connection('legacy')->table('ref_jenis_asuransi')
                    ->where('id', $guarantorId)
                    ->where('status', '1')
                    ->exists();

                if (!$validGuar) {
                    $v->errors()->add('guarantor_id', 'Pilihan jenis jaminan tidak valid atau sudah nonaktif.');
                }
            }
        });
    }
}
