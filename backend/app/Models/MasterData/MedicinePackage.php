<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicinePackage extends Model
{
    /** @use HasFactory<\Database\Factories\MasterData\MedicinePackageFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'name',
        'price',
        'quantity',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(MedicinePackageItem::class);
    }
}
