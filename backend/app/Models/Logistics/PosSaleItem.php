<?php

namespace App\Models\Logistics;

use App\Models\MasterData\Medicine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_sale_id',
        'warehouse_stock_id',
        'medicine_id',
        'medicine_name',
        'batch_number',
        'expired_date',
        'unit',
        'quantity',
        'price',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'expired_date' => 'date:Y-m-d',
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function posSale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function warehouseStock(): BelongsTo
    {
        return $this->belongsTo(WarehouseStock::class, 'warehouse_stock_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'pos_sale_item_id');
    }
}

