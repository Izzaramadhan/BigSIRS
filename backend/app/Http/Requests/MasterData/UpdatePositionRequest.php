<?php

namespace App\Http\Requests\MasterData;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $positionId = $this->route('position')?->id ?? $this->route('position');

        $uniqueRule = LegacyAccess::isAvailable('ref_jabatan')
            ? Rule::unique('legacy.ref_jabatan', 'jabatan')->whereNull('deleted_at')->ignore($positionId)
            : Rule::unique('positions', 'name')->whereNull('deleted_at')->ignore($positionId);

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                $uniqueRule,
            ],
            'description' => 'nullable|string|max:50',
            'code' => 'nullable|string|max:20',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim(preg_replace('/\s+/', ' ', $this->name)),
            ]);
        }
    }
}
