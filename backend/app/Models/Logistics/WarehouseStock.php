<?php

namespace App\Models\Logistics;

use App\Models\MasterData\Medicine;
use App\Models\MasterData\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id',
        'warehouse_id',
        'medicine_id',
        'quantity',
        'selling_price',
        'batch_number',
        'expired_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'expired_date' => 'date:Y-m-d',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'warehouse_stock_id');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('quantity', '>', 0);
    }
}
