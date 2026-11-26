<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyPatientProcedure extends LegacyModel
{
    protected $table = 'trx_tindakan';

    protected $primaryKey = 'id';

    protected $guarded = [];

    public function isReadOnly(): bool
    {
        return false;
    }

    public function tindakan(): BelongsTo
    {
        return $this->belongsTo(LegacyMedicalProcedure::class, 'id_tindakan', 'id');
    }
}
