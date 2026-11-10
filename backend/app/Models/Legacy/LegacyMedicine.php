<?php

namespace App\Models\Legacy;

use App\Models\MasterData\Medicine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class LegacyMedicine extends LegacyModel
{
    protected $table = 'ref_obat';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * Medicine is enabled for controlled direct CRUD.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    protected $appends = [
        'legacy_id',
        'code',
        'name',
        'description',
        'function',
        'kfa_code',
        'bpjs_code',
        'selling_price_per_unit',
        'price',
        'unit_price',
        'purchase_price',
        'medicine_unit_id',
        'medicine_category_id',
        'medicine_route_id',
        'generic_medicine_id',
        'medicine_classification_id',
        'is_active',
        'type',
        'generic_type',
        'fornas_type',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (LegacyMedicine $model) {
            if (empty($model->created_at)) {
                $model->created_at = now()->format('Y-m-d H:i:s');
            }
            if ($model->status === null) {
                $model->status = '1';
            }
            if ($model->is_bpjs === null) {
                $model->is_bpjs = -1;
            }
        });

        static::updating(function (LegacyMedicine $model) {
            $model->updated_at = now()->format('Y-m-d H:i:s');
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LegacyMedicineCategory::class, 'id_kategori_obat');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(LegacyMedicineUnit::class, 'id_satuan_obat');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(LegacyMedicineRoute::class, 'id_obat_jalur_masuk');
    }

    /**
     * Check if medicine is referenced in transactions or dependent tables.
     */
    public function isReferencedInTransactions(): bool
    {
        $id = $this->id;
        if (! $id) {
            return false;
        }

        try {
            $conn = DB::connection('legacy');

            // 1. Check faktur detail (lgs_faktur_detail)
            if ($conn->getSchemaBuilder()->hasTable('lgs_faktur_detail')) {
                if ($conn->table('lgs_faktur_detail')->where('id_obat', $id)->exists()) {
                    return true;
                }
            }

            // 2. Check medicine package map (map_paket_obat)
            if ($conn->getSchemaBuilder()->hasTable('map_paket_obat')) {
                if ($conn->table('map_paket_obat')->where('id_ref_obat', $id)->exists()) {
                    return true;
                }
            }

            // 3. Check incoming medicine transactions (trx_obat_masuk_detail)
            if ($conn->getSchemaBuilder()->hasTable('trx_obat_masuk_detail')) {
                if ($conn->table('trx_obat_masuk_detail')->where('id_obat', $id)->exists()) {
                    return true;
                }
            }

            // 4. Check lab antibiotic details (trx_detail_lab_antibiotik)
            if ($conn->getSchemaBuilder()->hasTable('trx_detail_lab_antibiotik')) {
                if ($conn->table('trx_detail_lab_antibiotik')->where('id_obat', $id)->exists()) {
                    return true;
                }
            }

            // 5. Check prescription surveillance (trx_resep_surveilans)
            if ($conn->getSchemaBuilder()->hasTable('trx_resep_surveilans')) {
                if ($conn->table('trx_resep_surveilans')->where('id_obat', $id)->exists()) {
                    return true;
                }
            }
        } catch (\Throwable) {
            // fallback
        }

        // 6. Check modern bigsirs_dev Medicine and relations if present
        try {
            if (class_exists(Medicine::class)) {
                $med = Medicine::where('id', $id)->orWhere('legacy_id', $id)->first();
                if ($med) {
                    if ($med->warehouseStocks()->exists() ||
                        $med->mutationItems()->exists() ||
                        $med->stockMovements()->exists() ||
                        $med->stockOpnameItems()->exists()) {
                        return true;
                    }
                }
            }
        } catch (\Throwable) {
            // ignore
        }

        return false;
    }

    /**
     * Perform safe legacy soft delete.
     */
    public function performSoftDelete(?int $userId = null): bool
    {
        $this->deleted_at = now()->format('Y-m-d H:i:s');
        $this->status = '0';
        $this->updated_at = now()->format('Y-m-d H:i:s');
        if ($userId) {
            $this->id_user = $userId;
        }

        return $this->save();
    }

    // Accessors & Mutators
    public function getLegacyIdAttribute(): ?int
    {
        return isset($this->attributes['id']) ? (int) $this->attributes['id'] : null;
    }

    public function getCodeAttribute(): ?string
    {
        $code = $this->attributes['kode'] ?? null;
        if ($code !== null && $code !== '') {
            return (string) $code;
        }

        return isset($this->attributes['id']) ? 'MED-' . $this->attributes['id'] : null;
    }

    public function setCodeAttribute($value): void
    {
        $this->attributes['kode'] = $value !== null ? trim((string) $value) : null;
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['nama'] = $value !== null ? trim((string) $value) : '';
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['deskripsi'] ?? null;
    }

    public function setDescriptionAttribute($value): void
    {
        $this->attributes['deskripsi'] = $value !== null ? trim((string) $value) : null;
    }

    public function getFunctionAttribute(): ?string
    {
        return $this->attributes['fungsi'] ?? null;
    }

    public function setFunctionAttribute($value): void
    {
        $this->attributes['fungsi'] = $value !== null ? trim((string) $value) : null;
    }

    public function getKfaCodeAttribute(): ?string
    {
        return $this->attributes['kode_kfa'] ?? null;
    }

    public function setKfaCodeAttribute($value): void
    {
        $this->attributes['kode_kfa'] = $value !== null ? trim((string) $value) : null;
    }

    public function getBpjsCodeAttribute(): ?string
    {
        return $this->attributes['kode_bpjs'] ?? null;
    }

    public function setBpjsCodeAttribute($value): void
    {
        $this->attributes['kode_bpjs'] = $value !== null ? trim((string) $value) : null;
    }

    public function getSellingPricePerUnitAttribute(): ?float
    {
        return isset($this->attributes['harga_jual']) ? (float) $this->attributes['harga_jual'] : null;
    }

    public function setSellingPricePerUnitAttribute($value): void
    {
        $this->attributes['harga_jual'] = $value !== null && $value !== '' ? round((float) $value) : null;
    }

    public function getPriceAttribute(): ?float
    {
        return $this->getSellingPricePerUnitAttribute();
    }

    public function setPriceAttribute($value): void
    {
        $this->setSellingPricePerUnitAttribute($value);
    }

    public function getUnitPriceAttribute(): ?float
    {
        return $this->getSellingPricePerUnitAttribute();
    }

    public function setUnitPriceAttribute($value): void
    {
        $this->setSellingPricePerUnitAttribute($value);
    }

    public function getPurchasePriceAttribute(): ?float
    {
        return isset($this->attributes['harga_beli']) ? (float) $this->attributes['harga_beli'] : null;
    }

    public function setPurchasePriceAttribute($value): void
    {
        $this->attributes['harga_beli'] = $value !== null && $value !== '' ? round((float) $value) : null;
    }

    public function getMedicineUnitIdAttribute(): ?int
    {
        return isset($this->attributes['id_satuan_obat']) ? (int) $this->attributes['id_satuan_obat'] : null;
    }

    public function setMedicineUnitIdAttribute($value): void
    {
        $this->attributes['id_satuan_obat'] = $value !== null && $value !== '' ? (int) $value : null;
    }

    public function getMedicineCategoryIdAttribute(): ?int
    {
        return isset($this->attributes['id_kategori_obat']) ? (int) $this->attributes['id_kategori_obat'] : null;
    }

    public function setMedicineCategoryIdAttribute($value): void
    {
        $this->attributes['id_kategori_obat'] = $value !== null && $value !== '' ? (int) $value : null;
    }

    public function getMedicineRouteIdAttribute(): ?int
    {
        return isset($this->attributes['id_obat_jalur_masuk']) ? (int) $this->attributes['id_obat_jalur_masuk'] : null;
    }

    public function setMedicineRouteIdAttribute($value): void
    {
        $this->attributes['id_obat_jalur_masuk'] = $value !== null && $value !== '' ? (int) $value : null;
    }

    public function getGenericMedicineIdAttribute(): ?int
    {
        return isset($this->attributes['id_generik']) ? (int) $this->attributes['id_generik'] : null;
    }

    public function setGenericMedicineIdAttribute($value): void
    {
        $this->attributes['id_generik'] = $value !== null && $value !== '' ? (int) $value : null;
    }

    public function getMedicineClassificationIdAttribute(): ?int
    {
        return isset($this->attributes['id_golongan']) ? (int) $this->attributes['id_golongan'] : null;
    }

    public function setMedicineClassificationIdAttribute($value): void
    {
        $this->attributes['id_golongan'] = $value !== null && $value !== '' ? (int) $value : null;
    }

    public function getTypeAttribute(): string
    {
        return (string) ($this->attributes['tipe'] ?? 'obat');
    }

    public function setTypeAttribute($value): void
    {
        $this->attributes['tipe'] = $value !== null ? (string) $value : 'obat';
    }

    public function getGenericTypeAttribute(): ?string
    {
        return $this->attributes['tipe_gen'] ?? null;
    }

    public function setGenericTypeAttribute($value): void
    {
        $this->attributes['tipe_gen'] = $value !== null ? (string) $value : null;
    }

    public function getFornasTypeAttribute(): ?string
    {
        return $this->attributes['tipe_for'] ?? null;
    }

    public function setFornasTypeAttribute($value): void
    {
        $this->attributes['tipe_for'] = $value !== null ? (string) $value : null;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['status'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->where(function ($q) use ($term) {
            $q->where('kode', 'like', $term)
              ->orWhere('nama', 'like', $term)
              ->orWhere('kode_kfa', 'like', $term)
              ->orWhere('kode_bpjs', 'like', $term)
              ->orWhere('fungsi', 'like', $term);
        });
    }
}
