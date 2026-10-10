<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MasterData\MedicalProcedureTariff;
use App\Models\TariffTypeComponent;

$tariffs = MedicalProcedureTariff::with('components', 'tariffType.components')->get();
$missing = [];
foreach ($tariffs as $t) {
    if (!$t->tariffType) continue;
    $allowed = $t->tariffType->components->pluck('tariff_component_id')->toArray();
    foreach ($t->components as $c) {
        if (!in_array($c->tariff_component_id, $allowed)) {
            $missing[] = [
                'tariff_type_id' => $t->tariff_type_id,
                'tariff_component_id' => $c->tariff_component_id,
                'percentage' => $c->percentage_snapshot
            ];
        }
    }
}

$missing = array_unique($missing, SORT_REGULAR);
echo json_encode(array_values($missing), JSON_PRETTY_PRINT);
