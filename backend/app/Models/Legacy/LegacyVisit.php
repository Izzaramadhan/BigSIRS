<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyVisit extends LegacyModel
{
    protected $table = 'trx_visit';

    protected $primaryKey = 'id';

    protected $guarded = [];

    public function isReadOnly(): bool
    {
        return false;
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(LegacyAdmission::class, 'id_admisi', 'id');
    }

    public function polyclinic(): BelongsTo
    {
        return $this->belongsTo(LegacyPolyclinic::class, 'id_poliklinik', 'id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(LegacyDoctor::class, 'id_dokter', 'id');
    }
}
