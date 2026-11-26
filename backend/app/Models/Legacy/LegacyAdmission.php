<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyAdmission extends LegacyModel
{
    protected $table = 'trx_admisi';

    protected $primaryKey = 'id';

    protected $guarded = [];

    public function isReadOnly(): bool
    {
        return false;
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(LegacyPatient::class, 'id_pasien', 'id');
    }

    public function guarantor(): BelongsTo
    {
        return $this->belongsTo(LegacyGuarantor::class, 'id_jenis_asuransi', 'id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(LegacyVisit::class, 'id_admisi', 'id');
    }

    public function latestVisit(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LegacyVisit::class, 'id_admisi', 'id')->latestOfMany('id');
    }

    public function queue(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LegacyQueue::class, 'id_admisi', 'id');
    }
}
