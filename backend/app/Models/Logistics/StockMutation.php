<?php

namespace App\Models\Logistics;

use App\Models\MasterData\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockMutation extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id',
        'mutation_number',
        'mutation_date',
        'source_warehouse_id',
        'target_warehouse_id',
        'notes',
        'total_value',
        'status',
        'created_by',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'mutation_date' => 'datetime',
        'validated_at' => 'datetime',
        'total_value' => 'decimal:2',
    ];

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function targetWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockMutationItem::class, 'stock_mutation_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'stock_mutation_id');
    }
}
