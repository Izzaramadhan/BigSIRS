<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Signature('legacy:import-employees {--dry-run : Only show what would be imported}')]
#[Description('Import employees from legacy database (ref_pegawai & master_person_index)')]
class LegacyImportEmployeesCommand extends Command
{
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->info('Running in DRY-RUN mode. No data will be written.');
        }

        $legacyDb = DB::connection('legacy');

        $this->info('Fetching legacy employees from ref_pegawai...');

        $employees = $legacyDb->select('
            SELECT p.id, p.nip, p.status as is_active, p.deleted_at as p_deleted_at, 
                   m.nama, j.jabatan as nama_jabatan
            FROM ref_pegawai p
            LEFT JOIN master_person_index m ON p.id_mpi = m.id
            LEFT JOIN ref_jabatan j ON p.id_jabatan = j.id
        ');

        $stats = ['create' => 0, 'update' => 0, 'skip' => 0, 'failed' => 0];

        $this->withProgressBar($employees, function ($row) use ($dryRun, &$stats) {
            try {
                $code = ! empty($row->nip) ? $row->nip : 'EMP-'.$row->id;
                $name = ! empty($row->nama) ? $row->nama : 'Pegawai tidak dikenal (Legacy ID: '.$row->id.')';

                if (! $dryRun) {
                    $employee = Employee::updateOrCreate(
                        ['legacy_id' => $row->id],
                        [
                            'code' => $code,
                            'name' => $name,
                            'profession' => $row->nama_jabatan,
                            'is_active' => (bool) $row->is_active,
                        ]
                    );

                    if ($employee->wasRecentlyCreated) {
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
                    Log::error("Failed importing employee legacy_id {$row->id}: ".$e->getMessage());
                }
            }
        });

        $this->newLine();
        $this->info('Import completed.');
        $this->table(['Create', 'Update', 'Skip (Deleted)', 'Failed'], [[$stats['create'], $stats['update'], $stats['skip'], $stats['failed']]]);
    }
}
