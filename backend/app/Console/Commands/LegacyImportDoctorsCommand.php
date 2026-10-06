<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\MasterData\Doctor;
use App\Models\MasterData\Specialization;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Signature('legacy:import-doctors {--dry-run : Only show what would be imported}')]
#[Description('Import doctors from legacy database')]
class LegacyImportDoctorsCommand extends Command
{
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->info('Running in DRY-RUN mode. No data will be written.');
        }

        $legacyDb = DB::connection('legacy');

        $this->info('Fetching legacy doctors from ref_dokter...');

        // We join with ref_pegawai to get the employee legacy ID
        $doctors = $legacyDb->select('
            SELECT d.id, d.id_mpi, d.id_spesialisasi, d.no_str, d.sip, d.masa_berlaku, d.status as is_active,
                   d.deleted_at, d.kode_dpjp, d.ihs_id, d.ttd, p.id as legacy_employee_id
            FROM ref_dokter d
            LEFT JOIN ref_pegawai p ON d.id_mpi = p.id_mpi
        ');

        $stats = [
            'read' => count($doctors),
            'orphan_employee' => 0,
            'orphan_specialization' => 0,
            'skipped_deleted' => 0,
            'create' => 0,
            'update' => 0,
            'failed' => 0,
            'signature_missing' => 0,
        ];

        // Cache specializations
        $specMap = Specialization::pluck('id', 'legacy_id')->toArray();
        // Cache employees
        $empMap = Employee::pluck('id', 'legacy_id')->toArray();

        $this->withProgressBar($doctors, function ($row) use ($dryRun, &$stats, $specMap, $empMap) {
            try {
                if ($row->deleted_at && $row->deleted_at !== '0000-00-00 00:00:00') {
                    $stats['skipped_deleted']++;

                    return;
                }

                if (! $row->legacy_employee_id || ! isset($empMap[$row->legacy_employee_id])) {
                    $stats['orphan_employee']++;

                    return;
                }

                $employeeId = $empMap[$row->legacy_employee_id];
                $specId = null;

                if ($row->id_spesialisasi) {
                    if (isset($specMap[$row->id_spesialisasi])) {
                        $specId = $specMap[$row->id_spesialisasi];
                    } else {
                        $stats['orphan_specialization']++;
                        // We still import but without specialization or handle as needed
                    }
                }

                $signaturePath = null;
                if (! empty($row->ttd)) {
                    $signaturePath = $row->ttd;
                    $stats['signature_missing']++; // Mark as missing since we don't have the files
                }

                if (! $dryRun) {
                    $doctor = Doctor::withTrashed()->updateOrCreate(
                        ['legacy_id' => $row->id],
                        [
                            'employee_id' => $employeeId,
                            'specialization_id' => $specId,
                            'str_number' => $row->no_str,
                            'sip_number' => $row->sip,
                            'sip_valid_until' => ($row->masa_berlaku && $row->masa_berlaku !== '0000-00-00') ? $row->masa_berlaku : null,
                            'bpjs_dpjp_code' => $row->kode_dpjp,
                            'ihs_number' => $row->ihs_id,
                            'signature_path' => $signaturePath,
                            'is_active' => (bool) $row->is_active,
                            'deleted_at' => null, // restore if soft deleted
                        ]
                    );

                    if ($doctor->wasRecentlyCreated) {
                        $stats['create']++;
                    } else {
                        $stats['update']++;
                    }
                } else {
                    // Check if exists for dry run stats
                    $exists = Doctor::where('legacy_id', $row->id)->exists();
                    if ($exists) {
                        $stats['update']++;
                    } else {
                        $stats['create']++;
                    }
                }
            } catch (\Exception $e) {
                $stats['failed']++;
                if (! $dryRun) {
                    Log::error("Failed importing doctor legacy_id {$row->id}: ".$e->getMessage());
                }
            }
        });

        $this->newLine();
        $this->info('Import completed.');
        $this->table(
            ['Total Read', 'Valid/Processed', 'Skipped Soft Deleted', 'Orphan Employee', 'Orphan Specialization', 'Would Create / Created', 'Would Update / Updated', 'Signature Missing', 'Failed'],
            [[
                $stats['read'],
                $stats['create'] + $stats['update'],
                $stats['skipped_deleted'],
                $stats['orphan_employee'],
                $stats['orphan_specialization'],
                $stats['create'],
                $stats['update'],
                $stats['signature_missing'],
                $stats['failed'],
            ]]
        );
    }
}
