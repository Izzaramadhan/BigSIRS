<?php

namespace App\Http\Requests\MasterData;

use App\Models\MasterData\Bed;
use App\Models\MasterData\Room;
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

        return [
            'room_id' => [
                'required',
                'integer',
                Rule::exists('rooms', 'id')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) {
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
                },
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('beds', 'name')
                    ->where('room_id', $roomId)
                    ->whereNull('deleted_at'),
                function ($attribute, $value, $fail) use ($roomId) {
                    if ($roomId && $value) {
                        $duplicate = Bed::where('room_id', $roomId)
                            ->whereNull('deleted_at')
                            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $value))])
                            ->exists();

                        if ($duplicate) {
                            $fail('Nama bed sudah digunakan pada kamar yang dipilih.');
                        }
                    }
                },
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
            'room_id.required' => 'Nama kamar wajib dipilih.',
            'room_id.integer' => 'Pilihan kamar tidak valid.',
            'room_id.exists' => 'Kamar yang dipilih tidak valid atau sudah dihapus.',
            'name.required' => 'Nama bed wajib diisi.',
            'name.string' => 'Nama bed harus berupa teks.',
            'name.max' => 'Nama bed maksimal 100 karakter.',
            'name.unique' => 'Nama bed sudah digunakan pada kamar yang dipilih.',
            'description.string' => 'Deskripsi bed harus berupa teks.',
            'description.max' => 'Deskripsi bed maksimal 1000 karakter.',
        ];
    }
}
