<?php

namespace App\Http\Requests\MasterData;

use App\Models\Legacy\LegacyMedicineCategory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicineCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $category = $this->route('medicine_category');
        $id = $category instanceof \Illuminate\Database\Eloquent\Model ? $category->id : $category;
        $legacy = LegacyMedicineCategory::find($id);

        return $legacy ? ($this->user()?->can('update', $legacy) ?? true) : true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'description' => is_string($this->description) ? trim($this->description) : $this->description,
        ]);
    }
}
