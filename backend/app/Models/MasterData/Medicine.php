<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicine extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'code',
        'name',
        'kfa_code',
        'function',
        'medicine_unit_id',
        'medicine_category_id',
        'medicine_classification_id',
        'medicine_route_id',
        'generic_medicine_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function unit()
    {
        return $this->belongsTo(MedicineUnit::class, 'medicine_unit_id');
    }

    public function category()
    {
        return $this->belongsTo(MedicineCategory::class, 'medicine_category_id');
    }

    public function classification()
    {
        return $this->belongsTo(MedicineClassification::class, 'medicine_classification_id');
    }

    public function route()
    {
        return $this->belongsTo(MedicineRoute::class, 'medicine_route_id');
    }

    public function generic()
    {
        return $this->belongsTo(GenericMedicine::class, 'generic_medicine_id');
    }
}
