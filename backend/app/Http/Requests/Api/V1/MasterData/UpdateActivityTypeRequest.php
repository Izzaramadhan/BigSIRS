<?php

namespace App\Http\Requests\Api\V1\MasterData;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActivityTypeRequest extends FormRequest
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
        $id = $this->route('activity_type') ? $this->route('activity_type')->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('activity_types', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($id),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('activity_types', 'id')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) use ($id) {
                    if ($value == $id) {
                        $fail('Jenis kegiatan tidak boleh menjadi induk untuk dirinya sendiri.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama jenis kegiatan wajib diisi.',
            'name.unique' => 'Nama jenis kegiatan sudah digunakan.',
            'name.max' => 'Nama jenis kegiatan tidak boleh lebih dari 255 karakter.',
            'parent_id.exists' => 'Induk jenis kegiatan tidak ditemukan atau sudah dihapus.',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim($this->name),
            ]);
        }
    }
}
