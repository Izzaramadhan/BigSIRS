<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVillageRequest extends FormRequest
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
                \Illuminate\Validation\Rule::exists('districts', 'id')->where(function ($query) {
                    $query->where('is_active', true)->whereNull('deleted_at');
                })
            ],
            'code' => [
                'nullable', 
                'string', 
                'max:50', 
                Rule::unique('villages', 'code')->ignore($this->village)
            ],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
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
