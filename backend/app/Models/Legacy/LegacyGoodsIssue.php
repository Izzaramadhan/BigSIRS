<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyGoodsIssue extends LegacyModel
{
    protected $table = 'lgs_pengeluaran';

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
        'issue_number',
        'issueNumber',
        'reference_number',
        'referenceNumber',
        'issue_date',
        'issueDate',
        'transaction_number',
        'transactionNumber',
        'transaction_date',
        'transactionDate',
        'purpose',
        'purpose_code',
        'purposeCode',
        'officer_name',
        'officerName',
        'officer_id',
        'officerId',
        'customer_name',
        'customerName',
        'customer_phone',
        'customerPhone',
        'customer_type',
        'customerType',
        'warehouse_id',
        'warehouseId',
        'warehouse_name',
        'warehouseName',
        'payment_method',
        'paymentMethod',
        'status',
        'notes',
        'subtotal',
        'discount',
        'tax',
        'rounding',
        'total',
        'paid_amount',
        'paidAmount',
        'change_amount',
        'changeAmount',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(LegacyEmployee::class, 'id_pegawai');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(LegacyWarehouse::class, 'warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LegacyGoodsIssueItem::class, 'id_lgs_pengeluaran');
    }

    public function getIssueNumberAttribute(): string
    {
        if (! empty($this->attributes['kode_billing'])) {
            return (string) $this->attributes['kode_billing'];
        }
        $dateStr = ! empty($this->attributes['tanggal']) ? date('Ymd', strtotime($this->attributes['tanggal'])) : date('Ymd');
        return 'GI-' . $dateStr . '-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function getIssueNumberCamelAttribute(): string
    {
        return $this->getIssueNumberAttribute();
    }

    public function getReferenceNumberAttribute(): string
    {
        return $this->getIssueNumberAttribute();
    }

    public function getReferenceNumberCamelAttribute(): string
    {
        return $this->getReferenceNumberAttribute();
    }

    public function getIssueDateAttribute(): ?string
    {
        return $this->attributes['tanggal'] ?? null;
    }

    public function getIssueDateCamelAttribute(): ?string
    {
        return $this->getIssueDateAttribute();
    }

    public function getTransactionNumberAttribute(): string
    {
        if (! empty($this->attributes['kode_billing'])) {
            return (string) $this->attributes['kode_billing'];
        }
        $dateStr = ! empty($this->attributes['tanggal']) ? date('Ymd', strtotime($this->attributes['tanggal'])) : date('Ymd');
        return 'POS-' . $dateStr . '-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function getTransactionNumberCamelAttribute(): string
    {
        return $this->getTransactionNumberAttribute();
    }

    public function getTransactionDateAttribute(): ?string
    {
        return $this->attributes['tanggal'] ?? null;
    }

    public function getTransactionDateCamelAttribute(): ?string
    {
        return $this->getTransactionDateAttribute();
    }

    public function getPurposeAttribute(): ?string
    {
        return $this->attributes['keperluan'] ?? 'Pengeluaran Non-Penjualan';
    }

    public function getPurposeCodeAttribute(): string
    {
        $p = strtolower((string) ($this->attributes['keperluan'] ?? ''));
        if (str_contains($p, 'rusak')) return 'damaged';
        if (str_contains($p, 'kedaluwarsa') || str_contains($p, 'pemusnahan')) return 'expired';
        if (str_contains($p, 'sosial')) return 'social';
        if (str_contains($p, 'buffer')) return 'buffer';
        if (str_contains($p, 'penelitian') || str_contains($p, 'uji')) return 'research';
        if (str_contains($p, 'internal')) return 'internal';
        return 'other';
    }

    public function getPurposeCodeCamelAttribute(): string
    {
        return $this->getPurposeCodeAttribute();
    }

    public function getOfficerNameAttribute(): string
    {
        return $this->employee ? $this->employee->name : 'Petugas #' . ($this->attributes['id_pegawai'] ?? '');
    }

    public function getOfficerNameCamelAttribute(): string
    {
        return $this->getOfficerNameAttribute();
    }

    public function getOfficerIdAttribute(): ?int
    {
        return isset($this->attributes['id_pegawai']) ? (int) $this->attributes['id_pegawai'] : null;
    }

    public function getOfficerIdCamelAttribute(): ?int
    {
        return $this->getOfficerIdAttribute();
    }

    public function getCustomerNameAttribute(): string
    {
        return ! empty($this->attributes['pembeli']) ? (string) $this->attributes['pembeli'] : 'Pasien Umum';
    }

    public function getCustomerNameCamelAttribute(): string
    {
        return $this->getCustomerNameAttribute();
    }

    public function getCustomerPhoneAttribute(): string
    {
        return '-';
    }

    public function getCustomerPhoneCamelAttribute(): string
    {
        return '-';
    }

    public function getCustomerTypeAttribute(): string
    {
        return 'Umum';
    }

    public function getCustomerTypeCamelAttribute(): string
    {
        return 'Umum';
    }

    public function getWarehouseIdAttribute(): int
    {
        $whId = $this->relationLoaded('items')
            ? $this->items->first()?->stockLog?->id_gudang
            : null;
        return (int) ($whId ?? 1);
    }

    public function getWarehouseIdCamelAttribute(): int
    {
        return $this->getWarehouseIdAttribute();
    }

    public function getWarehouseNameAttribute(): string
    {
        $whName = $this->relationLoaded('items')
            ? $this->items->first()?->stockLog?->warehouse?->name
            : null;
        return $whName ?? 'Gudang Utama';
    }

    public function getWarehouseNameCamelAttribute(): string
    {
        return $this->getWarehouseNameAttribute();
    }

    public function getPaymentMethodAttribute(): string
    {
        return strtolower((string) ($this->attributes['cara_bayar'] ?? '')) === 'non tunai' ? 'Non Tunai' : 'Tunai';
    }

    public function getPaymentMethodCamelAttribute(): string
    {
        return $this->getPaymentMethodAttribute();
    }

    public function getStatusAttribute(): string
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1' ? 'completed' : 'cancelled';
    }

    public function getNotesAttribute(): ?string
    {
        return $this->attributes['keperluan'] ?? null;
    }

    public function getSubtotalAttribute(): float
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum('subtotal');
        }
        return 0.00;
    }

    public function getDiscountAttribute(): float
    {
        return 0.00;
    }

    public function getTaxAttribute(): float
    {
        return 0.00;
    }

    public function getRoundingAttribute(): float
    {
        return 0.00;
    }

    public function getTotalAttribute(): float
    {
        return $this->getSubtotalAttribute();
    }

    public function getPaidAmountAttribute(): float
    {
        return $this->getStatusAttribute() === 'completed' ? $this->getTotalAttribute() : 0.00;
    }

    public function getPaidAmountCamelAttribute(): float
    {
        return $this->getPaidAmountAttribute();
    }

    public function getChangeAmountAttribute(): float
    {
        return 0.00;
    }

    public function getChangeAmountCamelAttribute(): float
    {
        return 0.00;
    }

    public function scopePengeluaran(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('flag', 'pengeluaran')
              ->orWhereNull('flag');
        });
    }

    public function scopePosSale(Builder $query): Builder
    {
        return $query->where('flag', 'apotek_bebas');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->where(function ($q) use ($term) {
            $q->where('keperluan', 'like', $term)
              ->orWhere('pembeli', 'like', $term)
              ->orWhere('kode_billing', 'like', $term)
              ->orWhereHas('employee.person', function ($sub) use ($term) {
                  $sub->where('nama', 'like', $term);
              });
        });
    }
}
