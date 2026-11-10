<?php

namespace App\Http\Requests\MasterData;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'doctor_id' => ['required', 'integer'],
            'polyclinic_id' => ['required', 'integer'],
            'day_of_week' => ['required', function ($attribute, $value, $fail) {
                if (is_numeric($value)) {
                    if ((int) $value < 1 || (int) $value > 7) {
                        $fail('Hari praktik harus berupa angka antara 1 dan 7.');
                    }
                } elseif (is_string($value)) {
                    $days = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
                    if (! in_array(strtolower(trim($value)), $days)) {
                        $fail('Nama hari praktik tidak valid.');
                    }
                } else {
                    $fail('Format hari praktik tidak valid.');
                }
            }],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_holiday' => ['nullable', 'boolean'],
            'online_quota' => ['nullable', 'integer', 'min:0'],
        ];

        if (! LegacyAccess::isAvailable('trx_jadwal_dokter')) {
            $rules['doctor_id'][] = 'exists:doctors,id';
            $rules['polyclinic_id'][] = 'exists:polyclinics,id';
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('start_time') && is_string($this->start_time)) {
            $this->merge(['start_time' => substr(trim($this->start_time), 0, 5)]);
        }
        if ($this->has('end_time') && is_string($this->end_time)) {
            $this->merge(['end_time' => substr(trim($this->end_time), 0, 5)]);
        }
    }
}
