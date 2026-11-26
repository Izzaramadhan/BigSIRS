<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyPrescription extends LegacyModel
{
    protected $table = 'trx_resep';

    protected $primaryKey = 'id';

    protected $guarded = [];

    public function isReadOnly(): bool
    {
        return false;
    }

    public function details(): HasMany
    {
        return $this->hasMany(LegacyPrescriptionDetail::class, 'id_resep', 'id');
    }
}
