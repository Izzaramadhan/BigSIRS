<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Education;
use App\Models\Occupation;
use App\Models\MasterData\Position;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyEmployeesCommand extends Command
{
    protected $signature = 'import:legacy-employees';
    protected $description = 'Import employees from legacy ref_pegawai and master_person_index';

    public function handle()
    {
        $this->info('Starting employees import...');
        
        try {
            // First load maps
            $positionsMap = Position::pluck('id', 'legacy_id')->toArray();
            $educationsMap = Education::pluck('id', 'legacy_id')->toArray();
            $occupationsMap = Occupation::pluck('id', 'legacy_id')->toArray();
            
            $legacyEmployees = DB::connection('legacy')
                ->table('ref_pegawai')
                ->join('master_person_index', 'ref_pegawai.id_mpi', '=', 'master_person_index.id')
                ->select('ref_pegawai.*', 'master_person_index.*', 'ref_pegawai.id as legacy_employee_id', 'master_person_index.id as legacy_person_id', 'ref_pegawai.status as employee_status')
                ->get();
                
            $created = 0;
            $updated = 0;
            
            foreach ($legacyEmployees as $legacy) {
                // Determine blood type based on enum definition
                $bloodType = $legacy->gol_darah;
                if ($bloodType == '-' || empty($bloodType)) {
                    $bloodType = 'Unknown';
                }
                
                // Process birth date
                $birthDate = $legacy->tgl_lahir;
                if ($birthDate == '0000-00-00' || empty($birthDate)) {
                    $birthDate = null;
                }
                
                $positionId = null;
                if ($legacy->id_jabatan && isset($positionsMap[$legacy->id_jabatan])) {
                    $positionId = $positionsMap[$legacy->id_jabatan];
                }
                
                $educationId = null;
                if ($legacy->id_pendidikan && isset($educationsMap[$legacy->id_pendidikan])) {
                    $educationId = $educationsMap[$legacy->id_pendidikan];
                }
                
                $occupationId = null;
                if ($legacy->id_pekerjaan && isset($occupationsMap[$legacy->id_pekerjaan])) {
                    $occupationId = $occupationsMap[$legacy->id_pekerjaan];
                }

                $status = isset($legacy->employee_status) ? (bool)$legacy->employee_status : true;
                
                // For code/NIP, handle 0
                $code = trim($legacy->nip);
                
                $employee = Employee::withTrashed()->updateOrCreate(
                    ['legacy_id' => $legacy->legacy_employee_id],
                    [
                        'code' => $code === '' ? null : $code,
                        'national_id' => empty($legacy->no_ktp) || $legacy->no_ktp == '0' ? null : $legacy->no_ktp,
                        'name' => trim($legacy->nama) ?: 'Unknown',
                        'birth_place' => $legacy->tempat_lahir,
                        'birth_date' => $birthDate,
                        'gender' => in_array($legacy->gender, ['L', 'P']) ? $legacy->gender : null,
                        'nationality' => $legacy->kebangsaan,
                        'blood_type' => in_array($bloodType, ['A', 'B', 'AB', 'O', 'Unknown']) ? $bloodType : null,
                        'religion' => $legacy->agama,
                        'marital_status' => $legacy->status_perkawinan,
                        'address' => $legacy->alamat,
                        'postal_code' => $legacy->kode_pos,
                        'province_id' => $legacy->id_provinsi,
                        'regency_id' => $legacy->id_kabupaten,
                        'district_id' => $legacy->id_kecamatan,
                        'village_id' => $legacy->id_kelurahan,
                        'phone' => $legacy->no_hp,
                        'education_id' => $educationId,
                        'occupation_id' => $occupationId,
                        'position_id' => $positionId,
                        'is_active' => $status,
                    ]
                );
                
                if ($employee->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }
            
            $this->info("Import completed! Created: {$created}, Updated: {$updated}");
        } catch (\Exception $e) {
            $this->error("Import failed: " . $e->getMessage() . " on line " . $e->getLine());
        }
    }
}
