<?php

namespace App\Console\Commands;

use App\Models\MasterData\Room;
use App\Models\MasterData\RoomClass;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportLegacyRoomClassesCommand extends Command
{
    protected $signature = 'legacy:import-room-classes {--dry-run : Uji coba pemetaan tanpa mengubah data target}';

    protected $description = 'Import room class reference data from legacy database (ref_kelas)';

    protected array $stats = [
        'read' => 0,
        'skipped' => 0,
        'valid' => 0,
        'would_create' => 0,
        'would_update' => 0,
        'created' => 0,
        'updated' => 0,
        'rooms_mapped' => 0,
        'failed' => 0,
    ];

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $this->info('Starting legacy Room Classes import...');
        if ($isDryRun) {
            $this->warn('RUNNING IN DRY-RUN MODE (No data will be saved)');
        }

        try {
            $legacyData = DB::connection('legacy')
                ->table('ref_kelas')
                ->orderBy('id')
                ->get();
        } catch (\Exception $e) {
            $this->error('Failed to connect to legacy database or read ref_kelas table.');
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->stats['read'] = $legacyData->count();
        $this->info("Read {$this->stats['read']} records from ref_kelas.");

        $validRecords = [];

        $codeMap = [
            1 => 'KL1',
            2 => 'KL2',
            3 => 'KL3',
            4 => 'VIP',
            5 => 'VVP',
            6 => 'UTM',
            7 => 'KKH',
            8 => 'VVP-2',
            9 => 'IBS',
            10 => 'VVP-K',
            11 => 'IGD',
        ];

        foreach ($legacyData as $item) {
            $legacyId = (int) $item->id;
            $name = trim(preg_replace('/\s+/', ' ', (string) ($item->nama ?? '')));
            $descRaw = trim((string) ($item->deskripsi ?? ''));
            $description = ($descRaw === '' || $descRaw === '-') ? null : $descRaw;
            // Legacy IDs 8 and 11 were disabled and not used by any rooms
            $isSoftDeleted = in_array($legacyId, [8, 11], true);

            if ($name === '') {
                $this->warn("Skipping legacy ID {$legacyId}: Name is empty");
                $this->stats['skipped']++;
                continue;
            }

            $code = $codeMap[$legacyId] ?? trim((string) ($item->kode_aplicares ?: ($item->kode ?: 'KLS-' . str_pad((string) $legacyId, 2, '0', STR_PAD_LEFT))));
            $price = ! empty($item->harga) ? (float) $item->harga : 0.00;
            $aplicareCode = ! empty($item->kode_aplicares) ? trim((string) $item->kode_aplicares) : null;
            $sirsCode = ! empty($item->kode_sirs) ? trim((string) $item->kode_sirs) : null;

            $validRecords[] = [
                'legacy_id' => $legacyId,
                'code' => $code,
                'name' => $name,
                'price' => $price,
                'description' => $description,
                'aplicare_code' => $aplicareCode,
                'sirs_code' => $sirsCode,
                'is_soft_deleted' => $isSoftDeleted,
            ];
            $this->stats['valid']++;
        }

        DB::beginTransaction();
        try {
            foreach ($validRecords as $record) {
                $existing = RoomClass::withTrashed()
                    ->where('legacy_id', $record['legacy_id'])
                    ->first();

                if (! $existing) {
                    $existing = RoomClass::withTrashed()
                        ->where('name', $record['name'])
                        ->first();
                }

                if ($record['is_soft_deleted']) {
                    if (! $isDryRun && $existing && ! $existing->trashed()) {
                        $existing->delete();
                    }
                    $this->stats['skipped']++;
                    continue;
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
                        'code' => $record['code'],
                        'name' => $record['name'],
                        'price' => $record['price'],
                        'description' => $record['description'],
                        'aplicare_code' => $record['aplicare_code'],
                        'sirs_code' => $record['sirs_code'],
                    ]);

                    if ($existing->trashed()) {
                        $existing->restore();
                    }

                    $this->stats['updated']++;
                } else {
                    RoomClass::create([
                        'legacy_id' => $record['legacy_id'],
                        'code' => $record['code'],
                        'name' => $record['name'],
                        'price' => $record['price'],
                        'description' => $record['description'],
                        'aplicare_code' => $record['aplicare_code'],
                        'sirs_code' => $record['sirs_code'],
                    ]);

                    $this->stats['created']++;
                }
            }

            // Map rooms.room_class_id from existing class_id or ref_kamar
            if (! $isDryRun) {
                $classMap = RoomClass::pluck('id', 'legacy_id')->toArray();
                $rooms = Room::withTrashed()->whereNull('room_class_id')->get();
                foreach ($rooms as $room) {
                    $legacyClassId = $room->class_id;
                    if ($legacyClassId && isset($classMap[$legacyClassId])) {
                        $room->update(['room_class_id' => $classMap[$legacyClassId]]);
                        $this->stats['rooms_mapped']++;
                    }
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
            $this->error('Failed to import room classes: ' . $e->getMessage());
            Log::error('Legacy room classes import error: ' . $e->getMessage(), [
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
                ['Skipped (Soft Deleted in Legacy)', $this->stats['skipped']],
                ['Valid for Import', $this->stats['valid']],
                [$prefix . 'Create', $isDryRun ? $this->stats['would_create'] : $this->stats['created']],
                [$prefix . 'Update', $isDryRun ? $this->stats['would_update'] : $this->stats['updated']],
                ['Rooms Mapped to RoomClass', $this->stats['rooms_mapped']],
                ['Failed', $this->stats['failed']],
            ]
        );
    }
}
