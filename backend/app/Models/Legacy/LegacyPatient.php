<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyPatient extends LegacyModel
{
    protected $table = 'ref_pasien';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    public $timestamps = false;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(LegacyPerson::class, 'id_mpi', 'id');
    }

    public function guarantor(): BelongsTo
    {
        return $this->belongsTo(LegacyGuarantor::class, 'id_jenis_asuransi', 'id');
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(LegacyAdmission::class, 'id_pasien', 'id');
    }

    public function latestAdmission(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LegacyAdmission::class, 'id_pasien', 'id')->latestOfMany('id');
    }
}

