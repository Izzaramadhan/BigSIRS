<?php

namespace App\Http\Requests;

use App\Enums\GuarantorType;
use App\Support\LegacyAccess;
use Illuminate\Contracts\Validation\ValidationRule;
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
        $guarantor = $this->route('guarantor');
        $code = $this->code;
        if ($code === null || trim((string) $code) === '') {
            $code = (is_object($guarantor) ? ($guarantor->code ?? null) : null) ?? ('ASR-' . strtoupper(substr(uniqid(), -6)));
        }

        $type = $this->type;
        if ($type === null || trim((string) $type) === '') {
            $existingType = is_object($guarantor) && isset($guarantor->type) ? ($guarantor->type instanceof GuarantorType ? $guarantor->type->value : (string) $guarantor->type) : null;
            $type = $existingType ?? (filter_var($this->is_government, FILTER_VALIDATE_BOOLEAN)
                ? GuarantorType::Bpjs->value
                : GuarantorType::Other->value);
        }

        $this->merge([
            'code' => strtoupper(trim((string) $code)),
            'type' => $type,
            'name' => $this->name !== null ? preg_replace('/\s+/', ' ', trim($this->name)) : null,
        ]);

        foreach (['legacy_id', 'inacbg_id'] as $field) {
            if ($this->has($field) && trim((string) $this->get($field)) === '') {
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
        $id = $this->route('guarantor')?->id ?? $this->route('guarantor');

        $uniqueCodeRule = LegacyAccess::isAvailable('ref_jenis_asuransi')
            ? Rule::unique('legacy.ref_jenis_asuransi', 'kode')->ignore($id)->whereNull('deleted_at')
            : Rule::unique('guarantors', 'code')->ignore($id)->whereNull('deleted_at');

        return [
            'code' => ['required', 'string', 'max:50', $uniqueCodeRule],
            'name' => ['required', 'string', 'max:150'],
            'legacy_id' => ['nullable', 'integer'],
            'type' => ['required', new Enum(GuarantorType::class)],
            'is_active' => ['boolean'],
            'is_government' => ['required', 'boolean'],
            'inacbg_id' => ['nullable', 'string', 'max:10'],
        ];
    }
}
