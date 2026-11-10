<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyStockOpnameDetail extends LegacyModel
{
    protected $table = 'lgs_stok_opname_detail';

    protected $guarded = ['id'];

    public $timestamps = false;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function getDeletedAtColumnName(): ?string
    {
        return 'deleted_at';
    }

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(LegacyStockOpname::class, 'id_lgs_stok_opname');
    }

    public function stockLog(): BelongsTo
    {
        return $this->belongsTo(LegacyStockLog::class, 'id_lgs_log_stok');
    }
}

