<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MasterData\MedicalProcedureTariff;
use App\Models\TariffTypeComponent;

$tariffs = MedicalProcedureTariff::with('components', 'tariffType.components')->get();
$missingCount = 0;

foreach ($tariffs as $t) {
    if (!$t->tariffType) continue;
    
    // Refresh allowed components
    $allowed = $t->tariffType->components()->pluck('tariff_component_id')->toArray();
    
    foreach ($t->components as $c) {
        if (!in_array($c->tariff_component_id, $allowed)) {
            // Create the missing relation!
            TariffTypeComponent::create([
                'tariff_type_id' => $t->tariff_type_id,
                'tariff_component_id' => $c->tariff_component_id,
                'percentage' => $c->percentage_snapshot,
                'needs_review' => true // Flag as needs review since it was auto-generated to fix legacy inconsistency
            ]);
            
            // Add to allowed so we don't duplicate
            $allowed[] = $c->tariff_component_id;
            
            echo "Created missing relation: Type {$t->tariff_type_id} -> Component {$c->tariff_component_id} (Percentage: {$c->percentage_snapshot})\n";
            $missingCount++;
        }
    }
}

echo "Total missing relations fixed: {$missingCount}\n";
