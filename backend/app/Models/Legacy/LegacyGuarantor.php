<?php

namespace App\Models\Legacy;

use App\Enums\GuarantorType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegacyGuarantor extends LegacyModel
{
    use SoftDeletes;

    protected $table = 'ref_jenis_asuransi';

    protected $guarded = ['id'];

    protected $appends = ['name', 'code', 'is_active', 'is_government', 'inacbg_id', 'type', 'legacy_id'];

    public function isReadOnly(): bool
    {
        return false;
    }

    public function getDeletedAtColumnName(): ?string
    {
        return 'deleted_at';
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (LegacyGuarantor $model) {
            if (empty($model->created_at)) {
                $model->created_at = now()->format('Y-m-d H:i:s');
            }
            if (empty($model->id_user)) {
                $model->id_user = auth()->id() ?? 1;
            }
            if (! isset($model->attributes['status'])) {
                $model->attributes['status'] = 1;
            }
            if (! isset($model->attributes['pemerintah'])) {
                $model->attributes['pemerintah'] = 0;
            }
        });

        static::updating(function (LegacyGuarantor $model) {
            $model->updated_at = now()->format('Y-m-d H:i:s');
            if (auth()->check()) {
                $model->id_user = auth()->id();
            }
        });
    }

    public function getLegacyIdAttribute(): ?int
    {
        return isset($this->attributes['id']) ? (int) $this->attributes['id'] : null;
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['jenis_asuransi'] ?? '');
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['jenis_asuransi'] = $value !== null ? trim($value) : null;
    }

    public function getCodeAttribute(): ?string
    {
        return $this->attributes['kode'] ?? null;
    }

    public function setCodeAttribute($value): void
    {
        $this->attributes['kode'] = $value !== null ? strtoupper(trim($value)) : null;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['status'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    public function getIsGovernmentAttribute(): bool
    {
        return (bool) ($this->attributes['pemerintah'] ?? false);
    }

    public function setIsGovernmentAttribute($value): void
    {
        $this->attributes['pemerintah'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    public function getInacbgIdAttribute(): ?string
    {
        return $this->attributes['id_inacbg'] ?? null;
    }

    public function setInacbgIdAttribute($value): void
    {
        $this->attributes['id_inacbg'] = $value !== null ? trim($value) : null;
    }

    public function getTypeAttribute(): ?GuarantorType
    {
        $raw = strtoupper((string) ($this->attributes['type'] ?? ''));
        return match ($raw) {
            'UMUM', 'SELF_PAY' => GuarantorType::SelfPay,
            'BPJS' => GuarantorType::Bpjs,
            'PRIVATE', 'PRIVATE_INSURANCE' => GuarantorType::PrivateInsurance,
            'GOVERNMENT' => GuarantorType::Government,
            default => GuarantorType::tryFrom(strtolower($raw)) ?? GuarantorType::Other,
        };
    }

    public function setTypeAttribute($value): void
    {
        if ($value instanceof GuarantorType) {
            $value = $value->value;
        }

        $val = strtolower((string) $value);
        $this->attributes['type'] = match ($val) {
            'bpjs' => 'BPJS',
            'private_insurance', 'private' => 'PRIVATE',
            default => 'UMUM',
        };
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->where(function ($q) use ($term) {
            $q->where('jenis_asuransi', 'like', $term)
              ->orWhere('kode', 'like', $term);
        });
    }
}
