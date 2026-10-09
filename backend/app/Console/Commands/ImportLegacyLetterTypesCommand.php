<?php

namespace App\Console\Commands;

use App\Models\LetterType;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyLetterTypesCommand extends Command
{
    protected $signature = 'import:legacy-letter-types';

    protected $description = 'Import letter types from legacy database';

    public function handle()
    {
        $this->info('Starting legacy letter types import...');

        $legacyDb = DB::connection('legacy');

        try {
            $legacyDb->getPdo();
        } catch (\Exception $e) {
            $this->error('Could not connect to legacy database. Make sure it is configured correctly.');

            return 1;
        }

        $records = $legacyDb->table('ref_jenis_surat')->get();
        $total = count($records);

        if ($total === 0) {
            $this->info('No records found in legacy database.');

            return 0;
        }

        $this->info("Found {$total} records. Starting import...");

        $created = 0;
        $updated = 0;

        DB::beginTransaction();
        try {
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
                    'description' => ! empty(trim($record->deskripsi)) ? trim($record->deskripsi) : null,
                    'legacy_resource' => ! empty(trim($record->resource)) ? trim($record->resource) : null,
                    'is_active' => (bool) $record->status,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                    'deleted_at' => $deletedAt,
                ];

                $existing = LetterType::withTrashed()->where('legacy_id', $record->id)->first();
                if ($existing) {
                    $existing->update($data);
                    $updated++;
                } else {
                    $data['legacy_id'] = $record->id;
                    LetterType::withTrashed()->create($data);
                    $created++;
                }
            }

            DB::commit();
            $this->info("Import completed successfully! Created: {$created}, Updated: {$updated}");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Import failed: '.$e->getMessage());

            return 1;
        }
    }
}
