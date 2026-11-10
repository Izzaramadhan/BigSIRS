<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyInvoice extends LegacyModel
{
    protected $table = 'lgs_faktur';

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
        'invoice_number',
        'invoiceNumber',
        'reference_number',
        'referenceNumber',
        'invoice_date',
        'invoiceDate',
        'due_date',
        'dueDate',
        'supplier_id',
        'supplierId',
        'supplier_name',
        'supplierName',
        'warehouse_id',
        'warehouseId',
        'warehouse_name',
        'warehouseName',
        'subtotal',
        'subtotal_amount',
        'subtotalAmount',
        'discount',
        'discount_amount',
        'discountAmount',
        'tax',
        'tax_amount',
        'taxAmount',
        'tax_type',
        'taxType',
        'tax_rate',
        'taxRate',
        'total_amount',
        'totalAmount',
        'paid_amount',
        'paidAmount',
        'paid_at',
        'paidAt',
        'status',
        'notes',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(LegacySupplier::class, 'id_supplier');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(LegacyWarehouse::class, 'warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LegacyInvoiceItem::class, 'id_lgs_faktur');
    }

    public function getInvoiceNumberAttribute(): string
    {
        return (string) ($this->attributes['nomor'] ?? '');
    }

    public function getInvoiceNumberCamelAttribute(): string
    {
        return $this->getInvoiceNumberAttribute();
    }

    public function getReferenceNumberAttribute(): string
    {
        return (string) ($this->attributes['nomor'] ?? '');
    }

    public function getReferenceNumberCamelAttribute(): string
    {
        return $this->getReferenceNumberAttribute();
    }

    public function getInvoiceDateAttribute(): ?string
    {
        return $this->attributes['tanggal'] ?? null;
    }

    public function getInvoiceDateCamelAttribute(): ?string
    {
        return $this->getInvoiceDateAttribute();
    }

    public function getDueDateAttribute(): ?string
    {
        $due = $this->attributes['jatuh_tempo'] ?? null;
        if ($due === '0000-00-00' || $due === '') {
            return null;
        }
        return $due;
    }

    public function getDueDateCamelAttribute(): ?string
    {
        return $this->getDueDateAttribute();
    }

    public function getSupplierIdAttribute(): ?int
    {
        return isset($this->attributes['id_supplier']) ? (int) $this->attributes['id_supplier'] : null;
    }

    public function getSupplierIdCamelAttribute(): ?int
    {
        return $this->getSupplierIdAttribute();
    }

    public function getSupplierNameAttribute(): string
    {
        return $this->supplier ? $this->supplier->name : 'Supplier #' . ($this->attributes['id_supplier'] ?? '');
    }

    public function getSupplierNameCamelAttribute(): string
    {
        return $this->getSupplierNameAttribute();
    }

    public function getWarehouseIdAttribute(): int
    {
        $stockLogWarehouse = $this->relationLoaded('items')
            ? $this->items->first()?->stockLog?->id_gudang
            : null;
        return (int) ($stockLogWarehouse ?? 1);
    }

    public function getWarehouseIdCamelAttribute(): int
    {
        return $this->getWarehouseIdAttribute();
    }

    public function getWarehouseNameAttribute(): string
    {
        return 'Gudang Utama';
    }

    public function getWarehouseNameCamelAttribute(): string
    {
        return $this->getWarehouseNameAttribute();
    }

    public function getSubtotalAttribute(): float
    {
        return (float) ($this->attributes['jumlah'] ?? 0);
    }

    public function getSubtotalAmountAttribute(): float
    {
        return $this->getSubtotalAttribute();
    }

    public function getSubtotalAmountCamelAttribute(): float
    {
        return $this->getSubtotalAttribute();
    }

    public function getDiscountAttribute(): float
    {
        return (float) ($this->attributes['diskon'] ?? 0);
    }

    public function getDiscountAmountAttribute(): float
    {
        return $this->getDiscountAttribute();
    }

    public function getDiscountAmountCamelAttribute(): float
    {
        return $this->getDiscountAttribute();
    }

    public function getTaxAttribute(): float
    {
        return (float) ($this->attributes['ppn'] ?? 0);
    }

    public function getTaxAmountAttribute(): float
    {
        return $this->getTaxAttribute();
    }

    public function getTaxAmountCamelAttribute(): float
    {
        return $this->getTaxAttribute();
    }

    public function getTaxTypeAttribute(): string
    {
        return ((float) ($this->attributes['ppn'] ?? 0)) > 0 ? 'ppn_11' : 'non_tax';
    }

    public function getTaxTypeCamelAttribute(): string
    {
        return $this->getTaxTypeAttribute();
    }

    public function getTaxRateAttribute(): float
    {
        return ((float) ($this->attributes['ppn'] ?? 0)) > 0 ? 11.00 : 0.00;
    }

    public function getTaxRateCamelAttribute(): float
    {
        return $this->getTaxRateAttribute();
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) ($this->attributes['total'] ?? 0);
    }

    public function getTotalAmountCamelAttribute(): float
    {
        return $this->getTotalAmountAttribute();
    }

    public function getPaidAmountAttribute(): float
    {
        if (isset($this->attributes['jumlah_bayar']) && $this->attributes['jumlah_bayar'] !== null) {
            return (float) $this->attributes['jumlah_bayar'];
        }
        return ((string) ($this->attributes['lunas'] ?? '0')) === '1'
            ? (float) ($this->attributes['total'] ?? 0)
            : 0.00;
    }

    public function getPaidAmountCamelAttribute(): float
    {
        return $this->getPaidAmountAttribute();
    }

    public function getPaidAtAttribute(): ?string
    {
        return $this->attributes['tanggal_lunas'] ?? null;
    }

    public function getPaidAtCamelAttribute(): ?string
    {
        return $this->getPaidAtAttribute();
    }

    public function getStatusAttribute(): string
    {
        return ((string) ($this->attributes['lunas'] ?? '0')) === '1' ? 'Lunas' : 'Belum Lunas';
    }

    public function getNotesAttribute(): ?string
    {
        return $this->attributes['keterangan'] ?? null;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->where(function ($q) use ($term) {
            $q->where('nomor', 'like', $term)
              ->orWhere('keterangan', 'like', $term)
              ->orWhereHas('supplier', function ($sub) use ($term) {
                  $sub->where('nama', 'like', $term);
              });
        });
    }
}
