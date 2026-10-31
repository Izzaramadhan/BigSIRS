<?php

namespace App\Http\Requests\Logistics;

use Illuminate\Foundation\Http\FormRequest;

class CancelPosSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan pembatalan (void) transaksi kasir wajib diisi.',
            'reason.min' => 'Alasan pembatalan minimal 3 karakter.',
        ];
    }
}

