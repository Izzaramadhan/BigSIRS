<?php

namespace App\Models\Legacy;

class LegacyPrescriptionDetail extends LegacyModel
{
    protected $table = 'trx_resep_detail';

    protected $primaryKey = 'id';

    protected $guarded = [];

    public function isReadOnly(): bool
    {
        return false;
    }
}
