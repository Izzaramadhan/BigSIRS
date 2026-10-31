<?php

namespace App\Models\MasterData;

use App\Models\Logistics\StockMovement;
use App\Models\Logistics\StockMutation;
use App\Models\Logistics\StockOpname;
use App\Models\Logistics\WarehouseStock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'code',
        'name',
        'description',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class, 'warehouse_id');
    }

    public function outgoingMutations(): HasMany
    {
        return $this->hasMany(StockMutation::class, 'source_warehouse_id');
    }

    public function incomingMutations(): HasMany
    {
        return $this->hasMany(StockMutation::class, 'target_warehouse_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'warehouse_id');
    }

    public function stockOpnames(): HasMany
    {
        return $this->hasMany(StockOpname::class, 'warehouse_id');
    }
}
