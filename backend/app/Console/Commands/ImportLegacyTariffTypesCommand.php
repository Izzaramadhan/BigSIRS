<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\TariffType;
use App\Models\TariffComponent;
use App\Models\TariffTypeComponent;

class ImportLegacyTariffTypesCommand extends Command
{
    protected $signature = 'legacy:import-tariff-types {--dry-run : Only show what would be imported without actually writing to database}';
    protected $description = 'Import legacy Tariff Types (Jenis Tarif) and their component relations from simrs_legacy';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info("Starting Legacy Tariff Types Import" . ($isDryRun ? " (DRY RUN)" : ""));
        
        $legacyDb = DB::connection('legacy');
        
        $stats = [
            'total_read' => 0,
            'skipped_soft_deleted' => 0,
            'would_create' => 0,
            'would_update' => 0,
            'relations_read' => 0,
            'valid_relations' => 0,
            'missing_component' => 0,
            'duplicate_relations' => 0,
            'outliers_above_100' => 0,
            'outliers_5000' => 0,
            'total_percentage_not_100' => 0,
            'no_components' => 0,
            'code_null' => 0,
        ];

        // Component Map: legacy_id => target_id
        $componentMap = TariffComponent::whereNotNull('legacy_id')->pluck('id', 'legacy_id')->toArray();
        $this->info("Loaded " . count($componentMap) . " mapped components.");

        // Read all Jenis Tarif
        $legacyTypes = $legacyDb->table('ref_jenis_tarif')->get();
        $stats['total_read'] = $legacyTypes->count();
        
        $outliers5000List = [];

        DB::beginTransaction();

        try {
            foreach ($legacyTypes as $legacy) {
                // Handle soft delete
                $isDeleted = false;
                if ($legacy->deleted_at && !in_array($legacy->deleted_at, ['0000-00-00', '0000-00-00 00:00:00'])) {
                    $isDeleted = true;
                    // We can either skip them or import them as trashed. 
                    // Let's import them as trashed so relations aren't completely lost if needed, 
                    // or skip if we don't want trash. The instruction says "Soft Deleted Skipped" in dry-run report.
                    // Wait, user said: "Jika Komponen nonaktif masih digunakan oleh Jenis Tarif legacy... Tetap tampil pada form Edit."
                    // For Jenis Tarif itself, we can skip importing soft-deleted ones, or import as soft-deleted. Let's just import as soft-deleted.
                }

                $name = $legacy->nama ? preg_replace('/\s+/', ' ', trim($legacy->nama)) : 'Unknown';
                $code = $legacy->kode ? strtoupper(trim($legacy->kode)) : null;
                $description = $legacy->deskripsi ? trim($legacy->deskripsi) : null;
                $isActive = $legacy->status == 1;

                if (empty($code)) {
                    $stats['code_null']++;
                }

                $needsReview = empty($code); // Flag if no code

                // Get relations
                $legacyRelations = $legacyDb->table('map_jenis_tarif_komponen')
                                            ->where('id_jenis_tarif', $legacy->id)
                                            ->get();

                $stats['relations_read'] += $legacyRelations->count();

                if ($legacyRelations->count() === 0) {
                    $stats['no_components']++;
                    $needsReview = true;
                }

                $totalPercentage = 0;
                $seenComponents = [];
                $validRels = [];

                foreach ($legacyRelations as $rel) {
                    if (!isset($componentMap[$rel->id_komponen])) {
                        $stats['missing_component']++;
                        continue;
                    }
                    
                    $targetCompId = $componentMap[$rel->id_komponen];
                    
                    if (isset($seenComponents[$targetCompId])) {
                        $stats['duplicate_relations']++;
                        $needsReview = true;
                    }
                    $seenComponents[$targetCompId] = true;

                    $percentage = floatval($rel->persen);
                    $totalPercentage += $percentage;

                    $relNeedsReview = false;
                    if ($percentage > 100) {
                        $stats['outliers_above_100']++;
                        $relNeedsReview = true;
                        if ($percentage == 5000) {
                            $stats['outliers_5000']++;
                            $outliers5000List[] = "Legacy Rel ID: {$rel->id}, Jenis Tarif ID: {$legacy->id}, Komponen ID: {$rel->id_komponen}";
                        }
                    }

                    if ($percentage < 0) {
                        // "Nilai negatif atau non-numeric diperlakukan sebagai conflict dan tidak diimpor"
                        continue;
                    }

                    $stats['valid_relations']++;
                    
                    $validRels[] = [
                        'legacy_id' => $rel->id,
                        'tariff_component_id' => $targetCompId,
                        'percentage' => $percentage,
                        'needs_review' => $relNeedsReview,
                    ];
                }

                if ($legacyRelations->count() > 0 && $totalPercentage != 100) {
                    $stats['total_percentage_not_100']++;
                    $needsReview = true;
                }

                if (!$isDryRun) {
                    $type = TariffType::withTrashed()->updateOrCreate(
                        ['legacy_id' => $legacy->id],
                        [
                            'name' => $name,
                            'code' => $code,
                            'description' => $description,
                            'is_active' => $isActive,
                            'needs_review' => $needsReview,
                            'deleted_at' => $isDeleted ? ($legacy->deleted_at ?? now()) : null,
                        ]
                    );

                    // Sync components
                    // Note: updateOrCreate by legacy_id to avoid creating duplicates on multiple runs
                    $existingRelIds = [];
                    foreach ($validRels as $relData) {
                        $pivot = TariffTypeComponent::updateOrCreate(
                            ['legacy_id' => $relData['legacy_id']],
                            [
                                'tariff_type_id' => $type->id,
                                'tariff_component_id' => $relData['tariff_component_id'],
                                'percentage' => $relData['percentage'],
                                'needs_review' => $relData['needs_review'],
                            ]
                        );
                        $existingRelIds[] = $pivot->id;
                    }

                    // Delete removed legacy relations (if any)
                    TariffTypeComponent::where('tariff_type_id', $type->id)
                                       ->whereNotIn('id', $existingRelIds)
                                       ->delete();
                } else {
                    $existing = TariffType::withTrashed()->where('legacy_id', $legacy->id)->first();
                    if ($existing) {
                        $stats['would_update']++;
                    } else {
                        $stats['would_create']++;
                    }
                }
            }

            if ($isDryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Import failed: " . $e->getMessage());
            return 1;
        }

        $this->info("--- Import Report ---");
        $this->line("Total Tariff Types Read : " . $stats['total_read']);
        $this->line("Would Create Master     : " . $stats['would_create']);
        $this->line("Would Update Master     : " . $stats['would_update']);
        $this->line("Code NULL               : " . $stats['code_null']);
        $this->line("No Components           : " . $stats['no_components']);
        $this->line("Relation Rows Read      : " . $stats['relations_read']);
        $this->line("Valid Relations         : " . $stats['valid_relations']);
        $this->line("Missing Component Map   : " . $stats['missing_component']);
        $this->line("Duplicate Relations     : " . $stats['duplicate_relations']);
        $this->line("Total % Not 100         : " . $stats['total_percentage_not_100']);
        $this->line("Percentage > 100        : " . $stats['outliers_above_100']);
        $this->line("Percentage == 5000      : " . $stats['outliers_5000']);
        
        if ($stats['outliers_5000'] > 0) {
            $this->line("\n--- Outliers 5000 List ---");
            foreach ($outliers5000List as $msg) {
                $this->line("- " . $msg);
            }
        }

        return 0;
    }
}
