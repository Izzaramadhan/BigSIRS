<?php

namespace App\Models\MasterData;

use Database\Factories\MasterData\BedFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Bed extends Model
{
    /** @use HasFactory<BedFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'beds';

    protected $fillable = [
        'room_id',
        'legacy_id',
        'name',
        'description',
    ];

    protected $casts = [
        'room_id' => 'integer',
        'legacy_id' => 'integer',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = "%{$term}%";

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', $term)
                ->orWhere('description', 'like', $term)
                ->orWhereHas('room', function (Builder $rq) use ($term) {
                    $rq->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhereHas('roomClass', function (Builder $rc) use ($term) {
                            $rc->where('name', 'like', $term)
                                ->orWhere('code', 'like', $term);
                        })
                        ->orWhereHas('ward', function (Builder $w) use ($term) {
                            $w->where('name', 'like', $term)
                                ->orWhereHas('floor', function (Builder $f) use ($term) {
                                    $f->where('name', 'like', $term)
                                        ->orWhereHas('building', function (Builder $b) use ($term) {
                                            $b->where('name', 'like', $term);
                                        });
                                });
                        });
                });
        });
    }

    public function isUsedInTransactions(): bool
    {
        if (method_exists($this, 'admissions') && $this->admissions()->exists()) {
            return true;
        }

        if (method_exists($this, 'inpatientAdmissions') && $this->inpatientAdmissions()->exists()) {
            return true;
        }

        if (method_exists($this, 'transactions') && $this->transactions()->exists()) {
            return true;
        }

        try {
            if ($this->legacy_id && config('database.connections.legacy')) {
                if (DB::connection('legacy')->table('trx_bed')->where('id_bed', $this->legacy_id)->exists()) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore if legacy connection or table is unavailable
        }

        return false;
    }

    protected static function newFactory()
    {
        return BedFactory::new();
    }
}
