<?php

namespace App\Http\Requests\Logistics;

use App\Models\MasterData\Warehouse;
use App\Support\LegacyAccess;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGoodsIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        // Warehouse resolution
        $rawWarehouse = $this->input('warehouse_id') ?? $this->input('warehouseId') ?? $this->input('warehouse');
        if (is_numeric($rawWarehouse)) {
            $merge['warehouse_id'] = (int) $rawWarehouse;
        } elseif (is_string($rawWarehouse) && ! empty($rawWarehouse)) {
            if (LegacyAccess::isAvailable('ref_gudang')) {
                $wh = \App\Models\Legacy\LegacyWarehouse::where('nama', $rawWarehouse)->first();
            } else {
                $wh = Warehouse::where('name', $rawWarehouse)->first();
            }
            $merge['warehouse_id'] = $wh?->id;
        }

        // Issue Date & Time
        $rawDate = $this->input('issue_date') ?? $this->input('date');
        $rawTime = $this->input('time') ?? '08:00';
        if ($rawDate) {
            try {
                $merge['issue_date'] = Carbon::parse("{$rawDate} {$rawTime}")->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                $merge['issue_date'] = Carbon::now()->format('Y-m-d H:i:s');
            }
        }

        // Officer
        $officerId = $this->input('officer_id') ?? $this->input('officerId');
        $officerName = $this->input('officer_name') ?? $this->input('officerName');

        if ($officerId) {
            $merge['officer_id'] = (int) $officerId;
            if (empty($officerName)) {
                if (LegacyAccess::isAvailable('ref_pegawai')) {
                    $employee = \App\Models\Legacy\LegacyEmployee::find($officerId);
                } else {
                    $employee = \App\Models\Employee::find($officerId);
                }
                if ($employee) {
                    $merge['officer_name'] = $employee->name;
                    $merge['officer_role'] = $employee->position?->name ?? 'Petugas Logistik Farmasi';
                }
            }
        } elseif (! empty($officerName)) {
            if (LegacyAccess::isAvailable('ref_pegawai') && LegacyAccess::isAvailable('master_person_index')) {
                $emp = \App\Models\Legacy\LegacyEmployee::join('master_person_index', 'ref_pegawai.id_mpi', '=', 'master_person_index.id')
                    ->where('master_person_index.nama', $officerName)
                    ->select('ref_pegawai.id')
                    ->first();
                if ($emp) {
                    $merge['officer_id'] = $emp->id;
                }
            }
        }
        if (! empty($officerName)) {
            $merge['officer_name'] = $officerName;
        }
        if ($this->has('officerRole') && ! $this->has('officer_role')) {
            $merge['officer_role'] = $this->input('officerRole');
        }

        // Purpose
        if ($this->has('notes') && ! $this->has('purpose')) {
            $merge['purpose'] = $this->input('notes') ?: 'Kebutuhan Internal';
        }

        // Location Detail
        if ($this->has('locationDetail') && ! $this->has('location_detail')) {
            $merge['location_detail'] = $this->input('locationDetail');
        }

        // Reference Number
        if ($this->has('referenceNumber') && ! $this->has('reference_number')) {
            $merge['reference_number'] = $this->input('referenceNumber');
        }

        // Draft flag
        if ($this->has('isDraft') && ! $this->has('is_draft')) {
            $merge['is_draft'] = filter_var($this->input('isDraft'), FILTER_VALIDATE_BOOLEAN);
        }

        // Items normalization
        if ($this->has('items') && is_array($this->input('items'))) {
            $normalizedItems = [];
            foreach ($this->input('items') as $item) {
                $normalizedItems[] = [
                    'warehouse_stock_id' => $item['warehouse_stock_id'] ?? $item['warehouseStockId'] ?? null,
                    'medicine_id' => $item['medicine_id'] ?? $item['medicineId'] ?? null,
                    'batch_number' => $item['batch_number'] ?? $item['batchNumber'] ?? null,
                    'expired_date' => $item['expired_date'] ?? $item['expDate'] ?? null,
                    'quantity' => $item['quantity'] ?? null,
                    'unit' => $item['unit'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ];
            }
            $merge['items'] = $normalizedItems;
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        $isLegacy = LegacyAccess::isAvailable('lgs_pengeluaran');
        $warehouseTable = $isLegacy ? 'legacy.ref_gudang' : 'warehouses';
        $employeeTable = $isLegacy ? 'legacy.ref_pegawai' : 'employees';
        $stockTable = $isLegacy ? 'legacy.lgs_log_stok' : 'warehouse_stocks';
        $medicineTable = $isLegacy ? 'legacy.ref_obat' : 'medicines';

        return [
            'warehouse_id' => ['sometimes', 'required', 'integer', "exists:{$warehouseTable},id"],
            'issue_date' => ['sometimes', 'required', 'date'],
            'location_detail' => ['nullable', 'string', 'max:255'],
            'officer_name' => ['sometimes', 'required', 'string', 'max:255'],
            'officer_id' => ['nullable', 'integer', "exists:{$employeeTable},id"],
            'officer_role' => ['nullable', 'string', 'max:255'],
            'purpose' => ['sometimes', 'required', 'string', 'max:100'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_draft' => ['nullable', 'boolean'],
            'items' => ['sometimes', 'required', 'array', 'min:1'],
            'items.*.warehouse_stock_id' => ['nullable', 'integer', "exists:{$stockTable},id"],
            'items.*.medicine_id' => ['required', 'integer', "exists:{$medicineTable},id"],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.expired_date' => ['nullable', 'date'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['nullable', 'string', 'max:100'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'Gudang asal wajib dipilih.',
            'warehouse_id.exists' => 'Gudang asal tidak valid.',
            'officer_name.required' => 'Petugas otorisasi wajib diisi.',
            'purpose.required' => 'Jenis keperluan wajib dipilih.',
            'items.required' => 'Minimal satu item obat harus ditambahkan.',
            'items.min' => 'Minimal satu item obat harus ditambahkan.',
            'items.*.medicine_id.required' => 'Obat wajib dipilih.',
            'items.*.medicine_id.exists' => 'Obat tidak ditemukan.',
            'items.*.quantity.required' => 'Kuantitas obat wajib diisi.',
            'items.*.quantity.gt' => 'Kuantitas obat harus lebih besar dari 0.',
        ];
    }
}

