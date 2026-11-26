<?php

namespace App\Models\Legacy;

class LegacyNursingExamination extends LegacyModel
{
    protected $table = 'trx_pemeriksaan_keperawatan';

    protected $primaryKey = 'id';

    protected $guarded = [];

    public function isReadOnly(): bool
    {
        return false;
    }
}
