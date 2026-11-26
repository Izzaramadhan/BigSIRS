<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyRadiologyOrderDetail extends LegacyModel
{
    protected $table = 'trx_rad_detail_pemeriksaan';

    protected $primaryKey = 'id';

    protected $guarded = [];

    public function isReadOnly(): bool
    {
        return false;
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(LegacyRadiologyItem::class, 'id_item_rad', 'id');
    }
}
