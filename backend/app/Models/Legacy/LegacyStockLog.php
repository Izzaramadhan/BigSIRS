<?php

namespace App\Models\Legacy;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyStockLog extends LegacyModel
{
    protected $table = 'lgs_log_stok';

    protected $guarded = ['id'];

    public $timestamps = false;

    /**
     * Physical stock log is enabled for direct operational CRUD.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function getDeletedAtColumnName(): ?string
    {
        return 'deleted_at';
    }

    protected $appends = [
        'stock_id',
        'warehouse_stock_id',
        'warehouse_id',
        'warehouseId',
        'warehouse_name',
        'warehouseName',
        'quantity',
        'available_stock',
        'availableStock',
        'selling_price',
        'price',
        'unit_price',
        'unitPrice',
        'medicine_id',
        'medicineId',
        'batch_number',
        'batchNumber',
        'expired_date',
        'expiredDate',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(LegacyWarehouse::class, 'id_gudang');
    }

    public function fakturDetail(): BelongsTo
    {
        return $this->belongsTo(LegacyInvoiceItem::class, 'id_lgs_faktur_detail');
    }

    /**
     * Direct alias for compatibility with code expecting item.medicine
     */
    public function getMedicineAttribute(): ?LegacyMedicine
    {
        return $this->fakturDetail?->medicine;
    }

    public function getStockIdAttribute(): int
    {
        return (int) $this->attributes['id'];
    }

    public function getWarehouseStockIdAttribute(): int
    {
        return (int) $this->attributes['id'];
    }

    public function getWarehouseIdAttribute(): ?int
    {
        return isset($this->attributes['id_gudang']) ? (int) $this->attributes['id_gudang'] : null;
    }

    public function getWarehouseIdCamelAttribute(): ?int
    {
        return $this->getWarehouseIdAttribute();
    }

    public function getWarehouseNameAttribute(): ?string
    {
        return $this->warehouse?->name;
    }

    public function getWarehouseNameCamelAttribute(): ?string
    {
        return $this->getWarehouseNameAttribute();
    }

    public function getQuantityAttribute(): float
    {
        return (float) ($this->attributes['qty'] ?? 0);
    }

    public function getAvailableStockAttribute(): float
    {
        return $this->getQuantityAttribute();
    }

    public function getAvailableStockCamelAttribute(): float
    {
        return $this->getQuantityAttribute();
    }

    public function getSellingPriceAttribute(): float
    {
        return (float) ($this->attributes['harga_jual'] ?? 0);
    }

    public function getPriceAttribute(): float
    {
        return $this->getSellingPriceAttribute();
    }

    public function getUnitPriceAttribute(): float
    {
        return $this->getSellingPriceAttribute();
    }

    public function getUnitPriceCamelAttribute(): float
    {
        return $this->getSellingPriceAttribute();
    }

    public function getMedicineIdAttribute(): ?int
    {
        return $this->fakturDetail?->id_obat;
    }

    public function getMedicineIdCamelAttribute(): ?int
    {
        return $this->getMedicineIdAttribute();
    }

    public function getBatchNumberAttribute(): ?string
    {
        return $this->fakturDetail?->batch;
    }

    public function getBatchNumberCamelAttribute(): ?string
    {
        return $this->getBatchNumberAttribute();
    }

    public function getExpiredDateAttribute(): ?string
    {
        return $this->fakturDetail?->ed;
    }

    public function getExpiredDateCamelAttribute(): ?string
    {
        return $this->getExpiredDateAttribute();
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('qty', '>', 0);
    }

    public function scopeByWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('id_gudang', $warehouseId);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->where(function ($q) use ($term) {
            $q->whereHas('fakturDetail', function ($fdQ) use ($term) {
                $fdQ->where('batch', 'like', $term)
                    ->orWhereHas('medicine', function ($mQ) use ($term) {
                        $mQ->where('nama', 'like', $term)
                           ->orWhere('kode', 'like', $term);
                    });
            });
        });
    }
}

