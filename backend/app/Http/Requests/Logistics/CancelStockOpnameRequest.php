<?php

namespace App\Http\Requests\Logistics;

use Illuminate\Foundation\Http\FormRequest;

class CancelStockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancel_reason' => ['required', 'string', 'max:500'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'cancel_reason.required' => 'Alasan pembatalan sesi stok opname wajib diisi.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('cancel_reason') && $this->has('reason')) {
            $this->merge(['cancel_reason' => $this->input('reason')]);
        }
    }
}
