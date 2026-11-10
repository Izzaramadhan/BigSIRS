<?php

namespace App\Http\Requests\MasterData;

use App\Models\Legacy\LegacyBed;
use App\Models\Legacy\LegacyRoom;
use App\Models\MasterData\Bed;
use App\Models\MasterData\Room;
use App\Support\LegacyAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'room_id' => ($this->room_id !== null && $this->room_id !== '') ? (int) $this->room_id : null,
            'name' => $this->name !== null ? preg_replace('/\s+/', ' ', trim((string) $this->name)) : null,
            'description' => $this->description !== null ? trim((string) $this->description) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roomId = $this->room_id;
        $isLegacy = LegacyAccess::isAvailable('ref_bed');
        $roomTable = $isLegacy ? 'legacy.ref_kamar' : 'rooms';

        $uniqueRule = $isLegacy
            ? Rule::unique('legacy.ref_bed', 'nama')->where('id_kamar', $roomId)->whereNull('deleted_at')
            : Rule::unique('beds', 'name')->where('room_id', $roomId)->whereNull('deleted_at');

        return [
            'room_id' => [
                'required',
                'integer',
                Rule::exists($roomTable, 'id')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) use ($isLegacy) {
                    if ($isLegacy) {
                        $room = LegacyRoom::with(['ward.floor.building', 'roomClass'])->find($value);
                        if ($room) {
                            if (! $room->ward) {
                                $fail('Kamar yang dipilih harus memiliki relasi Bangsal yang valid.');
                                return;
                            }
                            if (! $room->ward->floor || ! $room->ward->floor->building) {
                                $fail('Kamar yang dipilih harus memiliki relasi Lantai dan Gedung yang valid.');
                                return;
                            }
                            if (! $room->roomClass) {
                                $fail('Kamar yang dipilih harus memiliki relasi Kelas yang valid.');
                                return;
                            }
                        }
                    } else {
                        $room = Room::with(['ward.floor.building', 'roomClass'])->whereNull('deleted_at')->find($value);
                        if ($room) {
                            if (! $room->ward || $room->ward->trashed()) {
                                $fail('Kamar yang dipilih harus memiliki relasi Bangsal yang valid.');
                                return;
                            }
                            if (! $room->ward->floor || $room->ward->floor->trashed() || ! $room->ward->floor->building || $room->ward->floor->building->trashed()) {
                                $fail('Kamar yang dipilih harus memiliki relasi Lantai dan Gedung yang valid.');
                                return;
                            }
                            if (! $room->roomClass || $room->roomClass->trashed()) {
                                $fail('Kamar yang dipilih harus memiliki relasi Kelas yang valid.');
                                return;
                            }
                        }
                    }
                },
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                $uniqueRule,
                function ($attribute, $value, $fail) use ($roomId, $isLegacy) {
                    if ($roomId && $value) {
                        $normalized = mb_strtolower(trim((string) $value));
                        $duplicate = $isLegacy
                            ? LegacyBed::where('id_kamar', $roomId)->whereRaw('LOWER(nama) = ?', [$normalized])->exists()
                            : Bed::where('room_id', $roomId)->whereNull('deleted_at')->whereRaw('LOWER(name) = ?', [$normalized])->exists();

                        if ($duplicate) {
                            $fail('Nama bed sudah digunakan pada kamar yang dipilih.');
                        }
                    }
                },
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Kamar wajib dipilih.',
            'room_id.exists' => 'Kamar yang dipilih tidak valid atau sudah dihapus.',
            'name.required' => 'Nama bed wajib diisi.',
            'name.unique' => 'Nama bed sudah digunakan pada kamar yang dipilih.',
        ];
    }
}
