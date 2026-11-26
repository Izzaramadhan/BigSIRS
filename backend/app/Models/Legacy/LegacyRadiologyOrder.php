<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyRadiologyOrder extends LegacyModel
{
    protected $table = 'trx_rad';

    protected $primaryKey = 'id';

    protected $guarded = [];

    public function isReadOnly(): bool
    {
        return false;
    }

    public function details(): HasMany
    {
        return $this->hasMany(LegacyRadiologyOrderDetail::class, 'id_trx_rad', 'id');
    }
}
