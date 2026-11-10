<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyTariffTypeComponent extends LegacyModel
{
    protected $table = 'map_jenis_tarif_komponen';

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

    protected $appends = ['tariff_component_id', 'percentage'];

    public function tariffType(): BelongsTo
    {
        return $this->belongsTo(LegacyTariffType::class, 'id_jenis_tarif');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(LegacyTariffComponent::class, 'id_komponen');
    }

    public function getTariffComponentIdAttribute(): ?int
    {
        return isset($this->attributes['id_komponen']) ? (int) $this->attributes['id_komponen'] : null;
    }

    public function getPercentageAttribute(): float
    {
        return (float) ($this->attributes['persen'] ?? 0);
    }
}

