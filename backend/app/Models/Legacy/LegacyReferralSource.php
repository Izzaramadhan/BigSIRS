<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;

class LegacyReferralSource extends LegacyModel
{
    protected $table = 'ref_asal_rujukan';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = ['id'];

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
        'code',
        'is_active',
    ];

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function getCodeAttribute(): ?string
    {
        return $this->attributes['kode'] ?? null;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', 1)->orWhere('status', '1');
        })->where(function ($q) {
            $q->whereNull('deleted_at')
                ->orWhere('deleted_at', '0000-00-00 00:00:00')
                ->orWhere('deleted_at', '0000-00-00');
        });
    }
}
