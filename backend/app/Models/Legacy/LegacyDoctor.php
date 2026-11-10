<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LegacyDoctor extends LegacyModel
{
    protected $table = 'ref_dokter';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = [
        'masa_berlaku' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function isReadOnly(): bool
    {
        return false;
    }

    public function getDeletedAtColumnName(): ?string
    {
        return 'deleted_at';
    }

    protected $appends = [
        'name',
        'full_name',
        'is_active',
        'str_number',
        'sip_number',
        'bpjs_dpjp_code',
        'employee_id',
        'specialization_id',
        'sip_valid_until',
        'signature_path',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(LegacyPerson::class, 'id_mpi');
    }

    public function employee(): HasOne
    {
        return $this->hasOne(LegacyEmployee::class, 'id_mpi', 'id_mpi');
    }

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(LegacySpecialization::class, 'id_spesialisasi');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(LegacyDoctorSchedule::class, 'id_dokter');
    }

    public function getNameAttribute(): string
    {
        if ($this->employee && $this->employee->person) {
            return $this->employee->person->name;
        }
        if ($this->person) {
            return $this->person->name;
        }
        return 'Dokter #' . $this->id;
    }

    public function getFullNameAttribute(): string
    {
        return $this->getNameAttribute();
    }

    public function getStrNumberAttribute(): ?string
    {
        return $this->attributes['no_str'] ?? null;
    }

    public function getSipNumberAttribute(): ?string
    {
        return $this->attributes['sip'] ?? null;
    }

    public function getBpjsDpjpCodeAttribute(): ?string
    {
        return $this->attributes['kode_dpjp'] ?? null;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function getEmployeeIdAttribute(): ?int
    {
        return $this->employee?->id;
    }

    public function getSpecializationIdAttribute(): ?int
    {
        return isset($this->attributes['id_spesialisasi']) ? (int) $this->attributes['id_spesialisasi'] : null;
    }

    public function getSipValidUntilAttribute()
    {
        return $this->masa_berlaku;
    }

    public function getSignaturePathAttribute(): ?string
    {
        return $this->attributes['ttd'] ?? null;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->where(function ($q) use ($term) {
            $q->where('no_str', 'like', $term)
              ->orWhere('sip', 'like', $term)
              ->orWhere('kode_dpjp', 'like', $term)
              ->orWhereHas('person', function ($sub) use ($term) {
                  $sub->where('nama', 'like', $term);
              });
        });
    }
}
