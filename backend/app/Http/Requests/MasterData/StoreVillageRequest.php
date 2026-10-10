<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVillageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'district_id' => [
                'required',
                'integer',
                Rule::exists('districts', 'id')->where(function ($query) {
                    $query->whereNull('deleted_at');
                }),
            ],
            'code' => ['nullable', 'string', 'max:50', 'unique:villages,code'],
            'name' => ['required', 'string', 'max:255'],
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
