<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MasterData\MedicineRoute;
use Carbon\Carbon;

class ImportMedicineRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:medicine-routes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import medicine routes from legacy database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting import of medicine routes...');

        try {
            $legacyRoutes = DB::connection('legacy')
                ->table('ref_obat_jalur_masuk')
                ->whereNull('delete_time')
                ->get();

            $imported = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($legacyRoutes as $row) {
                if (empty(trim($row->rute_obat))) {
                    $this->warn("Skipped ID {$row->id}: Name is empty");
                    $skipped++;
                    continue;
                }
                
                $objectCode = trim($row->obj_id) === '-' ? null : trim($row->obj_id);
                if ($objectCode === '') {
                    $objectCode = null;
                }

                $isActive = $row->status == '1' ? true : false;
                
                $route = MedicineRoute::where('legacy_id', $row->id)->first();
                if (!$route) {
                    $route = MedicineRoute::where('name', trim($row->rute_obat))->first();
                }

                if ($route) {
                    $route->update([
                        'legacy_id' => $row->id,
                        'name' => trim($row->rute_obat),
                        'object_code' => $objectCode,
                        'is_active' => $isActive,
                    ]);
                    $updated++;
                } else {
                    MedicineRoute::create([
                        'legacy_id' => $row->id,
                        'name' => trim($row->rute_obat),
                        'object_code' => $objectCode,
                        'is_active' => $isActive,
                        'created_at' => $row->create_time ? Carbon::parse($row->create_time) : now(),
                        'updated_at' => $row->update_time ? Carbon::parse($row->update_time) : now(),
                    ]);
                    $imported++;
                }
            }

            $this->info("Import completed: $imported imported, $updated updated, $skipped skipped.");
        } catch (\Exception $e) {
            $this->error("Import failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
