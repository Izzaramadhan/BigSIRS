<?php

namespace App\Console\Commands;

use App\Models\MasterData\RadiologyType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportRadiologyTypesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:radiology-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import radiology types from legacy database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting import of Radiology Types...');

        $legacyData = DB::connection('legacy')->table('ref_tipe_rad')->get();

        $stats = [
            'total' => $legacyData->count(),
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'unchanged' => 0,
        ];

        foreach ($legacyData as $row) {
            $name = trim($row->nama);

            if (empty($name)) {
                $stats['skipped']++;
                $this->warn("Skipped row ID {$row->id}: Name is empty");
                continue;
            }

            $description = trim($row->deskripsi);

            $targetData = [
                'name' => $name,
                'description' => $description ?: null,
                'is_active' => (bool)$row->status,
                'created_at' => $row->created_at ?: now(),
                'updated_at' => $row->updated_at ?: now(),
            ];
            
            if ($row->deleted_at && $row->deleted_at !== '0000-00-00 00:00:00') {
                $targetData['deleted_at'] = $row->deleted_at;
            }

            $existing = RadiologyType::where('legacy_id', $row->id)->first();

            if ($existing) {
                // Check if changed
                $isChanged = $existing->name !== $targetData['name']
                    || $existing->description !== $targetData['description']
                    || $existing->is_active !== $targetData['is_active'];

                if ($isChanged) {
                    $existing->update($targetData);
                    $stats['updated']++;
                } else {
                    $stats['unchanged']++;
                }
            } else {
                // Also check if name exists but with different legacy_id
                $duplicateName = RadiologyType::where('name', $targetData['name'])->first();
                if ($duplicateName) {
                    $stats['skipped']++;
                    $this->warn("Skipped row ID {$row->id}: Name '{$name}' already exists with legacy_id {$duplicateName->legacy_id}");
                    continue;
                }

                $targetData['legacy_id'] = $row->id;
                RadiologyType::create($targetData);
                $stats['inserted']++;
            }
        }

        $this->info("Import completed:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Source', $stats['total']],
                ['Inserted', $stats['inserted']],
                ['Updated', $stats['updated']],
                ['Unchanged', $stats['unchanged']],
                ['Skipped', $stats['skipped']],
            ]
        );

        return self::SUCCESS;
    }
}
