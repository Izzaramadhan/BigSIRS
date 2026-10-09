<?php

namespace App\Console\Commands;

use App\Models\MasterData\Position;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyPositionsCommand extends Command
{
    protected $signature = 'import:legacy-positions';

    protected $description = 'Import positions from legacy ref_jabatan table';

    public function handle()
    {
        $this->info('Starting positions import...');

        try {
            $legacyPositions = DB::connection('legacy')->table('ref_jabatan')->get();

            $created = 0;
            $updated = 0;

            foreach ($legacyPositions as $legacy) {
                $status = isset($legacy->status) ? (bool) $legacy->status : true;

                $position = Position::withTrashed()->updateOrCreate(
                    ['legacy_id' => $legacy->id],
                    [
                        'name' => trim($legacy->jabatan),
                        'description' => trim($legacy->deskripsi ?? ''),
                        'is_active' => $status,
                    ]
                );

                if ($position->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }

            $this->info("Import completed! Created: {$created}, Updated: {$updated}");
        } catch (\Exception $e) {
            $this->error('Import failed: '.$e->getMessage());
        }
    }
}
