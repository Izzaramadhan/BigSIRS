<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyPatientIcd9 extends LegacyModel
{
    protected $table = 'trx_icd_9';

    protected $primaryKey = 'id';

    protected $guarded = [];

    public function isReadOnly(): bool
    {
        return false;
    }

    public function icd9(): BelongsTo
    {
        return $this->belongsTo(LegacyIcd9::class, 'id_icd_9', 'id');
    }
}
