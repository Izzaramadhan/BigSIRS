<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyMedicinePackageItem extends LegacyModel
{
    protected $table = 'map_paket_obat';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * MedicinePackageItem is enabled for controlled direct CRUD.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    protected $appends = [
        'medicine_package_id',
        'medicine_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(LegacyMedicinePackage::class, 'id_ref_paket_obat');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(LegacyMedicine::class, 'id_ref_obat');
    }

    // Accessors & Mutators
    public function getMedicinePackageIdAttribute(): ?int
    {
        return isset($this->attributes['id_ref_paket_obat']) ? (int) $this->attributes['id_ref_paket_obat'] : null;
    }

    public function setMedicinePackageIdAttribute($value): void
    {
        $this->attributes['id_ref_paket_obat'] = $value !== null ? (int) $value : null;
    }

    public function getMedicineIdAttribute(): ?int
    {
        return isset($this->attributes['id_ref_obat']) ? (int) $this->attributes['id_ref_obat'] : null;
    }

    public function setMedicineIdAttribute($value): void
    {
        $this->attributes['id_ref_obat'] = $value !== null ? (int) $value : null;
    }

    public function getQuantityAttribute(): int
    {
        return isset($this->attributes['jumlah']) ? (int) $this->attributes['jumlah'] : 1;
    }

    public function setQuantityAttribute($value): void
    {
        $this->attributes['jumlah'] = $value !== null ? (int) $value : 1;
    }

    public function getSubtotalAttribute(): float
    {
        return isset($this->attributes['sub_total']) ? (float) $this->attributes['sub_total'] : 0.0;
    }

    public function setSubtotalAttribute($value): void
    {
        $this->attributes['sub_total'] = $value !== null ? round((float) $value) : 0;
    }

    public function getUnitPriceAttribute(): float
    {
        $qty = $this->getQuantityAttribute();
        if ($qty > 0) {
            return round($this->getSubtotalAttribute() / $qty, 2);
        }

        return 0.0;
    }
}

