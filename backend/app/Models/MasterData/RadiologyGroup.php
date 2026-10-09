<?php

namespace App\Models\MasterData;

use App\Models\ActivityType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RadiologyGroup extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'radiology_category_id',
        'radiology_type_id',
        'activity_type_id',
        'name',
        'price',
        'interpretation_price',
        'loinc_code',
        'loinc_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'interpretation_price' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(RadiologyCategory::class, 'radiology_category_id');
    }

    public function type()
    {
        return $this->belongsTo(RadiologyType::class, 'radiology_type_id');
    }

    public function activityType()
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id');
    }

    public function itemGroups()
    {
        return $this->belongsToMany(
            RadiologyItemGroup::class,
            'radiology_group_item_groups',
            'radiology_group_id',
            'radiology_item_group_id'
        )->withTimestamps();
    }
}
