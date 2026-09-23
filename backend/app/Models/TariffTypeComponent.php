<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TariffTypeComponent extends Model
{
    protected $fillable = [
        'legacy_id',
        'tariff_type_id',
        'tariff_component_id',
        'percentage',
        'needs_review',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'needs_review' => 'boolean',
    ];

    public function type()
    {
        return $this->belongsTo(TariffType::class, 'tariff_type_id');
    }

    public function component()
    {
        return $this->belongsTo(TariffComponent::class, 'tariff_component_id');
    }
}
