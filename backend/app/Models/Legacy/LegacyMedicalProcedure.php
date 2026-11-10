<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyMedicalProcedure extends LegacyModel
{
    protected $table = 'ref_tarif_tindakan';

    protected $guarded = ['id'];

    public $timestamps = false;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function getDeletedAtColumnName(): ?string
    {
        return 'deleted_at';
    }

    protected $appends = [
        'code',
        'name',
        'is_visible',
        'needs_review',
        'procedure_category_id',
    ];

    public function procedureCategory(): BelongsTo
    {
        return $this->belongsTo(LegacyProcedureCategory::class, 'id_kategori');
    }

    public function tariffType(): BelongsTo
    {
        return $this->belongsTo(LegacyTariffType::class, 'id_jenis_tarif');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(LegacyProcedureUserMapping::class, 'id_ref_tindakan');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(LegacyEmployee::class, 'map_ref_tindakan_user', 'id_ref_tindakan', 'id_aktor');
    }

    public function getCodeAttribute(): string
    {
        return (string) ($this->attributes['kode'] ?? ('PROC-' . $this->id));
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function getIsVisibleAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function getNeedsReviewAttribute(): bool
    {
        return false;
    }

    public function getProcedureCategoryIdAttribute(): ?int
    {
        return isset($this->attributes['id_kategori']) ? (int) $this->attributes['id_kategori'] : null;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->where(function ($q) use ($term) {
            $q->where('kode', 'like', $term)
              ->orWhere('nama', 'like', $term);
        });
    }
}
