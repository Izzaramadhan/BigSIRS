<?php

namespace App\Models\Logistics;

use App\Models\Employee;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id',
        'issue_number',
        'issue_date',
        'warehouse_id',
        'location_detail',
        'officer_name',
        'officer_id',
        'officer_role',
        'purpose',
        'purpose_code',
        'reference_number',
        'notes',
        'status',
        'total_quantity',
        'total_value',
        'created_by',
        'finalized_by',
        'finalized_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'finalized_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'total_quantity' => 'decimal:2',
        'total_value' => 'decimal:2',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'officer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsIssueItem::class, 'goods_issue_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'goods_issue_id');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
