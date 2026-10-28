<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('legacy:import-all {--dry-run : Only show what would be imported without modifying database} {--with-villages : Also import 75,000 villages (takes ~5-10 minutes)}')]
#[Description('Jalankan seluruh proses sinkronisasi database legacy ke BigSIRS secara otomatis')]
class LegacyImportAllCommand extends Command
{
    /**
     * The ordered list of commands grouped by module.
     * Order is strictly tuned to satisfy foreign key relationships.
     */
    protected array $commandGroups = [
        'Referensi Wilayah & Dasar' => [
            'legacy:import-references',
        ],
        'Pelayanan Klinis & Ketenagakerjaan' => [
            'import:legacy-positions',
            'import:legacy-employees',
            'legacy:import-doctor-specializations',
            'legacy:import-doctors',
            'legacy:import-polyclinics',
            'legacy:import-doctor-schedules',
            'legacy:import-guarantors',
            'legacy:import-service-types',
            'legacy:import-diet-types',
            'import:legacy-activity-types',
            'import:legacy-letter-types',
        ],
        'Fasilitas & Tempat Tidur' => [
            'legacy:import-buildings',
            'legacy:import-floors',
            'legacy:import-wards',
            'legacy:import-room-classes',
            'legacy:import-rooms',
            'legacy:import-beds',
        ],
        'Logistik Farmasi' => [
            'import:legacy-warehouses',
            'import:legacy-suppliers',
            'import:medicine-categories',
            'import:medicine-units',
            'import:medicine-routes',
            'import:legacy-medicines',
            'legacy:import-medication-signas',
            'legacy:import-medicine-packages',
            'import:legacy-invoices',
            'legacy:link-polyclinic-warehouses',
        ],
        'Diagnosis, Prosedur & Penunjang Medis' => [
            'legacy:import-icd10',
            'legacy:import-icd9-cms',
            'legacy:import-procedure-categories',
            'legacy:import-tariff-components',
            'legacy:import-tariff-types',
            'legacy:import-procedures',
            'legacy:import-procedure-packages',
            'legacy:import-procedure-user-mappings',
            'legacy:import-report-groups',
            'import:legacy-laboratory-categories',
            'import:laboratory-groups',
            'import:legacy-laboratory-items',
            'import:radiology-types',
            'import:radiology-item-groups',
            'legacy:import-radiology-categories',
            'legacy:import-radiology-groups',
            'legacy:import-radiology-items',
            'legacy:import-follow-up-handlings',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $withVillages = $this->option('with-villages');

        $this->newLine();
        $this->info('===============================================================');
        $this->info('      MEMULAI SINKRONISASI LENGKAP DARI DATABASE LEGACY        ');
        $this->info('===============================================================');
        if ($dryRun) {
            $this->warn('Mode DRY-RUN aktif: Data tidak akan disimpan ke database.');
        }

        // Preflight Check: Memastikan koneksi database legacy tersedia
        $legacyDbName = config('database.connections.legacy.database', 'simrs_legacy');
        try {
            DB::connection('legacy')->getPdo();
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error("KONEKSI GAGAL: Database legacy '{$legacyDbName}' tidak ditemukan atau tidak dapat diakses.");
            $this->error("Pesan: {$e->getMessage()}");
            $this->newLine();
            $this->warn('Petunjuk Penyelesaian:');
            $this->line('Kontainer MySQL Docker belum memiliki database legacy atau belum di-restore dari dump SQL.');
            $this->line('Silakan jalankan perintah berikut dari root proyek:');
            $this->comment('  1. docker exec -i bigsirs-mysql mysql -uroot -pchange_root_password -e "CREATE DATABASE IF NOT EXISTS ' . $legacyDbName . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"');
            $this->comment('  2. docker exec -i bigsirs-mysql sh -c \'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" ' . $legacyDbName . ' < /backups/backup_db_simrs_masking.sql\'');
            $this->newLine();

            return Command::FAILURE;
        }

        // Pastikan tabel dasar ref_pendidikan ada
        try {
            $hasTable = DB::connection('legacy')->getSchemaBuilder()->hasTable('ref_pendidikan');
            if (! $hasTable) {
                $this->newLine();
                $this->error("DATABASE KOSONG: Database legacy '{$legacyDbName}' terhubung, namun tabel data master (seperti ref_pendidikan) belum ada.");
                $this->warn('Silakan restore file dump SQL dengan perintah:');
                $this->comment('  docker exec -i bigsirs-mysql sh -c \'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" ' . $legacyDbName . ' < /backups/backup_db_simrs_masking.sql\'');
                $this->newLine();

                return Command::FAILURE;
            }
        } catch (\Throwable $e) {
            // Abaikan jika pengecekan schema gagal karena alasan non-fatal
        }

        $startTime = microtime(true);
        $totalCommands = array_sum(array_map('count', $this->commandGroups));
        $current = 0;

        foreach ($this->commandGroups as $groupName => $commands) {
            $this->newLine();
            $this->info(">>> Modul: {$groupName}");
            $this->line(str_repeat('-', 50));

            foreach ($commands as $cmd) {
                $current++;
                $this->line("  [{$current}/{$totalCommands}] Menjalankan: <comment>{$cmd}</comment>");

                try {
                    $commandObj = $this->getApplication()->find($cmd);
                    $params = [];
                    if ($dryRun && $commandObj->getDefinition()->hasOption('dry-run')) {
                        $params['--dry-run'] = true;
                    }

                    // By default skip villages to speed up setup unless requested
                    if (! $withVillages && $cmd === 'legacy:import-references') {
                        $params['--skip-villages'] = true;
                    }

                    $exitCode = $this->call($cmd, $params);
                    if ($exitCode !== 0) {
                        $this->warn("  Peringatan: {$cmd} selesai dengan status code {$exitCode}");
                    }
                } catch (\Throwable $e) {
                    $this->error("  Gagal menjalankan {$cmd}: {$e->getMessage()}");
                }
            }
        }

        $duration = round(microtime(true) - $startTime, 2);
        $this->newLine();
        $this->info('===============================================================');
        $this->info("  SINKRONISASI SELESAI ({$duration} detik)");
        $this->info('===============================================================');

        return Command::SUCCESS;
    }
}