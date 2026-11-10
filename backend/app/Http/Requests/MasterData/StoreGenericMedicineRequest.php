<?php

namespace App\Http\Requests\MasterData;

use App\Models\Legacy\LegacyGenericMedicine;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;

class StoreGenericMedicineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', LegacyGenericMedicine::class) ?? true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $name = trim((string) $this->name);
            $name = preg_replace('/\s+/', ' ', $name);
            $this->merge(['name' => $name]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:1',
                'max:255',
                function ($attribute, $value, $fail) {
                    $normalized = strtolower(trim($value));
                    if (LegacyAccess::isAvailable('ref_generik') || app()->environment('testing')) {
                        $exists = LegacyGenericMedicine::whereRaw('LOWER(nama_objek) = ?', [$normalized])->exists();
                        if ($exists) {
                            $fail('Nama generik sudah terdaftar.');
                        }
                    }
                },
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama generik wajib diisi.',
            'name.string' => 'Nama generik harus berupa teks.',
            'name.max' => 'Nama generik maksimal 255 karakter.',
        ];
    }
}
