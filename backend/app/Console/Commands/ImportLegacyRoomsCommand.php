<?php

namespace App\Console\Commands;

use App\Models\MasterData\Room;
use App\Models\MasterData\Ward;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportLegacyRoomsCommand extends Command
{
    protected $signature = 'legacy:import-rooms {--dry-run : Uji coba pemetaan tanpa mengubah data target}';

    protected $description = 'Import room reference data from legacy database (ref_kamar)';

    protected array $stats = [
        'read' => 0,
        'skipped' => 0,
        'missing_ward' => 0,
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

        $this->info('Starting legacy Rooms import...');
        if ($isDryRun) {
            $this->warn('RUNNING IN DRY-RUN MODE (No data will be saved)');
        }

        try {
            $legacyData = DB::connection('legacy')
                ->table('ref_kamar')
                ->orderBy('id')
                ->get();
        } catch (\Exception $e) {
            $this->error('Failed to connect to legacy database or read ref_kamar table.');
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->stats['read'] = $legacyData->count();
        $this->info("Read {$this->stats['read']} records from ref_kamar.");

        // Cache wards mapped by legacy_id and id
        $wardsByLegacyId = Ward::whereNotNull('legacy_id')->pluck('id', 'legacy_id')->toArray();
        $wardsById = Ward::pluck('id', 'id')->toArray();

        $validRecords = [];
        $usedCodes = [];

        foreach ($legacyData as $item) {
            $legacyId = (int) $item->id;
            $legacyWardId = (int) $item->id_bangsal;
            $name = trim(preg_replace('/\s+/', ' ', (string) ($item->nama ?? '')));
            $descRaw = trim((string) ($item->deskripsi ?? ''));
            $description = ($descRaw === '' || $descRaw === '-') ? null : $descRaw;
            $isSoftDeleted = $this->isSoftDeleted($item->deleted_at) || (string) ($item->status ?? '1') === '0';

            if ($name === '') {
                $this->warn("Skipping legacy ID {$legacyId}: Name is empty");
                $this->stats['skipped']++;
                continue;
            }

            // Find target ward_id
            $targetWardId = $wardsByLegacyId[$legacyWardId] ?? $wardsById[$legacyWardId] ?? null;

            if (! $targetWardId) {
                $this->warn("Skipping legacy ID {$legacyId}: Ward with legacy ID {$legacyWardId} not found in target wards");
                $this->stats['missing_ward']++;
                $this->stats['skipped']++;
                continue;
            }

            if ($isSoftDeleted) {
                if (! $isDryRun) {
                    $existing = Room::withTrashed()->where('legacy_id', $legacyId)->first();
                    if ($existing && ! $existing->trashed()) {
                        $existing->delete();
                    }
                }
                $this->stats['skipped']++;
                continue;
            }

            $rawCode = trim((string) ($item->kode ?? ''));
            $code = $rawCode !== '' ? $rawCode : 'KMR-' . str_pad((string) $legacyId, 4, '0', STR_PAD_LEFT);
            if (in_array($code, $usedCodes, true)) {
                $code = $code . '-' . $legacyId;
            }
            $usedCodes[] = $code;

            $validRecords[] = [
                'legacy_id' => $legacyId,
                'ward_id' => $targetWardId,
                'class_id' => $item->id_kelas ? (int) $item->id_kelas : null,
                'code' => $code,
                'name' => $name,
                'description' => $description,
            ];
            $this->stats['valid']++;
        }

        DB::beginTransaction();
        try {
            foreach ($validRecords as $record) {
                $existing = Room::withTrashed()
                    ->where('legacy_id', $record['legacy_id'])
                    ->first();

                if (! $existing) {
                    $existing = Room::withTrashed()
                        ->where('code', $record['code'])
                        ->first();
                }

                if (! $existing) {
                    $existing = Room::withTrashed()
                        ->where('ward_id', $record['ward_id'])
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
                        'ward_id' => $record['ward_id'],
                        'class_id' => $record['class_id'],
                        'legacy_id' => $record['legacy_id'],
                        'code' => $record['code'],
                        'name' => $record['name'],
                        'description' => $record['description'],
                    ]);

                    if ($existing->trashed()) {
                        $existing->restore();
                    }

                    $this->stats['updated']++;
                } else {
                    Room::create([
                        'ward_id' => $record['ward_id'],
                        'class_id' => $record['class_id'],
                        'legacy_id' => $record['legacy_id'],
                        'code' => $record['code'],
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
            $this->error('Failed to import rooms: ' . $e->getMessage());
            Log::error('Legacy rooms import error: ' . $e->getMessage(), [
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
                ['Skipped (Soft Deleted / Invalid / Missing Ward)', $this->stats['skipped']],
                ['Valid for Import', $this->stats['valid']],
                [$prefix . 'Create', $isDryRun ? $this->stats['would_create'] : $this->stats['created']],
                [$prefix . 'Update', $isDryRun ? $this->stats['would_update'] : $this->stats['updated']],
                ['Failed', $this->stats['failed']],
            ]
        );
    }
}
