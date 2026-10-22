<?php

namespace App\Http\Requests\MasterData;

use App\Models\MasterData\RoomClass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->name !== null ? preg_replace('/\s+/', ' ', trim((string) $this->name)) : null,
            'code' => $this->code !== null ? preg_replace('/\s+/', ' ', trim((string) $this->code)) : null,
            'description' => $this->description !== null ? trim((string) $this->description) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $normalized = strtolower(preg_replace('/\s+/', ' ', trim((string) $value)));
                    $exists = RoomClass::whereNull('deleted_at')
                        ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized])
                        ->exists();

                    if ($exists) {
                        $fail('Nama kelas sudah digunakan.');
                    }
                },
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('room_classes', 'code')->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama kelas wajib diisi.',
            'name.string' => 'Nama kelas harus berupa teks.',
            'name.max' => 'Nama kelas maksimal 255 karakter.',
            'code.unique' => 'Kode kelas sudah digunakan.',
        ];
    }
}
