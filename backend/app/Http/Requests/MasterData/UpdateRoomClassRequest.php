<?php

namespace App\Http\Requests\MasterData;

use App\Models\MasterData\RoomClass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $price = $this->price;
        if (is_string($price) && trim($price) !== '') {
            $cleaned = trim($price);
            if (preg_match('/\d/', $cleaned)) {
                $cleaned = preg_replace('/[^\d.,]/', '', $cleaned);
                if (str_contains($cleaned, '.') && str_contains($cleaned, ',')) {
                    $cleaned = str_replace('.', '', $cleaned);
                    $cleaned = str_replace(',', '.', $cleaned);
                } elseif (substr_count($cleaned, '.') > 1) {
                    $cleaned = str_replace('.', '', $cleaned);
                } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $cleaned)) {
                    $cleaned = str_replace('.', '', $cleaned);
                }
                $price = $cleaned;
            }
        }

        $this->merge([
            'name' => $this->name !== null ? preg_replace('/\s+/', ' ', trim((string) $this->name)) : null,
            'code' => $this->code !== null ? preg_replace('/\s+/', ' ', trim((string) $this->code)) : null,
            'price' => ($price !== null && $price !== '') ? $price : null,
            'description' => $this->description !== null ? trim((string) $this->description) : null,
            'aplicare_code' => $this->aplicare_code !== null ? trim((string) $this->aplicare_code) : null,
            'sirs_code' => $this->sirs_code !== null ? trim((string) $this->sirs_code) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roomClassParam = $this->route('roomClass') ?? $this->route('room_class');
        $roomClassId = $roomClassParam instanceof RoomClass ? $roomClassParam->id : $roomClassParam;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($roomClassId) {
                    if ($value) {
                        $normalized = mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $value)));
                        $exists = RoomClass::whereNull('deleted_at')
                            ->where('id', '!=', $roomClassId)
                            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized])
                            ->exists();

                        if ($exists) {
                            $fail('Nama kelas sudah digunakan.');
                        }
                    }
                },
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($roomClassId) {
                    if ($value) {
                        $normalized = mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $value)));
                        $exists = RoomClass::whereNull('deleted_at')
                            ->where('id', '!=', $roomClassId)
                            ->whereRaw('LOWER(TRIM(code)) = ?', [$normalized])
                            ->exists();

                        if ($exists) {
                            $fail('Kode kelas sudah digunakan.');
                        }
                    }
                },
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'aplicare_code' => ['nullable', 'string', 'max:50'],
            'sirs_code' => ['nullable', 'string', 'max:50'],
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
            'code.required' => 'Kode kelas wajib diisi.',
            'code.string' => 'Kode kelas harus berupa teks.',
            'code.max' => 'Kode kelas maksimal 50 karakter.',
            'price.required' => 'Harga kelas wajib diisi.',
            'price.numeric' => 'Harga kelas harus berupa angka.',
            'price.min' => 'Harga kelas tidak boleh bernilai negatif.',
            'description.string' => 'Deskripsi kelas harus berupa teks.',
            'description.max' => 'Deskripsi kelas maksimal 1000 karakter.',
            'aplicare_code.string' => 'Kode Aplicare harus berupa teks.',
            'aplicare_code.max' => 'Kode Aplicare maksimal 50 karakter.',
            'sirs_code.string' => 'Kode SIRS harus berupa teks.',
            'sirs_code.max' => 'Kode SIRS maksimal 50 karakter.',
        ];
    }
}
