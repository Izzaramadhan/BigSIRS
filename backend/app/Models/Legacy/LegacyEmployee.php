<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LegacyEmployee extends LegacyModel
{
    protected $table = 'ref_pegawai';

    protected $guarded = ['id'];

    public $timestamps = false;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function getDeletedAtColumnName(): ?string
    {
        return null; // ref_pegawai has DEFAULT CURRENT_TIMESTAMP on deleted_at
    }

    protected $appends = [
        'name',
        'code',
        'profession',
        'is_active',
        'national_id',
        'phone',
        'birth_place',
        'birth_date',
        'gender',
        'religion',
        'address',
        'education_id',
        'occupation_id',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(LegacyPerson::class, 'id_mpi');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(LegacyPosition::class, 'id_jabatan');
    }

    public function doctor(): HasOne
    {
        return $this->hasOne(LegacyDoctor::class, 'id_mpi', 'id_mpi');
    }

    public function getNameAttribute(): string
    {
        return $this->person ? $this->person->name : ($this->attributes['name'] ?? ($this->attributes['nama'] ?? 'Pegawai #' . $this->id));
    }

    public function getCodeAttribute(): string
    {
        return !empty($this->attributes['nip']) ? (string) $this->attributes['nip'] : 'EMP-' . $this->id;
    }

    public function getProfessionAttribute(): ?string
    {
        return $this->position ? $this->position->name : null;
    }

    public function getNationalIdAttribute(): ?string
    {
        return $this->person ? $this->person->national_id : null;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->person ? $this->person->phone : null;
    }

    public function getBirthPlaceAttribute(): ?string
    {
        return $this->person?->tempat_lahir;
    }

    public function getBirthDateAttribute()
    {
        return $this->person?->tgl_lahir;
    }

    public function getGenderAttribute(): ?string
    {
        return $this->person?->gender;
    }

    public function getReligionAttribute(): ?string
    {
        return $this->person?->agama;
    }

    public function getAddressAttribute(): ?string
    {
        return $this->person?->alamat;
    }

    public function getEducationIdAttribute(): ?int
    {
        return isset($this->person?->id_pendidikan) ? (int) $this->person->id_pendidikan : null;
    }

    public function getOccupationIdAttribute(): ?int
    {
        return isset($this->person?->id_pekerjaan) ? (int) $this->person->id_pekerjaan : null;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->where(function ($q) use ($term) {
            $q->where('nip', 'like', $term)
              ->orWhereHas('person', function ($sub) use ($term) {
                  $sub->where('nama', 'like', $term)
                      ->orWhere('no_ktp', 'like', $term);
              });
        });
    }
}
