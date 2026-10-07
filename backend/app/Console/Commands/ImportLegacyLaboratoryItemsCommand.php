<?php

namespace App\Console\Commands;

use App\Services\MasterData\LaboratoryItemImporter;
use Illuminate\Console\Command;

class ImportLegacyLaboratoryItemsCommand extends Command
{
    protected $signature = 'import:legacy-laboratory-items {--dry-run : Calculate changes without writing target data}';

    protected $description = 'Import Item Lab from legacy ref_item_lab without category or group duplication';

    public function handle(LaboratoryItemImporter $importer): int
    {
        $stats = $importer->import((bool) $this->option('dry-run'));

        $this->table(
            ['Metric', 'Count'],
            collect($stats)->map(fn ($count, $metric) => [$metric, $count])->values()->all()
        );

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
