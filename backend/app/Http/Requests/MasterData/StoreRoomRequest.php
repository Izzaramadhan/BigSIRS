<?php

namespace App\Http\Requests\MasterData;

use App\Models\MasterData\Ward;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->code !== null ? preg_replace('/\s+/', ' ', trim((string) $this->code)) : null,
            'name' => $this->name !== null ? preg_replace('/\s+/', ' ', trim((string) $this->name)) : null,
            'description' => $this->description !== null ? trim((string) $this->description) : null,
            'class_id' => $this->class_id !== null && $this->class_id !== '' ? (int) $this->class_id : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ward_id' => [
                'required',
                'integer',
                Rule::exists('wards', 'id')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) {
                    $ward = Ward::with(['floor.building'])->whereNull('deleted_at')->find($value);
                    if ($ward) {
                        if (! $ward->floor || $ward->floor->trashed() || ! $ward->floor->building || $ward->floor->building->trashed()) {
                            $fail('Bangsal yang dipilih harus memiliki relasi Lantai dan Gedung yang valid.');
                        }
                    }
                },
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rooms', 'code')->whereNull('deleted_at'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rooms', 'name')
                    ->where('ward_id', $this->ward_id)
                    ->whereNull('deleted_at'),
            ],
            'class_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ward_id.required' => 'Bangsal wajib dipilih.',
            'ward_id.integer' => 'Pilihan bangsal tidak valid.',
            'ward_id.exists' => 'Bangsal yang dipilih tidak valid atau sudah dihapus.',
            'code.required' => 'Kode kamar wajib diisi.',
            'code.string' => 'Kode kamar harus berupa teks.',
            'code.max' => 'Kode kamar maksimal 50 karakter.',
            'code.unique' => 'Kode kamar sudah digunakan.',
            'name.required' => 'Nama kamar wajib diisi.',
            'name.string' => 'Nama kamar harus berupa teks.',
            'name.max' => 'Nama kamar maksimal 255 karakter.',
            'name.unique' => 'Nama kamar sudah digunakan pada bangsal yang dipilih.',
        ];
    }
}
