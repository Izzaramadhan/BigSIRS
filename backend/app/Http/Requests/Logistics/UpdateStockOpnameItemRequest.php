<?php

namespace App\Http\Requests\Logistics;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockOpnameItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'physical_stock' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'physical_stock.required' => 'Jumlah stok fisik wajib diisi.',
            'physical_stock.numeric' => 'Jumlah stok fisik harus berupa angka.',
            'physical_stock.min' => 'Jumlah stok fisik tidak boleh bernilai negatif.',
        ];
    }
}
