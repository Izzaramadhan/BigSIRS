<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyStockMutationItem extends LegacyModel
{
    protected $table = 'lgs_mutasi_detail';

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
        'mutation_id',
        'quantity',
        'medicine_id',
        'medicine_name',
        'medicine_code',
        'batch_number',
        'expired_date',
        'unit',
    ];

    public function mutation(): BelongsTo
    {
        return $this->belongsTo(LegacyStockMutation::class, 'id_mutasi');
    }

    public function fakturDetail(): BelongsTo
    {
        return $this->belongsTo(LegacyInvoiceItem::class, 'id_lgs_faktur_detail');
    }

    public function getMutationIdAttribute(): ?int
    {
        return isset($this->attributes['id_mutasi']) ? (int) $this->attributes['id_mutasi'] : null;
    }

    public function getQuantityAttribute(): float
    {
        return (float) ($this->attributes['qty'] ?? 0);
    }

    public function getMedicineIdAttribute(): ?int
    {
        return $this->fakturDetail?->id_obat;
    }

    public function getMedicineNameAttribute(): string
    {
        return $this->fakturDetail?->medicine?->name ?? 'Obat';
    }

    public function getMedicineCodeAttribute(): ?string
    {
        return $this->fakturDetail?->medicine?->code;
    }

    public function getBatchNumberAttribute(): ?string
    {
        return $this->fakturDetail?->batch;
    }

    public function getExpiredDateAttribute(): ?string
    {
        return $this->fakturDetail?->ed;
    }

    public function getUnitAttribute(): string
    {
        return $this->fakturDetail?->medicine?->unit?->name ?? 'Unit';
    }
}

