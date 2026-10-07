<?php

namespace App\Http\Requests\MasterData;

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
        $positionId = $this->route('position')->id ?? $this->route('position');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'name')->ignore($positionId),
            ],
            'description' => 'nullable|string|max:1000',

        ];
    }
}
