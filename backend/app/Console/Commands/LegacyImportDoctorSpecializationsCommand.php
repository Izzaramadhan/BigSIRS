<?php

namespace App\Console\Commands;

use App\Models\MasterData\Specialization;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Signature('legacy:import-doctor-specializations {--dry-run : Only show what would be imported}')]
#[Description('Import doctor specializations from legacy database')]
class LegacyImportDoctorSpecializationsCommand extends Command
{
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->info('Running in DRY-RUN mode. No data will be written.');
        }

        $legacyDb = DB::connection('legacy');

        $this->info('Fetching legacy specializations from ref_spesialisasi...');

        $specs = $legacyDb->select('
            SELECT id, kode, nama_spesialisasi, status as is_active, deleted_at
            FROM ref_spesialisasi
        ');

        $stats = ['create' => 0, 'update' => 0, 'skip' => 0, 'failed' => 0];

        $this->withProgressBar($specs, function ($row) use ($dryRun, &$stats) {
            try {
                if (! $dryRun) {
                    $deletedAt = ($row->deleted_at && $row->deleted_at !== '0000-00-00 00:00:00') ? $row->deleted_at : null;

                    $spec = Specialization::withTrashed()->updateOrCreate(
                        ['legacy_id' => $row->id],
                        [
                            'code' => $row->kode,
                            'name' => $row->nama_spesialisasi ?: 'Unknown',
                            'is_active' => (bool) $row->is_active,
                            'deleted_at' => $deletedAt,
                        ]
                    );

                    if ($spec->wasRecentlyCreated) {
                        $stats['create']++;
                    } else {
                        $stats['update']++;
                    }
                } else {
                    $stats['create']++; // estimate
                }
            } catch (\Exception $e) {
                $stats['failed']++;
                if (! $dryRun) {
                    Log::error("Failed importing specialization legacy_id {$row->id}: ".$e->getMessage());
                }
            }
        });

        $this->newLine();
        $this->info('Import completed.');
        $this->table(['Create', 'Update', 'Skip', 'Failed'], [[$stats['create'], $stats['update'], $stats['skip'], $stats['failed']]]);
    }
}
