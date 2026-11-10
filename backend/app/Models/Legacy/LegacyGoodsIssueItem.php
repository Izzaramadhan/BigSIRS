<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyGoodsIssueItem extends LegacyModel
{
    protected $table = 'lgs_pengeluaran_detail';

    protected $guarded = ['id'];

    public $timestamps = false;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function getDeletedAtColumnName(): ?string
    {
        return 'deleted_at';
    }

    protected $appends = [
        'goods_issue_id',
        'pos_sale_id',
        'warehouse_stock_id',
        'warehouseStockId',
        'medicine_id',
        'medicineId',
        'medicine_name',
        'medicineName',
        'medicine_code',
        'medicineCode',
        'quantity',
        'unit',
        'price',
        'unit_price',
        'unitPrice',
        'subtotal',
        'batch_number',
        'batchNumber',
        'expired_date',
        'expiredDate',
        'notes',
    ];

    public function goodsIssue(): BelongsTo
    {
        return $this->belongsTo(LegacyGoodsIssue::class, 'id_lgs_pengeluaran');
    }

    public function stockLog(): BelongsTo
    {
        return $this->belongsTo(LegacyStockLog::class, 'id_lgs_log_stok');
    }

    public function warehouseStock(): BelongsTo
    {
        return $this->stockLog();
    }

    public function getMedicineAttribute(): ?LegacyMedicine
    {
        return $this->stockLog?->medicine;
    }

    public function getGoodsIssueIdAttribute(): ?int
    {
        return isset($this->attributes['id_lgs_pengeluaran']) ? (int) $this->attributes['id_lgs_pengeluaran'] : null;
    }

    public function getPosSaleIdAttribute(): ?int
    {
        return $this->getGoodsIssueIdAttribute();
    }

    public function getWarehouseStockIdAttribute(): ?int
    {
        return isset($this->attributes['id_lgs_log_stok']) ? (int) $this->attributes['id_lgs_log_stok'] : null;
    }

    public function getWarehouseStockIdCamelAttribute(): ?int
    {
        return $this->getWarehouseStockIdAttribute();
    }

    public function getMedicineIdAttribute(): ?int
    {
        return $this->stockLog?->medicine_id;
    }

    public function getMedicineIdCamelAttribute(): ?int
    {
        return $this->getMedicineIdAttribute();
    }

    public function getMedicineNameAttribute(): string
    {
        return $this->stockLog?->medicine?->name ?? 'Obat';
    }

    public function getMedicineNameCamelAttribute(): string
    {
        return $this->getMedicineNameAttribute();
    }

    public function getMedicineCodeAttribute(): ?string
    {
        return $this->stockLog?->medicine?->code;
    }

    public function getMedicineCodeCamelAttribute(): ?string
    {
        return $this->getMedicineCodeAttribute();
    }

    public function getQuantityAttribute(): float
    {
        return (float) ($this->attributes['jumlah'] ?? 0);
    }

    public function getUnitAttribute(): string
    {
        return $this->stockLog?->medicine?->unit?->name ?? 'Unit';
    }

    public function getPriceAttribute(): float
    {
        return (float) ($this->stockLog?->selling_price ?? 0);
    }

    public function getUnitPriceAttribute(): float
    {
        return $this->getPriceAttribute();
    }

    public function getUnitPriceCamelAttribute(): float
    {
        return $this->getPriceAttribute();
    }

    public function getSubtotalAttribute(): float
    {
        return round($this->quantity * $this->price, 2);
    }

    public function getBatchNumberAttribute(): ?string
    {
        return $this->stockLog?->batch_number;
    }

    public function getBatchNumberCamelAttribute(): ?string
    {
        return $this->getBatchNumberAttribute();
    }

    public function getExpiredDateAttribute(): ?string
    {
        return $this->stockLog?->expired_date;
    }

    public function getExpiredDateCamelAttribute(): ?string
    {
        return $this->getExpiredDateAttribute();
    }

    public function getNotesAttribute(): ?string
    {
        return null;
    }
}
