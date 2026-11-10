<?php

namespace App\Http\Requests\MasterData;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFollowUpHandlingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('follow_up_handling')?->id ?? $this->route('follow_up_handling');

        $uniqueNameRule = LegacyAccess::isAvailable('ref_penanganan_lanjutan')
            ? Rule::unique('legacy.ref_penanganan_lanjutan', 'nama')->ignore($id)->whereNull('deleted_at')
            : Rule::unique('follow_up_handlings', 'name')->ignore($id)->whereNull('deleted_at');

        return [
            'name' => ['required', 'string', 'max:250', $uniqueNameRule],
            'code' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
            'inacbg_code' => ['nullable', 'integer'],
        ];
    }
}
