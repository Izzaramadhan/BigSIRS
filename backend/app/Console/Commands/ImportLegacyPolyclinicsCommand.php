<?php

namespace App\Console\Commands;

use App\Models\Polyclinic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportLegacyPolyclinicsCommand extends Command
{
    protected $signature = 'legacy:import-polyclinics {--dry-run : Uji coba pemetaan tanpa mengubah data target}';

    protected $description = 'Import Polyclinic reference data from legacy database (ref_poliklinik)';

    /** Legacy IDs confirmed as actually soft-deleted. Populated during Phase 1. */
    protected array $softDeletedLegacyIds = [];

    /** Children whose parents are soft-deleted. */
    protected array $hierarchyWarnings = [];

    protected array $stats = [
        'read'            => 0,
        'skipped'         => 0,
        'conflict'        => 0,
        'valid'           => 0,
        'would_create'    => 0,
        'would_update'    => 0,
        'created'         => 0,
        'updated'         => 0,
        'failed'          => 0,
        'status_active'   => 0,
        'status_inactive' => 0,
    ];

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Starting legacy Polyclinics import...');
        if ($isDryRun) {
            $this->warn('RUNNING IN DRY-RUN MODE (No data will be saved)');
        }

        // ------------------------------------------------------------------ //
        // Read legacy data
        // ------------------------------------------------------------------ //
        try {
            $legacyData = DB::connection('legacy')
                ->table('ref_poliklinik')
                ->orderBy('id')
                ->get();
        } catch (\Exception $e) {
            $this->error('Failed to connect to legacy database or read ref_poliklinik table.');
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        $this->stats['read'] = $legacyData->count();
        $this->info("Read {$this->stats['read']} records from ref_poliklinik.");

        $validRecords = [];
        $codeTracker  = [];
        $conflicts    = [];

        // ------------------------------------------------------------------ //
        // Phase 1: Validate and normalize all records
        // ------------------------------------------------------------------ //
        foreach ($legacyData as $item) {

            // --- Soft-delete detection (rules from audit) ---
            if ($this->isSoftDeleted($item->deleted_at)) {
                $this->softDeletedLegacyIds[] = $item->id;
                $this->stats['skipped']++;
                continue;
            }

            // --- Status mapping: independent from soft-delete ---
            $isActive = (string) $item->status === '1';
            if ($isActive) {
                $this->stats['status_active']++;
            } else {
                $this->stats['status_inactive']++;
            }

            // --- Code normalization ---
            $legacyId = $item->id;
            $rawCode  = trim((string) $item->kode);
            $code     = strtoupper($rawCode);

            if ($code === '') {
                $conflicts[] = [
                    'legacy_id' => $legacyId, 'issue' => 'Empty code',
                    'raw_code'  => $rawCode,  'raw_name' => $item->nama,
                    'conflicts_with_legacy_id' => null,
                ];
                $this->stats['conflict']++;
                continue;
            }

            if (isset($codeTracker[$code])) {
                $conflicts[] = [
                    'legacy_id' => $legacyId, 'issue' => 'Duplicate code',
                    'raw_code'  => $rawCode,  'raw_name' => $item->nama,
                    'conflicts_with_legacy_id' => $codeTracker[$code],
                ];
                $this->stats['conflict']++;
                continue;
            }

            $codeTracker[$code] = $legacyId;

            // --- Field normalization ---
            $name             = trim(preg_replace('/\s+/', ' ', (string) $item->nama));
            $serviceType      = trim((string) ($item->jenis ?? '')) ?: null;
            $description      = trim((string) ($item->deskripsi ?? '')) ?: null;
            $isVisible        = $this->normalizeLegacyBool($item->tampil ?? '1');
            $isOnlineVisible  = $this->normalizeLegacyBool($item->tampil_online ?? '0');
            $quota            = max(0, (int) ($item->kuota ?? 0));
            $jknQuota         = max(0, (int) ($item->kuota_jkn ?? 0));
            $bpjsCode         = trim((string) ($item->kode_bpjs ?? '')) ?: null;
            $satusehatCode    = trim((string) ($item->ihs_id_location ?? '')) ?: null;

            // Warehouse: stored as legacy raw ID until Master Gudang is built
            $legacyWarehouseId = isset($item->id_gudang) && (int) $item->id_gudang > 0
                ? (int) $item->id_gudang
                : null;

            // Parent ID: raw legacy reference for Phase 2
            $legacyParentId = (!empty($item->parent_id) && (int) $item->parent_id > 0)
                ? (int) $item->parent_id
                : null;

            $validRecords[] = [
                'legacy_id'                  => $legacyId,
                'code'                       => $code,
                'name'                       => $name,
                'service_type'               => $serviceType,
                'description'                => $description,
                'is_visible'                 => $isVisible,
                'is_online_visible'          => $isOnlineVisible,
                'quota'                      => $quota,
                'jkn_quota'                  => $jknQuota,
                'bpjs_code'                  => $bpjsCode,
                'satusehat_code'             => $satusehatCode,
                'is_active'                  => $isActive,
                'legacy_default_warehouse_id' => $legacyWarehouseId,
                'legacy_parent'              => $legacyParentId,
            ];

            $this->stats['valid']++;
        }

        // ------------------------------------------------------------------ //
        // Hierarchy check: children pointing to soft-deleted parents
        // Policy: parent_id set to NULL — documented behavior.
        // ------------------------------------------------------------------ //
        $softDeletedSet = array_flip($this->softDeletedLegacyIds);
        foreach ($validRecords as &$record) {
            if ($record['legacy_parent'] !== null && isset($softDeletedSet[$record['legacy_parent']])) {
                $this->hierarchyWarnings[] = [
                    'child_legacy_id'  => $record['legacy_id'],
                    'child_code'       => $record['code'],
                    'child_name'       => $record['name'],
                    'parent_legacy_id' => $record['legacy_parent'],
                    'action'           => 'parent_id set to NULL (parent was soft-deleted in legacy)',
                ];
                $record['legacy_parent'] = null;
            }
        }
        unset($record);

        // ------------------------------------------------------------------ //
        // Dry-run: report without writing
        // ------------------------------------------------------------------ //
        if ($isDryRun) {
            $existingLegacyIds = Polyclinic::withTrashed()
                ->whereIn('legacy_id', array_column($validRecords, 'legacy_id'))
                ->pluck('legacy_id')
                ->flip()
                ->toArray();

            foreach ($validRecords as $record) {
                if (isset($existingLegacyIds[$record['legacy_id']])) {
                    $this->stats['would_update']++;
                } else {
                    $this->stats['would_create']++;
                }
            }

            $this->showConflicts($conflicts);
            $this->showHierarchyWarnings();
            $this->printReport($isDryRun);
            return Command::SUCCESS;
        }

        // ------------------------------------------------------------------ //
        // Phase 2: Write to target DB in a transaction
        // ------------------------------------------------------------------ //
        $this->showConflicts($conflicts);
        $this->showHierarchyWarnings();

        DB::beginTransaction();
        try {
            // First pass: upsert without parent_id
            foreach ($validRecords as $data) {
                $polyclinic = Polyclinic::where('legacy_id', $data['legacy_id'])->first();
                $isNew      = !$polyclinic;

                if ($isNew) {
                    $polyclinic            = new Polyclinic();
                    $polyclinic->legacy_id = $data['legacy_id'];
                }

                $polyclinic->code                        = $data['code'];
                $polyclinic->name                        = $data['name'];
                $polyclinic->service_type                = $data['service_type'];
                $polyclinic->description                 = $data['description'];
                $polyclinic->is_visible                  = $data['is_visible'];
                $polyclinic->is_online_visible           = $data['is_online_visible'];
                $polyclinic->quota                       = $data['quota'];
                $polyclinic->jkn_quota                   = $data['jkn_quota'];
                $polyclinic->bpjs_code                   = $data['bpjs_code'];
                $polyclinic->satusehat_code              = $data['satusehat_code'];
                $polyclinic->is_active                   = $data['is_active'];
                $polyclinic->legacy_default_warehouse_id = $data['legacy_default_warehouse_id'];
                $polyclinic->parent_id                   = null; // resolve in second pass
                $polyclinic->save();

                if ($isNew) {
                    $this->stats['created']++;
                } else {
                    $this->stats['updated']++;
                }
            }

            // Second pass: resolve parent_id via legacy_id
            foreach ($validRecords as $data) {
                if ($data['legacy_parent'] !== null) {
                    $child  = Polyclinic::where('legacy_id', $data['legacy_id'])->first();
                    $parent = Polyclinic::where('legacy_id', $data['legacy_parent'])->first();

                    if ($child && $parent) {
                        $child->parent_id = $parent->id;
                        $child->save();
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Legacy Polyclinics import failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('Import failed. Transaction rolled back.');
            $this->error($e->getMessage());
            $this->stats['failed']  = $this->stats['valid'];
            $this->stats['created'] = 0;
            $this->stats['updated'] = 0;
            return Command::FAILURE;
        }

        $this->printReport($isDryRun);
        $this->info('Import completed successfully.');
        return Command::SUCCESS;
    }

    // ------------------------------------------------------------------ //
    // Helpers
    // ------------------------------------------------------------------ //

    /**
     * Soft-delete detection.
     * NULL, '', '0000-00-00', '0000-00-00 00:00:00' → NOT deleted.
     * Any other value → soft-deleted.
     */
    protected function isSoftDeleted(mixed $deletedAt): bool
    {
        if ($deletedAt === null) {
            return false;
        }
        $str = trim((string) $deletedAt);
        return !in_array($str, ['', '0000-00-00', '0000-00-00 00:00:00'], true);
    }

    /**
     * Normalize legacy enum('0','1') to boolean.
     * Legacy tampil and tampil_online use this pattern.
     */
    protected function normalizeLegacyBool(mixed $value): bool
    {
        return (string) $value === '1';
    }

    protected function showConflicts(array $conflicts): void
    {
        if (count($conflicts) === 0) {
            return;
        }

        $this->warn(PHP_EOL . 'Code Conflicts Detected (' . count($conflicts) . '):');
        $this->table(
            ['Legacy ID', 'Issue', 'Raw Code', 'Raw Name', 'Conflicts With (ID)'],
            array_map(fn ($c) => [
                $c['legacy_id'], $c['issue'], $c['raw_code'], $c['raw_name'],
                $c['conflicts_with_legacy_id'] ?? 'N/A',
            ], $conflicts)
        );
    }

    protected function showHierarchyWarnings(): void
    {
        if (count($this->hierarchyWarnings) === 0) {
            return;
        }

        $this->warn(PHP_EOL . 'Hierarchy Warnings (' . count($this->hierarchyWarnings) . '):');
        $this->warn('Policy: parent_id set to NULL for the records below (parent was soft-deleted in legacy).');
        $this->table(
            ['Child Legacy ID', 'Child Code', 'Child Name', 'Soft-Deleted Parent (Legacy ID)', 'Action'],
            array_map(fn ($w) => [
                $w['child_legacy_id'], $w['child_code'], $w['child_name'],
                $w['parent_legacy_id'], $w['action'],
            ], $this->hierarchyWarnings)
        );
    }

    protected function printReport(bool $isDryRun): void
    {
        $prefix = $isDryRun ? 'Would ' : '';
        $this->info(PHP_EOL . ($isDryRun ? '[DRY-RUN] ' : '') . 'Import Report');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Read',                              $this->stats['read']],
                ['Skipped (Soft Deleted)',                  $this->stats['skipped']],
                ['Conflicts (Need Action)',                  $this->stats['conflict']],
                ['Valid for Import',                         $this->stats['valid']],
                [$prefix . 'Create',                        $isDryRun ? $this->stats['would_create'] : $this->stats['created']],
                [$prefix . 'Update',                        $isDryRun ? $this->stats['would_update'] : $this->stats['updated']],
                ['Failed',                                   $this->stats['failed']],
                ['Status Active (is_active=true)',           $this->stats['status_active']],
                ['Status Inactive (is_active=false)',        $this->stats['status_inactive']],
                ['Hierarchy Warnings (parent → NULL)',       count($this->hierarchyWarnings)],
            ]
        );
    }
}
