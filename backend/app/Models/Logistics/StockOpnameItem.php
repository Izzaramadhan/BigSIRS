<?php

namespace App\Models\Logistics;

use App\Models\MasterData\Medicine;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpnameItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_opname_id',
        'warehouse_stock_id',
        'medicine_id',
        'medicine_name',
        'medicine_code',
        'unit',
        'batch_number',
        'expired_date',
        'system_stock',
        'physical_stock',
        'difference',
        'unit_price',
        'difference_value',
        'notes',
        'counted_by',
        'counted_at',
    ];

    protected $casts = [
        'expired_date' => 'date:Y-m-d',
        'counted_at' => 'datetime',
        'system_stock' => 'decimal:2',
        'physical_stock' => 'decimal:2',
        'difference' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'difference_value' => 'decimal:2',
    ];

    public function opname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    public function warehouseStock(): BelongsTo
    {
        return $this->belongsTo(WarehouseStock::class, 'warehouse_stock_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'stock_opname_item_id');
    }
}
