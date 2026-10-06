<?php

namespace App\Console\Commands\Legacy;

use App\Enums\GuarantorType;
use App\Models\Guarantor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Signature('legacy:import-guarantors {--dry-run : Only show what would be imported}')]
#[Description('Import guarantors (Jenis Asuransi) from legacy database')]
class LegacyImportGuarantorsCommand extends Command
{
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->info('Running in DRY-RUN mode. No data will be written.');
        }

        $legacyDb = DB::connection('legacy');

        $this->info('Fetching legacy guarantors from ref_jenis_asuransi...');

        $rows = $legacyDb->select('
            SELECT id, jenis_asuransi, pemerintah, status, type, id_inacbg, kode, deleted_at
            FROM ref_jenis_asuransi
        ');

        $stats = [
            'read' => count($rows),
            'skipped_deleted' => 0,
            'create' => 0,
            'update' => 0,
            'failed' => 0,
        ];

        $this->withProgressBar($rows, function ($row) use ($dryRun, &$stats) {
            try {
                if ($row->deleted_at && $row->deleted_at !== '0000-00-00 00:00:00') {
                    $stats['skipped_deleted']++;

                    return;
                }

                $type = match ($row->type) {
                    'UMUM' => GuarantorType::SelfPay,
                    'BPJS' => GuarantorType::Bpjs,
                    'PRIVATE' => GuarantorType::PrivateInsurance,
                    default => GuarantorType::Other,
                };

                $code = $row->kode;
                if (empty($code)) {
                    $code = 'GUR-'.str_pad($row->id, 5, '0', STR_PAD_LEFT);
                }

                $inacbgId = $row->id_inacbg;
                if (trim((string) $inacbgId) === '') {
                    $inacbgId = null;
                }

                if (! $dryRun) {
                    $guarantor = Guarantor::withTrashed()->updateOrCreate(
                        ['legacy_id' => $row->id],
                        [
                            'name' => trim($row->jenis_asuransi),
                            'code' => $code,
                            'type' => $type,
                            'is_government' => (bool) $row->pemerintah,
                            'inacbg_id' => $inacbgId,
                            'is_active' => (bool) $row->status,
                            'deleted_at' => null, // restore if soft deleted
                        ]
                    );

                    if ($guarantor->wasRecentlyCreated) {
                        $stats['create']++;
                    } else {
                        $stats['update']++;
                    }
                } else {
                    $exists = Guarantor::withTrashed()->where('legacy_id', $row->id)->exists();
                    if ($exists) {
                        $stats['update']++;
                    } else {
                        $stats['create']++;
                    }
                }
            } catch (\Exception $e) {
                $stats['failed']++;
                if (! $dryRun) {
                    Log::error("Failed importing guarantor legacy_id {$row->id}: ".$e->getMessage());
                }
            }
        });

        $this->newLine();
        $this->info('Import completed.');
        $this->table(
            ['Total Read', 'Valid/Processed', 'Skipped Soft Deleted', 'Would Create / Created', 'Would Update / Updated', 'Failed'],
            [[
                $stats['read'],
                $stats['create'] + $stats['update'],
                $stats['skipped_deleted'],
                $stats['create'],
                $stats['update'],
                $stats['failed'],
            ]]
        );
    }
}
