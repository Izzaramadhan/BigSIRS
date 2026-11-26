<?php

namespace App\Models\Legacy;

class LegacyPerson extends LegacyModel
{
    protected $table = 'master_person_index';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = [
        'tgl_lahir' => 'date',
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

    protected $appends = ['name', 'national_id', 'phone'];

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function getNationalIdAttribute(): ?string
    {
        return isset($this->attributes['no_ktp']) ? (string) $this->attributes['no_ktp'] : null;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->attributes['no_hp'] ?? null;
    }

    public function getIhsNumberAttribute(): ?string
    {
        return $this->attributes['ihs_id'] ?? null;
    }

    public function getBirthPlaceAttribute(): ?string
    {
        return $this->attributes['tempat_lahir'] ?? null;
    }

    public function getBirthDateAttribute()
    {
        return $this->tgl_lahir;
    }

    public function getNationalityAttribute(): ?string
    {
        return $this->attributes['kebangsaan'] ?? null;
    }

    public function getBloodTypeAttribute(): ?string
    {
        $val = $this->attributes['gol_darah'] ?? null;
        if ($val === '-') {
            return 'Unknown';
        }

        return $val ?: null;
    }

    public function getAllergiesAttribute(): ?string
    {
        return $this->attributes['alergi_lainnya'] ?? null;
    }

    public function getReligionAttribute(): ?string
    {
        return $this->attributes['agama'] ?? null;
    }

    public function getMaritalStatusAttribute(): ?string
    {
        return $this->attributes['status_perkawinan'] ?? null;
    }

    public function getAddressAttribute(): ?string
    {
        return $this->attributes['alamat'] ?? null;
    }

    public function getPostalCodeAttribute(): ?string
    {
        return $this->attributes['kode_pos'] ?? null;
    }

    public function getProvinceIdAttribute(): ?string
    {
        $val = $this->attributes['id_provinsi'] ?? null;

        return ($val !== null && $val !== '' && $val !== '00') ? (string) $val : null;
    }

    public function getRegencyIdAttribute(): ?string
    {
        $val = $this->attributes['id_kabupaten'] ?? null;

        return ($val !== null && $val !== '' && $val !== '00') ? (string) $val : null;
    }

    public function getDistrictIdAttribute(): ?string
    {
        $val = $this->attributes['id_kecamatan'] ?? null;

        return ($val !== null && $val !== '' && $val !== '00') ? (string) $val : null;
    }

    public function getVillageIdAttribute(): ?string
    {
        $val = $this->attributes['id_kelurahan'] ?? null;

        return ($val !== null && $val !== '' && $val !== '00') ? (string) $val : null;
    }

    public function getEducationIdAttribute(): ?int
    {
        return isset($this->attributes['id_pendidikan']) ? (int) $this->attributes['id_pendidikan'] : null;
    }

    public function getOccupationIdAttribute(): ?int
    {
        return isset($this->attributes['id_pekerjaan']) ? (int) $this->attributes['id_pekerjaan'] : null;
    }
}
