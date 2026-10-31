<?php

namespace App\Models\Logistics;

use App\Models\MasterData\Medicine;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'medicine_id',
        'warehouse_stock_id',
        'stock_mutation_id',
        'stock_mutation_item_id',
        'stock_opname_id',
        'stock_opname_item_id',
        'goods_issue_id',
        'goods_issue_item_id',
        'pos_sale_id',
        'pos_sale_item_id',
        'movement_type',
        'direction',
        'quantity',
        'balance_before',
        'balance_after',
        'batch_number',
        'expired_date',
        'occurred_at',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'expired_date' => 'date:Y-m-d',
        'occurred_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function warehouseStock(): BelongsTo
    {
        return $this->belongsTo(WarehouseStock::class, 'warehouse_stock_id');
    }

    public function mutation(): BelongsTo
    {
        return $this->belongsTo(StockMutation::class, 'stock_mutation_id');
    }

    public function mutationItem(): BelongsTo
    {
        return $this->belongsTo(StockMutationItem::class, 'stock_mutation_item_id');
    }

    public function opname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    public function opnameItem(): BelongsTo
    {
        return $this->belongsTo(StockOpnameItem::class, 'stock_opname_item_id');
    }

    public function goodsIssue(): BelongsTo
    {
        return $this->belongsTo(GoodsIssue::class, 'goods_issue_id');
    }

    public function goodsIssueItem(): BelongsTo
    {
        return $this->belongsTo(GoodsIssueItem::class, 'goods_issue_item_id');
    }

    public function posSale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function posSaleItem(): BelongsTo
    {
        return $this->belongsTo(PosSaleItem::class, 'pos_sale_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
