<?php

namespace App\Models\Legacy;

class LegacyVitalSign extends LegacyModel
{
    protected $table = 'trx_vital_sign';

    protected $primaryKey = 'id';

    protected $guarded = [];

    public function isReadOnly(): bool
    {
        return false;
    }
}
