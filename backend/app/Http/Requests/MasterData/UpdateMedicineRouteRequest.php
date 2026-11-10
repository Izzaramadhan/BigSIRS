<?php

namespace App\Http\Requests\MasterData;

use App\Models\Legacy\LegacyMedicineRoute;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicineRouteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $route = $this->route('medicine_route');
        $id = $route instanceof \Illuminate\Database\Eloquent\Model ? $route->id : $route;
        $legacy = LegacyMedicineRoute::find($id);

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
            'object_code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'object_code' => is_string($this->object_code) ? trim($this->object_code) : $this->object_code,
        ]);
    }
}
