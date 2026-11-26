<?php

namespace App\Models\Legacy;

class LegacyClinicalExamination extends LegacyModel
{
    protected $table = 'trx_pemeriksaan';

    protected $primaryKey = 'id';

    protected $guarded = [];

    public function isReadOnly(): bool
    {
        return false;
    }
}
