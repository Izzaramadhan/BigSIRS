<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyPatientDiagnosis extends LegacyModel
{
    protected $table = 'trx_diagnosa';

    protected $primaryKey = 'id';

    protected $guarded = [];

    public function isReadOnly(): bool
    {
        return false;
    }

    public function diagnosa(): BelongsTo
    {
        return $this->belongsTo(LegacyIcd10::class, 'id_diagnosa', 'id');
    }
}
