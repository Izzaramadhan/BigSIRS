<?php

namespace App\Http\Requests;

use App\Enums\ServiceType;
use App\Support\LegacyAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePolyclinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $bpjsCode = $this->bpjs_code !== null ? trim((string) $this->bpjs_code) : null;
        if ($bpjsCode === '') {
            $bpjsCode = null;
        }

        $this->merge([
            'code' => $this->code !== null ? strtoupper(trim($this->code)) : null,
            'name' => $this->name !== null ? preg_replace('/\s+/', ' ', trim($this->name)) : null,
            'bpjs_code' => $bpjsCode,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isLegacy = LegacyAccess::isAvailable('ref_poliklinik');

        $uniqueRule = $isLegacy
            ? Rule::unique('legacy.ref_poliklinik', 'kode')->where(function ($q) {
                $q->whereNull('deleted_at')
                    ->orWhere('deleted_at', '0000-00-00 00:00:00')
                    ->orWhere('deleted_at', '0000-00-00');
            })
            : Rule::unique('polyclinics', 'code')->whereNull('deleted_at');

        $warehouseRule = LegacyAccess::isAvailable('ref_gudang')
            ? Rule::exists('legacy.ref_gudang', 'id')->where(function ($q) {
                $q->whereNull('deleted_at')
                    ->orWhere('deleted_at', '0000-00-00 00:00:00')
                    ->orWhere('deleted_at', '0000-00-00');
            })
            : Rule::exists('warehouses', 'id')->whereNull('deleted_at');

        $maxCode = $isLegacy ? 11 : 30;
        $maxName = $isLegacy ? 50 : 150;
        $maxDesc = $isLegacy ? 100 : 255;

        return [
            'code' => ['required', 'string', 'max:'.$maxCode, $uniqueRule],
            'name' => ['required', 'string', 'max:'.$maxName],
            'service_type' => ['required', Rule::enum(ServiceType::class)],
            'description' => ['nullable', 'string', 'max:'.$maxDesc],
            'is_visible' => ['boolean'],
            'is_online_visible' => ['boolean'],
            'quota' => ['integer', 'min:0'],
            'jkn_quota' => ['integer', 'min:0'],
            'bpjs_code' => ['nullable', 'string', 'max:50'],
            'warehouse_id' => ['nullable', 'integer', $warehouseRule],
            'is_active' => ['boolean'],
        ];
    }
}
