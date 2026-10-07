<?php

namespace App\Console\Commands;

use App\Models\ActivityType;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyActivityTypesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:legacy-activity-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import activity types from legacy database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting legacy activity types import...');

        $legacyDb = DB::connection('legacy');

        try {
            $legacyDb->getPdo();
        } catch (\Exception $e) {
            $this->error('Could not connect to legacy database. Make sure it is configured correctly.');

            return 1;
        }

        $rawRecords = $legacyDb->table('ref_jenis_kegiatan')->get();
        $records = $rawRecords->filter(function ($record) {
            if ($record->tipe === 'RL3.7-neonatal') {
                return false;
            }
            if ($record->id == 57 || $record->parent_id == 57) {
                return false;
            }
            return true;
        })->values();

        $total = count($records);

        if ($total === 0) {
            $this->info('No records found in legacy database.');

            return 0;
        }

        $this->info("Found {$total} records. Starting Stage 1 (Importing all as root)...");

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            // Stage 1: Import all without parent
            foreach ($records as $record) {
                $isDeleted = ! empty($record->deleted_at) && $record->deleted_at !== '0000-00-00 00:00:00';
                $deletedAt = $isDeleted ? Carbon::parse($record->deleted_at) : null;

                $createdAt = (! empty($record->created_at) && $record->created_at !== '0000-00-00 00:00:00')
                    ? Carbon::parse($record->created_at)
                    : now();

                $updatedAt = (! empty($record->updated_at) && $record->updated_at !== '0000-00-00 00:00:00')
                    ? Carbon::parse($record->updated_at)
                    : $createdAt;

                $data = [
                    'name' => trim($record->nama),
                    'is_active' => (bool) $record->status,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                    'deleted_at' => $deletedAt,
                ];

                $existing = ActivityType::withTrashed()->where('legacy_id', $record->id)->first();
                if ($existing) {
                    $existing->update($data);
                    $updated++;
                } else {
                    $data['legacy_id'] = $record->id;
                    $data['parent_id'] = null; // Enforce root in stage 1
                    ActivityType::withTrashed()->create($data);
                    $created++;
                }
            }

            DB::commit();
            $this->info("Stage 1 completed. Created: {$created}, Updated: {$updated}");

            $this->info('Starting Stage 2 (Restoring hierarchy)...');

            DB::beginTransaction();
            $hierarchyUpdated = 0;
            $orphans = 0;
            $selfParent = 0;

            foreach ($records as $record) {
                $pid = $record->parent_id;
                // If it has a parent
                if ($pid !== null && $pid !== '' && $pid != 0) {
                    // Prevent self parent
                    if ($pid == $record->id) {
                        $selfParent++;

                        continue;
                    }

                    $childTarget = ActivityType::withTrashed()->where('legacy_id', $record->id)->first();
                    $parentTarget = ActivityType::withTrashed()->where('legacy_id', $pid)->first();

                    if ($childTarget && $parentTarget) {
                        $childTarget->update(['parent_id' => $parentTarget->id]);
                        $hierarchyUpdated++;
                    } elseif ($childTarget && ! $parentTarget) {
                        $orphans++;
                    }
                } else {
                    // Ensure root
                    $childTarget = ActivityType::withTrashed()->where('legacy_id', $record->id)->first();
                    if ($childTarget && $childTarget->parent_id !== null) {
                        $childTarget->update(['parent_id' => null]);
                    }
                }
            }

            DB::commit();

            $this->info('Stage 2 completed.');
            $this->info("Hierarchy Linked: {$hierarchyUpdated}");
            $this->info("Orphans Ignored: {$orphans}");
            $this->info("Self Parents Ignored: {$selfParent}");

            $this->info('Import completed successfully!');

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Import failed: '.$e->getMessage());

            return 1;
        }
    }
}
