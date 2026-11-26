<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyLabOrder extends LegacyModel
{
    protected $table = 'trx_lab';

    protected $primaryKey = 'id';

    protected $guarded = [];

    public function isReadOnly(): bool
    {
        return false;
    }

    public function details(): HasMany
    {
        return $this->hasMany(LegacyLabOrderDetail::class, 'id_trx_lab', 'id');
    }
}
