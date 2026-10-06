<?php

namespace App\Models\MasterData;

use App\Models\Polyclinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DoctorSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'doctor_id',
        'polyclinic_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_holiday',
        'online_quota',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_holiday' => 'boolean',
        'online_quota' => 'integer',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function polyclinic(): BelongsTo
    {
        return $this->belongsTo(Polyclinic::class);
    }
}
