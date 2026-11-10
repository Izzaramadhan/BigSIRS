<?php

namespace App\Http\Requests\MasterData;

use App\Models\Legacy\LegacyGenericMedicine;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGenericMedicineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $generic = $this->route('generic_medicine');
        $id = $generic instanceof \Illuminate\Database\Eloquent\Model ? $generic->id : $generic;
        $legacy = LegacyGenericMedicine::find($id);

        return $legacy ? ($this->user()?->can('update', $legacy) ?? true) : true;
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
        $generic = $this->route('generic_medicine');
        $id = $generic instanceof \Illuminate\Database\Eloquent\Model ? $generic->id : $generic;

        return [
            'name' => [
                'required',
                'string',
                'min:1',
                'max:255',
                function ($attribute, $value, $fail) use ($id) {
                    $normalized = strtolower(trim($value));
                    if (LegacyAccess::isAvailable('ref_generik') || app()->environment('testing')) {
                        $exists = LegacyGenericMedicine::whereRaw('LOWER(nama_objek) = ?', [$normalized])
                            ->where('id', '!=', $id)
                            ->exists();
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
