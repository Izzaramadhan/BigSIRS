<?php

namespace App\Http\Requests\MasterData;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEducationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uniqueRule = LegacyAccess::isAvailable('ref_pendidikan')
            ? Rule::unique('legacy.ref_pendidikan', 'pendidikan')->whereNull('deleted_at')
            : Rule::unique('educations', 'name')->whereNull('deleted_at');

        return [
            'name' => ['required', 'string', 'max:50', $uniqueRule],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Nama Pendidikan sudah digunakan.',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('name')) {
            $name = trim(preg_replace('/\s+/', ' ', $this->name));
            $this->merge(['name' => $name]);
        }
    }
}
