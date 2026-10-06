<?php

namespace App\Console\Commands;

use App\Models\ServiceType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImportLegacyServiceTypesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:import-service-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import jenis layanan dari database legacy ref_jenis_layanan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai import jenis layanan dari database legacy...');

        try {
            $legacyServiceTypes = DB::connection('legacy')->table('ref_jenis_layanan')->get();
        } catch (Throwable $e) {
            $this->error('Gagal terhubung ke database legacy atau tabel ref_jenis_layanan tidak ditemukan.');
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $totalLegacy = $legacyServiceTypes->count();
        $this->info("Ditemukan {$totalLegacy} data jenis layanan di legacy.");

        $created = 0;
        $updated = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($totalLegacy);
        $bar->start();

        DB::beginTransaction();
        try {
            foreach ($legacyServiceTypes as $legacy) {
                // Determine deleted_at
                $deletedAt = null;
                if (! empty($legacy->deleted_at) && $legacy->deleted_at !== '0000-00-00 00:00:00') {
                    $deletedAt = $legacy->deleted_at;
                }

                // Status mapping: if '1' then active, else inactive
                $isActive = (string) $legacy->status === '1';

                try {
                    $serviceType = ServiceType::withTrashed()->where('legacy_id', $legacy->id)->first();

                    if ($serviceType) {
                        $serviceType->update([
                            'name' => $legacy->nama,
                            'is_active' => $isActive,
                            'deleted_at' => $deletedAt,
                        ]);
                        $updated++;
                    } else {
                        // Create
                        $serviceType = new ServiceType([
                            'legacy_id' => $legacy->id,
                            'name' => $legacy->nama,
                            'is_active' => $isActive,
                        ]);
                        if ($deletedAt) {
                            $serviceType->deleted_at = $deletedAt;
                        }
                        $serviceType->save();
                        $created++;
                    }
                } catch (Throwable $e) {
                    $failed++;
                    $this->error("\nGagal memproses ID {$legacy->id}: ".$e->getMessage());
                }

                $bar->advance();
            }

            DB::commit();
            $bar->finish();
            $this->info("\nImport selesai!");
            $this->info("Berhasil ditambahkan: $created");
            $this->info("Berhasil diperbarui: $updated");
            $this->error("Gagal diproses: $failed");

            return Command::SUCCESS;
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error("\nTerjadi kesalahan fatal saat menyimpan ke database target: ".$e->getMessage());

            return Command::FAILURE;
        }
    }
}
