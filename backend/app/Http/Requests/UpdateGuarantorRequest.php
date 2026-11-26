<?php

namespace App\Http\Requests;

use App\Enums\GuarantorType;
use App\Support\LegacyAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateGuarantorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('code')) {
            $code = $this->code !== null ? strtoupper(trim((string) $this->code)) : null;
            $this->merge(['code' => $code]);
        }

        if ($this->exists('name')) {
            $name = $this->name !== null ? preg_replace('/\s+/', ' ', trim((string) $this->name)) : null;
            $this->merge(['name' => $name]);
        }

        foreach (['legacy_id', 'inacbg_id'] as $field) {
            if ($this->exists($field) && trim((string) $this->input($field)) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $routeParam = $this->route('guarantor');
        $id = $routeParam instanceof Model ? $routeParam->getKey() : $routeParam;

        $uniqueCodeRule = LegacyAccess::isAvailable('ref_jenis_asuransi')
            ? Rule::unique('legacy.ref_jenis_asuransi', 'kode')->ignore($id)->whereNull('deleted_at')
            : Rule::unique('guarantors', 'code')->ignore($id)->whereNull('deleted_at');

        return [
            'code' => ['sometimes', 'required', 'string', 'max:50', $uniqueCodeRule],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'legacy_id' => ['sometimes', 'nullable', 'integer'],
            'type' => ['sometimes', 'required', new Enum(GuarantorType::class)],
            'is_active' => ['sometimes', 'boolean'],
            'is_government' => ['sometimes', 'required', 'boolean'],
            'inacbg_id' => ['sometimes', 'nullable', 'string', 'max:10'],
        ];
    }
}
