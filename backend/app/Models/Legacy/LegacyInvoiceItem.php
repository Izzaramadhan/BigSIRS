<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LegacyInvoiceItem extends LegacyModel
{
    protected $table = 'lgs_faktur_detail';

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
        'discount_type',
        'discountType',
        'discount',
        'subtotal',
        'is_repack',
        'isRepack',
        'margin',
        'batch_number',
        'batchNumber',
        'expired_date',
        'expiredDate',
        'notes',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(LegacyInvoice::class, 'id_lgs_faktur');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(LegacyMedicine::class, 'id_obat');
    }

    public function stockLog(): HasOne
    {
        return $this->hasOne(LegacyStockLog::class, 'id_lgs_faktur_detail');
    }

    public function getMedicineIdAttribute(): ?int
    {
        return isset($this->attributes['id_obat']) ? (int) $this->attributes['id_obat'] : null;
    }

    public function getMedicineIdCamelAttribute(): ?int
    {
        return $this->getMedicineIdAttribute();
    }

    public function getMedicineNameAttribute(): string
    {
        return $this->medicine ? $this->medicine->name : 'Obat #' . ($this->attributes['id_obat'] ?? '');
    }

    public function getMedicineNameCamelAttribute(): string
    {
        return $this->getMedicineNameAttribute();
    }

    public function getMedicineCodeAttribute(): ?string
    {
        return $this->medicine?->code;
    }

    public function getMedicineCodeCamelAttribute(): ?string
    {
        return $this->getMedicineCodeAttribute();
    }

    public function getQuantityAttribute(): float
    {
        return (float) ($this->attributes['qty'] ?? 0);
    }

    public function getUnitAttribute(): string
    {
        return $this->medicine?->unit?->name ?? 'Unit';
    }

    public function getPriceAttribute(): float
    {
        return (float) ($this->attributes['harga_beli'] ?? 0);
    }

    public function getUnitPriceAttribute(): float
    {
        return $this->getPriceAttribute();
    }

    public function getUnitPriceCamelAttribute(): float
    {
        return $this->getPriceAttribute();
    }

    public function getDiscountTypeAttribute(): string
    {
        return 'percentage';
    }

    public function getDiscountTypeCamelAttribute(): string
    {
        return $this->getDiscountTypeAttribute();
    }

    public function getDiscountAttribute(): float
    {
        return (float) ($this->attributes['diskon'] ?? 0);
    }

    public function getSubtotalAttribute(): float
    {
        $gross = $this->quantity * $this->price;
        $disc = $this->discount;
        $discAmount = ($disc > 0 && $disc < 1) ? round($gross * $disc, 2) : round($gross * ($disc / 100), 2);
        return max(0, round($gross - $discAmount, 2));
    }

    public function getIsRepackAttribute(): bool
    {
        return ((string) ($this->attributes['repackaged_status'] ?? '0')) === '1';
    }

    public function getIsRepackCamelAttribute(): bool
    {
        return $this->getIsRepackAttribute();
    }

    public function getMarginAttribute(): float
    {
        return 0.2800;
    }

    public function getBatchNumberAttribute(): ?string
    {
        return $this->attributes['batch'] ?? null;
    }

    public function getBatchNumberCamelAttribute(): ?string
    {
        return $this->getBatchNumberAttribute();
    }

    public function getExpiredDateAttribute(): ?string
    {
        $ed = $this->attributes['ed'] ?? null;
        if ($ed === '0000-00-00' || $ed === '') {
            return null;
        }
        return $ed;
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
