<?php

namespace App\Http\Requests\MasterData;

use App\Models\Legacy\LegacyMedicationSigna;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicationSignaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $signa = $this->route('medication_signa');
        $id = $signa instanceof \Illuminate\Database\Eloquent\Model ? $signa->id : $signa;
        $legacy = LegacyMedicationSigna::find($id);

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
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
        ]);
    }
}
