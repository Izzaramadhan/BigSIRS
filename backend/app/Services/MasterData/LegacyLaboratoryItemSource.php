<?php

namespace App\Services\MasterData;

use Closure;
use Illuminate\Support\Facades\DB;

class LegacyLaboratoryItemSource
{
    public function chunk(Closure $callback): void
    {
        DB::connection('legacy')
            ->table('ref_item_lab')
            ->orderBy('id')
            ->chunk(100, $callback);
    }
}
