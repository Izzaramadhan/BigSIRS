<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyLabOrderDetail extends LegacyModel
{
    protected $table = 'trx_lab_detail_pemeriksaan';

    protected $primaryKey = 'id';

    protected $guarded = [];

    public function isReadOnly(): bool
    {
        return false;
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(LegacyLaboratoryItem::class, 'id_item_lab', 'id');
    }
}
