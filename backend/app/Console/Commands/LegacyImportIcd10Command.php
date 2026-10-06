<?php

namespace App\Console\Commands;

use App\Models\MasterData\Icd10Code;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LegacyImportIcd10Command extends Command
{
    protected $signature = 'legacy:import-icd10 {--dry-run : Only show what would happen without actually importing}';

    protected $description = 'Import ICD-10 codes from legacy database';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Starting ICD-10 Import from legacy DB '.($isDryRun ? '(DRY RUN)' : ''));

        try {
            $legacyDb = DB::connection('legacy');
            $legacyDb->getPdo();
        } catch (\Exception $e) {
            $this->error('Failed to connect to legacy database: '.$e->getMessage());

            return 1;
        }

        $stats = [
            'total_read' => 0,
            'skipped_soft_deleted' => 0,
            'valid' => 0,
            'conflicts' => 0,
            'would_create' => 0,
            'would_update' => 0,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
            'duplicate_codes' => 0,
            'empty_codes' => 0,
            'hierarchy_warnings' => 0,
            'inacbg_warnings' => 0,
            'tariff_warnings' => 0,
        ];

        // Process in chunks to avoid memory issues
        $legacyDb->table('ref_diagnosa')
            ->orderBy('id')
            ->chunk(1000, function ($records) use (&$stats, $isDryRun) {
                foreach ($records as $row) {
                    $stats['total_read']++;

                    // Check soft deleted
                    if ($row->deleted_at && $row->deleted_at !== '0000-00-00 00:00:00') {
                        $stats['skipped_soft_deleted']++;

                        continue;
                    }

                    $code = trim($row->code ?? '');
                    if (empty($code)) {
                        $stats['empty_codes']++;

                        continue;
                    }
                    $code = strtoupper($code);

                    $name = trim($row->nama ?? '');
                    if (empty($name)) {
                        $name = 'Tidak Diketahui (ID: '.$row->id.')';
                    }

                    $stats['valid']++;

                    // Build target payload
                    $targetData = [
                        'legacy_id' => $row->id,
                        'code' => $code,
                        'name' => $name,
                        'english_name' => trim($row->nama_en ?? ''),
                        'description' => trim($row->deskripsi ?? ''),
                        'is_medical_history' => $row->riwayat_penyakit == '1',
                        'is_active' => $row->status == '1',
                        'inacbg_code' => trim($row->kode_inacbg ?? ''),
                        'inacbg_name' => trim($row->deskripsi_inacbg ?? ''),
                        'class_1_tariff' => is_numeric($row->tarif_kelas1) ? (float) $row->tarif_kelas1 : 0,
                        'class_2_tariff' => is_numeric($row->tarif_kelas2) ? (float) $row->tarif_kelas2 : 0,
                        'class_3_tariff' => is_numeric($row->tarif_kelas3) ? (float) $row->tarif_kelas3 : 0,
                    ];

                    if (Str::contains($code, '-')) {
                        $stats['hierarchy_warnings']++;
                    }

                    if (! empty($targetData['inacbg_code'])) {
                        $stats['inacbg_warnings']++;
                    }

                    if ($targetData['class_1_tariff'] > 0 || $targetData['class_2_tariff'] > 0 || $targetData['class_3_tariff'] > 0) {
                        $stats['tariff_warnings']++;
                    }

                    // Check duplicate code conflicts in target DB (from other legacy records or same code)
                    $existingByCode = Icd10Code::where('code', $code)->first();
                    if ($existingByCode && $existingByCode->legacy_id !== (int) $row->id) {
                        // In a dry run, we'd log the conflict but in actual we might skip or fail it.
                        // We will allow one code to overwrite if not duplicate within this run, but since they are in legacy:
                        // Legacy profiling showed B20 is duplicated in legacy.
                        $stats['duplicate_codes']++;
                        $stats['conflicts']++;
                        if (! $isDryRun) {
                            $this->warn("Conflict: Code {$code} already exists for another record (Legacy ID: {$row->id} vs Target Legacy ID: {$existingByCode->legacy_id}). Skipping.");
                        }

                        continue;
                    }

                    $existing = Icd10Code::where('legacy_id', $row->id)->first();

                    if (! $existing) {
                        $stats['would_create']++;
                        if (! $isDryRun) {
                            try {
                                Icd10Code::create($targetData);
                                $stats['created']++;
                            } catch (\Exception $e) {
                                $stats['failed']++;
                                $this->error("Failed to create {$code}: ".$e->getMessage());
                            }
                        }
                    } else {
                        $stats['would_update']++;
                        if (! $isDryRun) {
                            try {
                                $existing->update($targetData);
                                $stats['updated']++;
                            } catch (\Exception $e) {
                                $stats['failed']++;
                                $this->error("Failed to update {$code}: ".$e->getMessage());
                            }
                        }
                    }
                }
            });

        $this->info('Import Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Read', $stats['total_read']],
                ['Skipped Soft Deleted', $stats['skipped_soft_deleted']],
                ['Valid', $stats['valid']],
                ['Conflicts', $stats['conflicts']],
                ['Would Create', $stats['would_create']],
                ['Would Update', $stats['would_update']],
                ['Created', $stats['created']],
                ['Updated', $stats['updated']],
                ['Failed', $stats['failed']],
                ['Duplicate Codes', $stats['duplicate_codes']],
                ['Empty Codes', $stats['empty_codes']],
                ['Hierarchy Warnings', $stats['hierarchy_warnings']],
                ['INACBG Warnings', $stats['inacbg_warnings']],
                ['Tariff Warnings', $stats['tariff_warnings']],
            ]
        );

        if ($isDryRun) {
            $this->info('This was a DRY RUN. No actual data was modified.');
        }

        return 0;
    }
}
