<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyProcedurePackageItem extends LegacyModel
{
    protected $table = 'map_paket_tindakan';

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
        'medical_procedure_id',
        'medical_procedure_tariff_id',
        'quantity',
        'unit_amount',
        'subtotal_amount',
        'sort_order',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(LegacyProcedurePackage::class, 'id_ref_paket_tindakan');
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(LegacyMedicalProcedure::class, 'id_ref_tarif_tindakan');
    }

    public function tariff(): BelongsTo
    {
        return $this->procedure();
    }

    public function getMedicalProcedureIdAttribute(): ?int
    {
        return isset($this->attributes['id_ref_tarif_tindakan']) ? (int) $this->attributes['id_ref_tarif_tindakan'] : null;
    }

    public function getMedicalProcedureTariffIdAttribute(): ?int
    {
        return $this->getMedicalProcedureIdAttribute();
    }

    public function getQuantityAttribute(): int
    {
        return 1;
    }

    public function getUnitAmountAttribute(): float
    {
        return (float) ($this->procedure?->attributes['harga'] ?? 0);
    }

    public function getSubtotalAmountAttribute(): float
    {
        return $this->getUnitAmountAttribute();
    }

    public function getSortOrderAttribute(): int
    {
        return (int) $this->id;
    }
}

