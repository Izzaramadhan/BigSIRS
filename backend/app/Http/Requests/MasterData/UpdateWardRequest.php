<?php

namespace App\Http\Requests\MasterData;

use App\Models\MasterData\Floor;
use App\Models\MasterData\Ward;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->name !== null ? preg_replace('/\s+/', ' ', trim((string) $this->name)) : null,
            'description' => $this->description !== null ? trim((string) $this->description) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $wardParam = $this->route('ward');
        $wardId = $wardParam instanceof Ward ? $wardParam->id : $wardParam;
        $floorId = $this->floor_id ?? ($wardParam instanceof Ward ? $wardParam->floor_id : null);

        return [
            'floor_id' => [
                'required',
                'integer',
                Rule::exists('floors', 'id')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) {
                    $floor = Floor::with('building')->whereNull('deleted_at')->find($value);
                    if ($floor && (! $floor->building || $floor->building->trashed())) {
                        $fail('Lantai yang dipilih harus memiliki relasi Gedung yang valid.');
                    }
                },
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('wards', 'name')
                    ->where('floor_id', $floorId)
                    ->whereNull('deleted_at')
                    ->ignore($wardId),
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
            'floor_id.required' => 'Nama lantai wajib dipilih.',
            'floor_id.integer' => 'Pilihan lantai tidak valid.',
            'floor_id.exists' => 'Lantai yang dipilih tidak valid atau sudah dihapus.',
            'name.required' => 'Nama bangsal wajib diisi.',
            'name.string' => 'Nama bangsal harus berupa teks.',
            'name.max' => 'Nama bangsal maksimal 255 karakter.',
            'name.unique' => 'Nama bangsal sudah digunakan pada lantai yang dipilih.',
        ];
    }
}
