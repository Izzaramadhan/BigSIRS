<?php

namespace App\Http\Requests\MasterData;

use App\Models\MasterData\Room;
use App\Models\MasterData\Ward;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $roomClassId = $this->room_class_id ?? $this->class_id;
        $this->merge([
            'code' => $this->code !== null ? preg_replace('/\s+/', ' ', trim((string) $this->code)) : null,
            'name' => $this->name !== null ? preg_replace('/\s+/', ' ', trim((string) $this->name)) : null,
            'description' => $this->description !== null ? trim((string) $this->description) : null,
            'room_class_id' => ($roomClassId !== null && $roomClassId !== '') ? (int) $roomClassId : null,
            'class_id' => ($roomClassId !== null && $roomClassId !== '') ? (int) $roomClassId : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roomParam = $this->route('room');
        $roomId = $roomParam instanceof Room ? $roomParam->id : $roomParam;
        $wardId = $this->ward_id ?? ($roomParam instanceof Room ? $roomParam->ward_id : null);

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
            'room_class_id' => [
                'required',
                'integer',
                Rule::exists('room_classes', 'id')->whereNull('deleted_at'),
            ],
            'class_id' => [
                'nullable',
                'integer',
                Rule::exists('room_classes', 'id')->whereNull('deleted_at'),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rooms', 'code')
                    ->whereNull('deleted_at')
                    ->ignore($roomId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rooms', 'name')
                    ->where('ward_id', $wardId)
                    ->whereNull('deleted_at')
                    ->ignore($roomId),
            ],
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
            'room_class_id.required' => 'Kelas wajib dipilih.',
            'room_class_id.integer' => 'Kelas yang dipilih tidak valid.',
            'room_class_id.exists' => 'Kelas yang dipilih tidak valid.',
            'class_id.required' => 'Kelas wajib dipilih.',
            'class_id.integer' => 'Kelas yang dipilih tidak valid.',
            'class_id.exists' => 'Kelas yang dipilih tidak valid.',
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
