<?php

namespace App\Console\Commands;

use App\Models\DietType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportLegacyDietTypesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:import-diet-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import master data Jenis Diet (Asuhan Gizi) dari database legacy simrs_legacy_full';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai import Jenis Diet (Asuhan Gizi)...');

        try {
            $legacyDb = DB::connection('legacy');
            $legacyDietTypes = $legacyDb->table('ref_jenis_diet')->get();

            $this->info("Ditemukan {$legacyDietTypes->count()} data jenis diet di legacy database.");

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $failed = 0;

            DB::beginTransaction();

            foreach ($legacyDietTypes as $legacy) {
                try {
                    $name = trim($legacy->nama ?? '');

                    if (empty($name)) {
                        $this->warn("Data dengan legacy_id {$legacy->id} dilewati karena nama kosong.");
                        $skipped++;

                        continue;
                    }

                    // Convert status: status=1 is active, else inactive
                    $isActive = ($legacy->status == 1);

                    // Convert deleted_at zero dates
                    $deletedAt = null;
                    if (! empty($legacy->deleted_at) && $legacy->deleted_at !== '0000-00-00 00:00:00') {
                        $deletedAt = $legacy->deleted_at;
                    }

                    // Check if exists
                    $existing = DietType::withTrashed()->where('legacy_id', $legacy->id)->first();

                    $desc = trim($legacy->deskripsi ?? '');
                    if ($desc === '-' || empty($desc)) {
                        $desc = null;
                    }

                    $dataToSave = [
                        'name' => $name,
                        'description' => $desc,
                        'is_active' => $isActive,
                        'deleted_at' => $deletedAt,
                    ];

                    if ($existing) {
                        $existing->update($dataToSave);
                        $updated++;
                    } else {
                        // Conflict on name? Use soft delete policy
                        $conflict = DietType::withTrashed()->where('name', $name)->first();
                        if ($conflict) {
                            $this->warn("Data nama '$name' duplikat dengan legacy_id {$legacy->id}. Akan memperbarui data tersebut.");
                            $conflict->update(array_merge($dataToSave, ['legacy_id' => $legacy->id]));
                            $updated++;
                        } else {
                            $newDiet = new DietType;
                            $newDiet->legacy_id = $legacy->id;
                            $newDiet->name = $name;
                            $newDiet->description = $desc;
                            $newDiet->is_active = $isActive;
                            if ($deletedAt) {
                                $newDiet->deleted_at = $deletedAt;
                            }
                            $newDiet->save();
                            $created++;
                        }
                    }

                } catch (\Exception $e) {
                    $this->error("Gagal import data dengan legacy_id {$legacy->id}: ".$e->getMessage());
                    Log::error("Failed to import diet type legacy_id {$legacy->id}: ".$e->getMessage());
                    $failed++;
                }
            }

            DB::commit();

            $this->info('Import selesai!');
            $this->info("Created: {$created}");
            $this->info("Updated: {$updated}");
            $this->info("Skipped: {$skipped}");
            $this->info("Failed:  {$failed}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Terjadi kesalahan fatal saat import: '.$e->getMessage());
            Log::error('Fatal error during diet types import: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
