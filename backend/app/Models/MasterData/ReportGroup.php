<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];
}
