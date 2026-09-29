<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcedurePackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'name',
        'total_amount',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_amount' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(ProcedurePackageItem::class)->orderBy('sort_order');
    }
}
