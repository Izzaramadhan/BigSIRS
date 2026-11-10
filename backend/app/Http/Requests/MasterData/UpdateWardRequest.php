<?php

namespace App\Http\Requests\MasterData;

use App\Models\Legacy\LegacyFloor;
use App\Models\MasterData\Floor;
use App\Support\LegacyAccess;
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
        $wardId = is_object($wardParam) ? $wardParam->id : $wardParam;

        $isLegacy = LegacyAccess::isAvailable('ref_bangsal');
        $floorTable = $isLegacy ? 'legacy.ref_lantai' : 'floors';

        $uniqueRule = $isLegacy
            ? Rule::unique('legacy.ref_bangsal', 'nama')->where('id_lantai', $this->floor_id)->ignore($wardId)->whereNull('deleted_at')
            : Rule::unique('wards', 'name')->where('floor_id', $this->floor_id)->ignore($wardId)->whereNull('deleted_at');

        return [
            'floor_id' => [
                'required',
                'integer',
                Rule::exists($floorTable, 'id')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) use ($isLegacy) {
                    if ($isLegacy) {
                        $floor = LegacyFloor::with('building')->find($value);
                        if ($floor && ! $floor->building) {
                            $fail('Lantai yang dipilih harus memiliki relasi Gedung yang valid.');
                        }
                    } else {
                        $floor = Floor::with('building')->whereNull('deleted_at')->find($value);
                        if ($floor && (! $floor->building || $floor->building->trashed())) {
                            $fail('Lantai yang dipilih harus memiliki relasi Gedung yang valid.');
                        }
                    }
                },
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                $uniqueRule,
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'floor_id.required' => 'Lantai wajib dipilih.',
            'floor_id.exists' => 'Lantai yang dipilih tidak valid atau sudah dihapus.',
            'name.required' => 'Nama bangsal wajib diisi.',
            'name.unique' => 'Nama bangsal sudah digunakan pada lantai yang dipilih.',
        ];
    }
}
