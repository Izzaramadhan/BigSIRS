<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;

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
}
