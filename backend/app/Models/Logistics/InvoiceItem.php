<?php

namespace App\Models\Logistics;

use App\Models\MasterData\Medicine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'invoice_id',
        'medicine_id',
        'medicine_name',
        'quantity',
        'unit',
        'price',
        'discount_type',
        'discount',
        'subtotal',
        'is_repack',
        'margin',
        'batch_number',
        'expired_date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'is_repack' => 'boolean',
        'margin' => 'decimal:4',
        'expired_date' => 'date:Y-m-d',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }
}
