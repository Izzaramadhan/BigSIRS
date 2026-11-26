<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function queue(): HasOne
    {
        return $this->hasOne(LegacyQueue::class, 'id_visit', 'id');
    }

    public function vitalSign(): HasOne
    {
        return $this->hasOne(LegacyVitalSign::class, 'id_visit', 'id');
    }

    public function clinicalExamination(): HasOne
    {
        return $this->hasOne(LegacyClinicalExamination::class, 'id_visit', 'id');
    }

    public function nursingExamination(): HasOne
    {
        return $this->hasOne(LegacyNursingExamination::class, 'id_visit', 'id');
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(LegacyPatientDiagnosis::class, 'id_visit', 'id');
    }

    public function icd9Procedures(): HasMany
    {
        return $this->hasMany(LegacyPatientIcd9::class, 'id_visit', 'id');
    }

    public function procedures(): HasMany
    {
        return $this->hasMany(LegacyPatientProcedure::class, 'id_visit', 'id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(LegacyPrescription::class, 'id_visit', 'id');
    }

    public function labOrders(): HasMany
    {
        return $this->hasMany(LegacyLabOrder::class, 'id_visit', 'id');
    }

    public function radiologyOrders(): HasMany
    {
        return $this->hasMany(LegacyRadiologyOrder::class, 'id_visit', 'id');
    }
}
