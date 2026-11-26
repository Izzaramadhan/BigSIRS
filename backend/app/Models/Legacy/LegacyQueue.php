<?php

namespace App\Models\Legacy;

class LegacyQueue extends LegacyModel
{
    protected $table = 'trx_antrian';

    protected $primaryKey = 'id';

    protected $guarded = [];

    public function isReadOnly(): bool
    {
        return false;
    }
}
