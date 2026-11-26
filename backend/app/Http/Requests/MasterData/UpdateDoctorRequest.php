<?php

namespace App\Http\Requests\MasterData;

use App\Models\Legacy\LegacyDoctor;
use App\Models\Legacy\LegacyEmployee;
use App\Support\LegacyAccess;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isLegacy = LegacyAccess::isAvailable('ref_dokter');
        $employeeTable = $isLegacy ? 'legacy.ref_pegawai' : 'employees';
        $specializationTable = $isLegacy ? 'legacy.ref_spesialisasi' : 'specializations';

        $doctor = $this->route('doctor');
        $doctorId = is_object($doctor) ? $doctor->id : $doctor;
        $employeeId = is_object($doctor) ? ($doctor->employee_id ?? null) : null;

        $employeeRules = ['nullable', 'integer', "exists:{$employeeTable},id"];
        $nationalIdRule = ['nullable', 'string', 'max:20'];
        $sipRule = ['nullable', 'string', 'max:255'];

        if ($isLegacy) {
            $employeeRules[] = function (string $attribute, mixed $value, Closure $fail) use ($doctorId): void {
                if ($value === null || $value === '') {
                    return;
                }
                $employee = LegacyEmployee::find($value);
                if (
                    $employee &&
                    $employee->id_mpi &&
                    LegacyDoctor::where('id_mpi', $employee->id_mpi)
                        ->where('id', '!=', $doctorId)
                        ->exists()
                ) {
                    $fail('Pegawai ini sudah terdaftar sebagai dokter.');
                }
            };
            $sipRule[] = Rule::unique('legacy.ref_dokter', 'sip')->ignore($doctorId);
        } else {
            $employeeRules[] = Rule::unique('doctors', 'employee_id')
                ->whereNull('deleted_at')
                ->ignore($doctorId);
            if ($employeeId) {
                $nationalIdRule[] = Rule::unique('employees', 'national_id')->ignore($employeeId);
            }
            $sipRule[] = Rule::unique('doctors', 'sip_number')->ignore($doctorId);
        }

        return [
            'employee_id' => $employeeRules,
            'person' => ['required', 'array'],
            'person.national_id' => $nationalIdRule,
            'person.ihs_number' => ['nullable', 'string', 'max:255'],
            'person.name' => ['required', 'string', 'max:255'],
            'person.birth_place' => ['nullable', 'string', 'max:255'],
            'person.birth_date' => ['nullable', 'date'],
            'person.gender' => ['required', 'in:L,P'],
            'person.nationality' => ['nullable', 'in:WNI,WNA'],
            'person.blood_type' => ['nullable', 'in:A,B,AB,O,Unknown'],
            'person.allergies' => ['nullable', 'string'],
            'person.religion' => ['nullable', 'string', 'max:50'],
            'person.marital_status' => ['nullable', 'string', 'max:50'],
            'person.address' => ['nullable', 'string'],
            'person.postal_code' => ['nullable', 'string', 'max:10'],
            'person.province_id' => ['nullable', 'string', 'max:10'],
            'person.regency_id' => ['nullable', 'string', 'max:10'],
            'person.district_id' => ['nullable', 'string', 'max:10'],
            'person.village_id' => ['nullable', 'string', 'max:15'],
            'person.phone' => ['nullable', 'string', 'max:20'],
            'person.education_id' => ['nullable', 'integer'],
            'person.occupation_id' => ['nullable', 'integer'],

            'professional' => ['required', 'array'],
            'professional.specialization_id' => ['required', 'integer', "exists:{$specializationTable},id"],
            'professional.str_number' => ['nullable', 'string', 'max:50'],
            'professional.sip_number' => $sipRule,
            'professional.sip_valid_until' => ['nullable', 'date'],
            'professional.bpjs_dpjp_code' => ['nullable', 'string', 'max:50'],
            'professional.is_active' => ['boolean'],

            'signature' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'remove_signature' => ['nullable', 'boolean'],
        ];
    }
}
