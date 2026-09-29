<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\MasterData\MedicalProcedure;

#[Signature('legacy:repair-procedure-user-mappings {--dry-run : Only show what would be repaired}')]
#[Description('Repair procedure user mappings and employee names based on legacy data')]
class LegacyRepairProcedureUserMappingsCommand extends Command
{
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->info("Running in DRY-RUN mode. No data will be written.");
        }

        $legacyDb = DB::connection('legacy');
        
        $this->info("Fetching legacy employees from ref_pegawai...");

        $legacyEmployees = $legacyDb->select("
            SELECT p.id, p.nip, p.status as is_active, p.deleted_at as p_deleted_at, 
                   m.nama, j.jabatan as nama_jabatan
            FROM ref_pegawai p
            LEFT JOIN master_person_index m ON p.id_mpi = m.id
            LEFT JOIN ref_jabatan j ON p.id_jabatan = j.id
        ");

        $stats = [
            'emp_read' => count($legacyEmployees),
            'emp_match' => 0,
            'emp_name_diff' => 0,
            'map_correct' => 0,
            'map_wrong' => 0,
            'map_repaired' => 0,
            'orphan_emp' => 0,
            'orphan_proc' => 0,
            'conflict' => 0,
            'failed' => 0
        ];

        // 1. Fix employees
        $this->info("Step 1: Repairing Employees data...");
        $employeesByLegacy = [];
        foreach ($legacyEmployees as $row) {
            $code = !empty($row->nip) ? $row->nip : 'EMP-' . $row->id;
            $name = !empty($row->nama) ? $row->nama : 'Pegawai tidak dikenal (Legacy ID: ' . $row->id . ')';

            $emp = Employee::where('legacy_id', $row->id)->first();
            if ($emp) {
                $stats['emp_match']++;
                if ($emp->name !== $name || $emp->profession !== $row->nama_jabatan) {
                    $stats['emp_name_diff']++;
                    if (!$dryRun) {
                        $emp->update([
                            'name' => $name,
                            'profession' => $row->nama_jabatan,
                            'code' => $code,
                            'is_active' => (bool) $row->is_active,
                        ]);
                    }
                }
                $employeesByLegacy[$row->id] = $emp->id;
            } else {
                // If it doesn't exist, we will create it to avoid orphans
                if (!$dryRun) {
                    $emp = Employee::create([
                        'legacy_id' => $row->id,
                        'code' => $code,
                        'name' => $name,
                        'profession' => $row->nama_jabatan,
                        'is_active' => (bool) $row->is_active,
                    ]);
                    $employeesByLegacy[$row->id] = $emp->id;
                }
            }
        }

        // 2. Fix mappings
        $this->info("Step 2: Repairing Mappings...");
        $legacyMappings = $legacyDb->table('map_ref_tindakan_user')->get();
        $procedures = MedicalProcedure::whereNotNull('legacy_id')->pluck('id', 'legacy_id')->toArray();

        // Get all current mappings
        $currentMappingsRaw = DB::table('procedure_employee')->get();
        $currentMappings = [];
        foreach ($currentMappingsRaw as $cm) {
            $currentMappings[$cm->procedure_id . '_' . $cm->employee_id] = $cm;
        }

        $expectedMappings = [];

        foreach ($legacyMappings as $row) {
            if ($row->deleted_at && $row->deleted_at != '0000-00-00 00:00:00') {
                continue;
            }

            if (!isset($procedures[$row->id_ref_tindakan])) {
                $stats['orphan_proc']++;
                continue;
            }
            if (!isset($employeesByLegacy[$row->id_aktor])) {
                $stats['orphan_emp']++;
                continue;
            }

            $procId = $procedures[$row->id_ref_tindakan];
            $empId = $employeesByLegacy[$row->id_aktor];
            $key = $procId . '_' . $empId;
            $expectedMappings[$key] = true;

            if (isset($currentMappings[$key])) {
                $stats['map_correct']++;
            } else {
                $stats['map_wrong']++;
                if (!$dryRun) {
                    try {
                        DB::table('procedure_employee')->insert([
                            'procedure_id' => $procId,
                            'employee_id' => $empId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $stats['map_repaired']++;
                    } catch (\Exception $e) {
                        $stats['failed']++;
                    }
                } else {
                    $stats['map_repaired']++; // Estimate
                }
            }
        }

        // Remove mappings that shouldn't exist anymore
        foreach ($currentMappings as $key => $cm) {
            if (!isset($expectedMappings[$key])) {
                $stats['map_wrong']++;
                if (!$dryRun) {
                    try {
                        DB::table('procedure_employee')
                            ->where('procedure_id', $cm->procedure_id)
                            ->where('employee_id', $cm->employee_id)
                            ->delete();
                        $stats['map_repaired']++;
                    } catch (\Exception $e) {
                        $stats['failed']++;
                    }
                } else {
                    $stats['map_repaired']++;
                }
            }
        }

        $this->newLine();
        $this->info("Repair completed.");
        $this->info("Pegawai dibaca: " . $stats['emp_read']);
        $this->info("Pegawai target cocok: " . $stats['emp_match']);
        $this->info("Nama target berbeda: " . $stats['emp_name_diff']);
        $this->info("Mapping benar: " . $stats['map_correct']);
        $this->info("Mapping salah: " . $stats['map_wrong']);
        $this->info("Mapping akan diperbaiki: " . $stats['map_repaired']);
        $this->info("Orphan pegawai: " . $stats['orphan_emp']);
        $this->info("Orphan tindakan: " . $stats['orphan_proc']);
        $this->info("Konflik: " . $stats['conflict']);
        $this->info("Failed: " . $stats['failed']);
    }
}
