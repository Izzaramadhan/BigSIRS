<?php

namespace App\Http\Requests\MasterData;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFollowUpHandlingRequest extends FormRequest
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
        $uniqueNameRule = LegacyAccess::isAvailable('ref_penanganan_lanjutan')
            ? Rule::unique('legacy.ref_penanganan_lanjutan', 'nama')->whereNull('deleted_at')
            : Rule::unique('follow_up_handlings', 'name')->whereNull('deleted_at');

        return [
            'name' => ['required', 'string', 'max:250', $uniqueNameRule],
            'code' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
            'inacbg_code' => ['nullable', 'integer'],
        ];
    }
}
