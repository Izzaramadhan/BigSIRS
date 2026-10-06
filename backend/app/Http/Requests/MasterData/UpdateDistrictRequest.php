<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class UpdateDistrictRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => $this->name ? Str::squish(trim($this->name)) : null,
            ]);
        }
        if ($this->has('code')) {
            $this->merge([
                'code' => $this->code ? trim($this->code) : null,
            ]);
        }
    }

    public function rules(): array
    {
        $districtId = $this->route('district')->id ?? $this->route('district');

        return [
            'regency_id' => [
                'required',
                'integer',
                Rule::exists('regencies', 'id')->whereNull('deleted_at'),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('districts', 'code')
                    ->ignore($districtId)
                    ->whereNull('deleted_at'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('districts', 'name')
                    ->where('regency_id', $this->regency_id)
                    ->ignore($districtId)
                    ->whereNull('deleted_at'),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
