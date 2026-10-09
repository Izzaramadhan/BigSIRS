<?php

namespace App\Console\Commands;

use App\Models\MasterData\MedicationSigna;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LegacyImportMedicationSignasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:import-medication-signas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import medication signas (aturan pakai obat) from legacy simrs database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting import of medication signas from legacy database...');

        $legacySignas = DB::connection('legacy')->table('ref_signa')->get();
        
        $bar = $this->output->createProgressBar(count($legacySignas));
        $bar->start();

        $imported = 0;
        $updated = 0;

        foreach ($legacySignas as $legacySigna) {
            // skip if empty name
            if (empty(trim($legacySigna->nama))) {
                $bar->advance();
                continue;
            }

            // determine active status based on legacy status and deleted_at
            $isActive = true;
            if ($legacySigna->status === 0 || $legacySigna->status === null) {
                $isActive = false;
            }
            if ($legacySigna->deleted_at && $legacySigna->deleted_at !== '0000-00-00 00:00:00') {
                $isActive = false;
            }

            $signa = MedicationSigna::updateOrCreate(
                ['legacy_id' => $legacySigna->id],
                [
                    'name' => trim($legacySigna->nama),
                    'is_active' => $isActive,
                ]
            );

            if ($signa->wasRecentlyCreated) {
                $imported++;
            } else {
                $updated++;
            }

            // Handle soft delete if it's marked as deleted in legacy
            if ($legacySigna->deleted_at && $legacySigna->deleted_at !== '0000-00-00 00:00:00') {
                if (!$signa->trashed()) {
                    $signa->delete();
                }
            } else {
                if ($signa->trashed()) {
                    $signa->restore();
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Import completed! $imported newly created, $updated updated.");
    }
}
