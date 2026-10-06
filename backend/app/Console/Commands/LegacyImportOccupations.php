<?php

namespace App\Console\Commands;

use App\Models\Occupation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LegacyImportOccupations extends Command
{
    protected $signature = 'legacy:import-occupations {--dry-run : Only show what would be done without making changes}';

    protected $description = 'Import occupations from legacy database';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Starting '.($isDryRun ? 'DRY-RUN ' : '').'import of Occupations...');

        try {
            $legacyOccupations = DB::connection('legacy')->table('ref_pekerjaan')->get();
        } catch (\Exception $e) {
            $this->error('Failed to connect to legacy DB or table not found: '.$e->getMessage());

            return;
        }

        $stats = [
            'total_legacy' => $legacyOccupations->count(),
            'soft_deleted' => 0,
            'invalid' => 0,
            'would_create' => 0,
            'would_update' => 0,
            'duplicates_skipped' => 0,
        ];

        DB::beginTransaction();

        try {
            foreach ($legacyOccupations as $legacy) {
                // Check soft delete status (status = '0')
                if (isset($legacy->status) && $legacy->status === '0') {
                    $stats['soft_deleted']++;

                    continue;
                }

                if (isset($legacy->deleted_at) && $legacy->deleted_at !== '0000-00-00 00:00:00' && $legacy->deleted_at !== null) {
                    $stats['soft_deleted']++;

                    continue;
                }

                $name = trim(preg_replace('/\s+/', ' ', $legacy->pekerjaan ?? ''));
                if (empty($name)) {
                    $stats['invalid']++;

                    continue;
                }

                $isActive = (! isset($legacy->status) || $legacy->status === '1');

                // Check by legacy_id first
                $existing = Occupation::where('legacy_id', $legacy->id)->first();

                if ($existing) {
                    if ($existing->name !== $name || $existing->is_active !== $isActive) {
                        $stats['would_update']++;
                        if (! $isDryRun) {
                            $existing->update([
                                'name' => $name,
                                'is_active' => $isActive,
                            ]);
                        }
                    } else {
                        $stats['duplicates_skipped']++;
                    }
                } else {
                    // Check by name just in case
                    $existingByName = Occupation::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
                    if ($existingByName) {
                        $stats['would_update']++;
                        if (! $isDryRun) {
                            $existingByName->update([
                                'legacy_id' => $legacy->id,
                                'is_active' => $isActive,
                            ]);
                        }
                    } else {
                        $stats['would_create']++;
                        if (! $isDryRun) {
                            Occupation::create([
                                'legacy_id' => $legacy->id,
                                'name' => $name,
                                'is_active' => $isActive,
                            ]);
                        }
                    }
                }
            }

            if ($isDryRun) {
                DB::rollBack();
                $this->info("\nDry-run completed successfully.");
            } else {
                DB::commit();
                $this->info("\nImport completed successfully.");
            }

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Legacy Records', $stats['total_legacy']],
                    ['Soft Deleted (Skipped)', $stats['soft_deleted']],
                    ['Invalid (Empty Name)', $stats['invalid']],
                    ['Would Create / Created', $stats['would_create']],
                    ['Would Update / Updated', $stats['would_update']],
                    ['Unchanged / Skipped', $stats['duplicates_skipped']],
                ]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('An error occurred during import: '.$e->getMessage());
        }
    }
}
