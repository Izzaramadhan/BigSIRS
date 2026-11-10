<?php

namespace App\Http\Requests\MasterData;

use App\Models\Legacy\LegacyWard;
use App\Models\MasterData\Ward;
use App\Support\LegacyAccess;
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
        $roomId = is_object($roomParam) ? $roomParam->id : $roomParam;

        $isLegacy = LegacyAccess::isAvailable('ref_kamar');
        $wardTable = $isLegacy ? 'legacy.ref_bangsal' : 'wards';
        $classTable = $isLegacy ? 'legacy.ref_kelas' : 'room_classes';

        $codeRule = $isLegacy
            ? Rule::unique('legacy.ref_kamar', 'kode')->ignore($roomId)->whereNull('deleted_at')
            : Rule::unique('rooms', 'code')->ignore($roomId)->whereNull('deleted_at');

        $nameRule = $isLegacy
            ? Rule::unique('legacy.ref_kamar', 'nama')->where('id_bangsal', $this->ward_id)->ignore($roomId)->whereNull('deleted_at')
            : Rule::unique('rooms', 'name')->where('ward_id', $this->ward_id)->ignore($roomId)->whereNull('deleted_at');

        return [
            'ward_id' => [
                'required',
                'integer',
                Rule::exists($wardTable, 'id')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) use ($isLegacy) {
                    if ($isLegacy) {
                        $ward = LegacyWard::with(['floor.building'])->find($value);
                        if ($ward && (! $ward->floor || ! $ward->floor->building)) {
                            $fail('Bangsal yang dipilih harus memiliki relasi Lantai dan Gedung yang valid.');
                        }
                    } else {
                        $ward = Ward::with(['floor.building'])->whereNull('deleted_at')->find($value);
                        if ($ward && (! $ward->floor || $ward->floor->trashed() || ! $ward->floor->building || $ward->floor->building->trashed())) {
                            $fail('Bangsal yang dipilih harus memiliki relasi Lantai dan Gedung yang valid.');
                        }
                    }
                },
            ],
            'room_class_id' => [
                'required',
                'integer',
                Rule::exists($classTable, 'id')->whereNull('deleted_at'),
            ],
            'class_id' => [
                'nullable',
                'integer',
                Rule::exists($classTable, 'id')->whereNull('deleted_at'),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                $codeRule,
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                $nameRule,
            ],
            'description' => ['nullable', 'string'],
        ];
    }

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
