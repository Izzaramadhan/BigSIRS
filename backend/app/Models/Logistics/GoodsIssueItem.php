<?php

namespace App\Models\Logistics;

use App\Models\MasterData\Medicine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsIssueItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_issue_id',
        'warehouse_stock_id',
        'medicine_id',
        'medicine_name',
        'batch_number',
        'expired_date',
        'quantity',
        'unit',
        'unit_price',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'expired_date' => 'date:Y-m-d',
    ];

    public function goodsIssue(): BelongsTo
    {
        return $this->belongsTo(GoodsIssue::class, 'goods_issue_id');
    }

    public function warehouseStock(): BelongsTo
    {
        return $this->belongsTo(WarehouseStock::class, 'warehouse_stock_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }
}

