<?php

namespace App\Console\Commands;

use App\Models\MasterData\Bed;
use App\Models\MasterData\Room;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportLegacyBedsCommand extends Command
{
    protected $signature = 'legacy:import-beds {--dry-run : Uji coba pemetaan tanpa mengubah data target}';

    protected $description = 'Import bed reference data from legacy database (ref_bed)';

    protected array $stats = [
        'read' => 0,
        'skipped' => 0,
        'missing_room' => 0,
        'valid' => 0,
        'would_create' => 0,
        'would_update' => 0,
        'created' => 0,
        'updated' => 0,
        'failed' => 0,
    ];

    /** @var array<string> */
    protected array $unmappedDetails = [];

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $this->info('Starting legacy Beds import...');
        if ($isDryRun) {
            $this->warn('RUNNING IN DRY-RUN MODE (No data will be saved)');
        }

        try {
            $legacyData = DB::connection('legacy')
                ->table('ref_bed')
                ->orderBy('id')
                ->get();
        } catch (\Exception $e) {
            $this->error('Failed to connect to legacy database or read ref_bed table.');
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->stats['read'] = $legacyData->count();
        $this->info("Read {$this->stats['read']} records from ref_bed.");

        // Cache rooms mapped by legacy_id and id
        $roomsByLegacyId = Room::whereNotNull('legacy_id')->pluck('id', 'legacy_id')->toArray();
        $roomsById = Room::pluck('id', 'id')->toArray();

        $validRecords = [];

        foreach ($legacyData as $item) {
            $legacyId = (int) $item->id;
            $legacyRoomId = (int) ($item->id_kamar ?? 0);
            $name = trim(preg_replace('/\s+/', ' ', (string) ($item->nama ?? '')));
            $descRaw = trim((string) ($item->deskripsi ?? ''));
            $description = ($descRaw === '' || $descRaw === '-') ? null : $descRaw;
            $isSoftDeleted = $this->isSoftDeleted($item->deleted_at) || (string) ($item->status ?? '1') === '0';

            if ($name === '') {
                $this->warn("Skipping legacy ID {$legacyId}: Name is empty");
                $this->stats['skipped']++;
                $this->unmappedDetails[] = "Bed ID {$legacyId}: Nama bed kosong";
                continue;
            }

            // Find target room_id
            $targetRoomId = $roomsByLegacyId[$legacyRoomId] ?? $roomsById[$legacyRoomId] ?? null;

            if (! $targetRoomId) {
                $reason = "Kamar dengan legacy ID {$legacyRoomId} tidak ditemukan pada tabel target rooms";
                $this->warn("Skipping legacy ID {$legacyId} ({$name}): {$reason}");
                $this->stats['missing_room']++;
                $this->stats['skipped']++;
                $this->unmappedDetails[] = "Bed ID {$legacyId} ({$name}): {$reason}";
                continue;
            }

            if ($isSoftDeleted) {
                if (! $isDryRun) {
                    $existing = Bed::withTrashed()->where('legacy_id', $legacyId)->first();
                    if ($existing && ! $existing->trashed()) {
                        $existing->delete();
                    }
                }
                $this->stats['skipped']++;
                continue;
            }

            $validRecords[] = [
                'legacy_id' => $legacyId,
                'room_id' => $targetRoomId,
                'name' => $name,
                'description' => $description,
            ];
        }

        $this->stats['valid'] = count($validRecords);
        $this->info("Found {$this->stats['valid']} valid records to import.");

        DB::beginTransaction();
        try {
            foreach ($validRecords as $record) {
                $existing = Bed::withTrashed()
                    ->where('legacy_id', $record['legacy_id'])
                    ->first();

                if (! $existing) {
                    $existing = Bed::withTrashed()
                        ->where('room_id', $record['room_id'])
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
                        'room_id' => $record['room_id'],
                        'legacy_id' => $record['legacy_id'],
                        'name' => $record['name'],
                        'description' => $record['description'],
                    ]);

                    if ($existing->trashed()) {
                        $existing->restore();
                    }

                    $this->stats['updated']++;
                } else {
                    Bed::create([
                        'room_id' => $record['room_id'],
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
            $this->error('Failed to import records: ' . $e->getMessage());
            $this->stats['failed']++;

            Log::error('Legacy beds import error: ' . $e->getMessage(), [
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
                ['Total Read from ref_bed', $this->stats['read']],
                ['Skipped (Soft Deleted / Inactive / Missing Room)', $this->stats['skipped']],
                ['Missing Target Room', $this->stats['missing_room']],
                ['Valid for Import', $this->stats['valid']],
                [$prefix . 'Create', $isDryRun ? $this->stats['would_create'] : $this->stats['created']],
                [$prefix . 'Update', $isDryRun ? $this->stats['would_update'] : $this->stats['updated']],
                ['Failed', $this->stats['failed']],
            ]
        );

        if (! empty($this->unmappedDetails)) {
            $this->warn(PHP_EOL . 'Unmapped Details:');
            foreach ($this->unmappedDetails as $detail) {
                $this->line(" - {$detail}");
            }
        }
    }
}
