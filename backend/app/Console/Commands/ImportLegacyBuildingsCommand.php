<?php

namespace App\Console\Commands;

use App\Models\MasterData\Building;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportLegacyBuildingsCommand extends Command
{
    protected $signature = 'legacy:import-buildings {--dry-run : Uji coba pemetaan tanpa mengubah data target}';

    protected $description = 'Import building reference data from legacy database (ref_gedung)';

    protected array $stats = [
        'read' => 0,
        'skipped' => 0,
        'valid' => 0,
        'would_create' => 0,
        'would_update' => 0,
        'created' => 0,
        'updated' => 0,
        'failed' => 0,
    ];

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $this->info('Starting legacy Buildings import...');
        if ($isDryRun) {
            $this->warn('RUNNING IN DRY-RUN MODE (No data will be saved)');
        }

        try {
            $legacyData = DB::connection('legacy')
                ->table('ref_gedung')
                ->orderBy('id')
                ->get();
        } catch (\Exception $e) {
            $this->error('Failed to connect to legacy database or read ref_gedung table.');
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->stats['read'] = $legacyData->count();
        $this->info("Read {$this->stats['read']} records from ref_gedung.");

        $validRecords = [];

        foreach ($legacyData as $item) {
            $legacyId = (int) $item->id;
            $name = trim(preg_replace('/\s+/', ' ', (string) ($item->nama ?? '')));
            $description = trim((string) ($item->deskripsi ?? '')) ?: null;
            $isSoftDeleted = $this->isSoftDeleted($item->deleted_at);

            if ($name === '') {
                $this->warn("Skipping legacy ID {$legacyId}: Name is empty");
                $this->stats['skipped']++;
                continue;
            }

            if ($isSoftDeleted) {
                // If soft-deleted in legacy and already in target, soft delete it
                if (! $isDryRun) {
                    $existing = Building::withTrashed()->where('legacy_id', $legacyId)->first();
                    if ($existing && ! $existing->trashed()) {
                        $existing->delete();
                    }
                }
                $this->stats['skipped']++;
                continue;
            }

            $validRecords[] = [
                'legacy_id' => $legacyId,
                'name' => $name,
                'description' => $description,
            ];
            $this->stats['valid']++;
        }

        DB::beginTransaction();
        try {
            foreach ($validRecords as $record) {
                $existing = Building::withTrashed()
                    ->where('legacy_id', $record['legacy_id'])
                    ->first();

                if (! $existing) {
                    // Check by name as fallback to prevent duplicates
                    $existing = Building::withTrashed()
                        ->where('name', $record['name'])
                        ->first();
                }

                if ($isDryRun) {
                    if ($existing) {
                        $this->stats['would_update']++;
                    } else {
                        $this->stats['would_create']++;
                    }
                    continue;
                }

                if ($existing) {
                    $existing->update([
                        'legacy_id' => $record['legacy_id'],
                        'name' => $record['name'],
                        'description' => $record['description'],
                    ]);

                    if ($existing->trashed()) {
                        $existing->restore();
                    }

                    $this->stats['updated']++;
                } else {
                    Building::create([
                        'legacy_id' => $record['legacy_id'],
                        'name' => $record['name'],
                        'description' => $record['description'],
                    ]);

                    $this->stats['created']++;
                }
            }

            if ($isDryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->stats['failed']++;
            $this->error('Failed to import buildings: ' . $e->getMessage());
            Log::error('Legacy buildings import error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }

        $this->printReport($isDryRun);

        return Command::SUCCESS;
    }

    protected function isSoftDeleted(mixed $deletedAt): bool
    {
        if ($deletedAt === null) {
            return false;
        }

        $str = trim((string) $deletedAt);

        return ! in_array($str, ['', '0000-00-00', '0000-00-00 00:00:00'], true);
    }

    protected function printReport(bool $isDryRun): void
    {
        $prefix = $isDryRun ? 'Would ' : '';
        $this->info(PHP_EOL . ($isDryRun ? '[DRY-RUN] ' : '') . 'Import Report');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Read', $this->stats['read']],
                ['Skipped (Soft Deleted / Invalid)', $this->stats['skipped']],
                ['Valid for Import', $this->stats['valid']],
                [$prefix . 'Create', $isDryRun ? $this->stats['would_create'] : $this->stats['created']],
                [$prefix . 'Update', $isDryRun ? $this->stats['would_update'] : $this->stats['updated']],
                ['Failed', $this->stats['failed']],
            ]
        );
    }
}
