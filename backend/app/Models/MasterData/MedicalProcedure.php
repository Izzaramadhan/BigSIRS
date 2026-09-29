<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\ProcedureCategory;
use App\Models\Polyclinic;

class MedicalProcedure extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_visible' => 'boolean',
        'needs_review' => 'boolean',
    ];

    public function procedureCategory(): BelongsTo
    {
        return $this->belongsTo(ProcedureCategory::class);
    }

    public function icd9Cm(): BelongsTo
    {
        return $this->belongsTo(Icd9Cm::class);
    }

    public function tariffs(): HasMany
    {
        return $this->hasMany(MedicalProcedureTariff::class);
    }

    public function polyclinics(): BelongsToMany
    {
        return $this->belongsToMany(Polyclinic::class, 'medical_procedure_polyclinic');
    }

    public function reportGroups(): BelongsToMany
    {
        return $this->belongsToMany(ReportGroup::class, 'medical_procedure_report_group');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Employee::class, 'procedure_employee', 'procedure_id', 'employee_id')
                    ->withTimestamps();
    }
}
