<?php

namespace App\Http\Requests\MasterData;

use App\Support\LegacyAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOccupationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $occupationId = $this->route('occupation')?->id ?? $this->route('occupation');

        $uniqueRule = LegacyAccess::isAvailable('ref_pekerjaan')
            ? Rule::unique('legacy.ref_pekerjaan', 'pekerjaan')->whereNull('deleted_at')->ignore($occupationId)
            : Rule::unique('occupations', 'name')->whereNull('deleted_at')->ignore($occupationId);

        return [
            'name' => ['required', 'string', 'max:50', $uniqueRule],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Nama Pekerjaan sudah digunakan.',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim(preg_replace('/\s+/', ' ', $this->name)),
            ]);
        }
    }
}
