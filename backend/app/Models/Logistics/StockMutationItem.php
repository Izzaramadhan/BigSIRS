<?php

namespace App\Models\Logistics;

use App\Models\MasterData\Medicine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockMutationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_mutation_id',
        'medicine_id',
        'medicine_name',
        'quantity',
        'unit_price',
        'subtotal',
        'unit',
        'batch_number',
        'expired_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'expired_date' => 'date:Y-m-d',
    ];

    public function mutation(): BelongsTo
    {
        return $this->belongsTo(StockMutation::class, 'stock_mutation_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'stock_mutation_item_id');
    }
}
