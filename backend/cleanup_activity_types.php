<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ActivityType;
use Illuminate\Support\Facades\DB;

$legacyIdsToDelete = range(57, 90);

DB::beginTransaction();
try {
    $recordsToDelete = ActivityType::whereIn('legacy_id', $legacyIdsToDelete)->get();
    
    $deletedCount = 0;
    $deletedIds = [];
    
    foreach ($recordsToDelete as $record) {
        $deletedIds[] = $record->id;
        // First set parent_id to null for children of this record to avoid constraint errors if any
        ActivityType::where('parent_id', $record->id)->update(['parent_id' => null]);
        
        // Force delete the record since they were incorrectly imported
        $record->forceDelete();
        $deletedCount++;
    }
    
    DB::commit();
    echo "Successfully deleted $deletedCount incorrect baby weight records.\n";
    echo "Deleted IDs: " . implode(', ', $deletedIds) . "\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Failed to delete: " . $e->getMessage() . "\n";
}
