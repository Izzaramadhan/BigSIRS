<?php

namespace App\Console\Commands;

use App\Models\MasterData\Doctor;
use App\Models\MasterData\DoctorSchedule;
use App\Models\Polyclinic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LegacyImportDoctorSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:import-doctor-schedules {--dry-run : Only show what would be done without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import doctor schedules from legacy database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('Starting import of doctor schedules'.($dryRun ? ' [DRY RUN]' : ''));

        $schedules = DB::connection('legacy')->table('trx_jadwal_dokter')->get();
        $this->info('Total legacy records read: '.$schedules->count());

        $stats = [
            'soft_deleted' => 0,
            'valid' => 0,
            'missing_doctor' => 0,
            'missing_polyclinic' => 0,
            'time_conflict' => 0, // In legacy we might just skip or we might import anyway? Let's just import and count conflicts if we want, or rely on update.
            'would_create' => 0,
            'would_update' => 0,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
        ];

        // Cache mappings
        $doctors = Doctor::whereNotNull('legacy_id')->pluck('id', 'legacy_id');
        $polyclinics = Polyclinic::whereNotNull('legacy_id')->pluck('id', 'legacy_id');

        $dayMap = [
            'Senin' => 1,
            'Selasa' => 2,
            'Rabu' => 3,
            'Kamis' => 4,
            'Jumat' => 5,
            'Sabtu' => 6,
            'Minggu' => 7,
        ];

        DB::beginTransaction();

        try {
            foreach ($schedules as $row) {
                if ($row->status === '0') {
                    $stats['soft_deleted']++;

                    continue; // Skip soft deleted
                }

                if (! isset($doctors[$row->id_dokter])) {
                    $stats['missing_doctor']++;

                    continue;
                }

                if (! isset($polyclinics[$row->id_poliklinik])) {
                    $stats['missing_polyclinic']++;

                    continue;
                }

                $doctorId = $doctors[$row->id_dokter];
                $polyId = $polyclinics[$row->id_poliklinik];
                $dayOfWeek = $dayMap[$row->hari_praktik] ?? null;

                if (! $dayOfWeek || ! $row->jam_mulai || ! $row->jam_selesai) {
                    $stats['failed']++;

                    continue;
                }

                $stats['valid']++;

                $data = [
                    'legacy_id' => $row->id,
                    'doctor_id' => $doctorId,
                    'polyclinic_id' => $polyId,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $row->jam_mulai,
                    'end_time' => $row->jam_selesai,
                    'is_holiday' => (bool) $row->libur,
                    'online_quota' => (int) $row->kuota_online,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ];

                $existing = DoctorSchedule::where('legacy_id', $row->id)->first();

                if ($existing) {
                    $stats['would_update']++;
                    if (! $dryRun) {
                        $existing->update($data);
                        $stats['updated']++;
                    }
                } else {
                    $stats['would_create']++;
                    if (! $dryRun) {
                        DoctorSchedule::create($data);
                        $stats['created']++;
                    }
                }
            }

            if ($dryRun) {
                DB::rollBack();
                $this->info('Dry run completed. No data was modified.');
            } else {
                DB::commit();
                $this->info('Import completed successfully.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Import failed: '.$e->getMessage());

            return 1;
        }

        $this->table(
            ['Metric', 'Value'],
            collect($stats)->map(fn ($v, $k) => [$k, $v])->toArray()
        );

        return 0;
    }
}
