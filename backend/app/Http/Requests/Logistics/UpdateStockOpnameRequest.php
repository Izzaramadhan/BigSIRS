<?php

namespace App\Http\Requests\Logistics;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('sellingPrice') && ! $this->has('selling_price')) {
            $merge['selling_price'] = $this->input('sellingPrice');
        } elseif ($this->has('harga_jual') && ! $this->has('selling_price')) {
            $merge['selling_price'] = $this->input('harga_jual');
        }

        if ($this->has('expirationDate') && ! $this->has('expiration_date')) {
            $merge['expiration_date'] = $this->input('expirationDate');
        } elseif ($this->has('tgl_kadaluarsa') && ! $this->has('expiration_date')) {
            $merge['expiration_date'] = $this->input('tgl_kadaluarsa');
        }

        if ($this->has('quantity') && ! $this->has('stock')) {
            $merge['stock'] = $this->input('quantity');
        } elseif ($this->has('stok') && ! $this->has('stock')) {
            $merge['stock'] = $this->input('stok');
        }

        if ($this->has('warehouseId') && ! $this->has('warehouse_id')) {
            $merge['warehouse_id'] = $this->input('warehouseId');
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'stock' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'expiration_date' => ['nullable', 'date'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'stock.required' => 'Stok sediaan wajib diisi.',
            'stock.numeric' => 'Stok sediaan harus berupa angka valid.',
            'stock.min' => 'Stok sediaan tidak boleh bernilai negatif.',
            'selling_price.required' => 'Harga jual sediaan wajib diisi.',
            'selling_price.numeric' => 'Harga jual harus berupa angka valid.',
            'selling_price.min' => 'Harga jual tidak boleh bernilai negatif.',
            'expiration_date.date' => 'Format tanggal kadaluarsa tidak valid.',
            'warehouse_id.exists' => 'Gudang yang dipilih tidak ditemukan.',
        ];
    }
}
